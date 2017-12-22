<?php
/**
 * @version $Revision: 971 $
 */

require_once(SITE_PATH . '/class/Filenames.php');
require_once(SITE_PATH . '/class/FileSystem.php');
require_once(SITE_PATH . '/class/FileBrowser.php');
require_once(SITE_PATH . '/class/Imagemagick.php');

/**
 * Class for handling file uploads in Modera.net
 *
 * @author Priit Pyld <priit.pold@modera.net>
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class FileUploader
{
    /**
     * Last error description
     *
     * @var string
     * @access protected
     */
    var $_last_error;

    /**
     * Permission for uploaded files
     *
     * @var int
     * @access private
     */
    var $_permission = 0644;

    /**
     * @return FileUploader
     */
    function FileUploader()
    {
        $this->_last_error = '';
    }

    /**
     * Clear last errors
     *
     * @access protected
     */
    function _clearLastError()
    {
        $this->_last_error = '';
    }

    /**
     * Get last error description
     *
     * @return string error description, or empty string if no errors happened
     *  during last executed action
     */
    function getLastError()
    {
        return $this->_last_error;
    }

    /**
     * Set last error
     *
     * @param string $error
     * @param bool $fail_on_error if set to TRUE than E_USER_ERROR will be generated,
     *  otherwise error string will be saved in internal variable
     * @access protected
     */
    function _setError($error, $fail_on_error)
    {
        if ($fail_on_error) {
            trigger_error($error, E_USER_ERROR);
        } else {
            $this->_last_error = $error;
        }
    }

    /**
     * Handle file uploading
     *
     * Resizes original file if needed, created thumbnail and copies files to
     * destination folder
     *
     * @param string $source path to raw uploaded file
     * @param stirng $destination path to destination file
     * @param string|NULL $big_img_size dimensions of big image, for example '300x400',
     *  if NULL or not specified, than big image will not be resized
     * @param string|NULL $thumb_size dimensions of thumbnail image, is NULL or not
     *  specified than thumbnail image will not be created
     * @param string|NULL $if_file_exists action to perform if destination file already
     *  exists, can be one of: "rename" - automaticaly rename current file, "replace"
     *  - replace destination file with new one, "skip" - do nothing. If not passed, or
     *  set to NULL 'rename' action will be used
     * @param string $fail_on_error if set to TRUE than E_USER_ERROR will be generated,
     *  otherwise FALSE will be returned, error description can be retrived via
     *  using getLastError() method
     * @global constant SITE_PATH
     * @return string|FALSE destination filename, FALSE if error happened
     */
    function processUploadedImage($source, $destination, $big_img_size = null
        , $thumb_size = null, $if_file_exists = null, $fail_on_error = true)
    {
        $this->_clearLastError();

        // default action in case if destination file exists
        if (is_null($if_file_exists)) {
            $if_file_exists = 'rename';
        }

        // create temporary file in cache/ directory
        $tmp_source = tempnam(SITE_PATH . '/cache', 'upload_');
        $thumb_img = array();
        if (!$tmp_source) {
            trigger_error('Unable to create temporary file', E_USER_ERROR);
        }

        // copy image to cache/ directory for processing with imagemagick
        if (!move_uploaded_file($source, $tmp_source) && (!is_readable($source)
            || !copy($source, $tmp_source)))
        {
            // unlink here is needed, because tmp_source file was created by
            // previous call to tempnam() function
            if (is_writeable($tmp_source)) unlink($tmp_source);
            $this->_setError('File upload/copy failed. Check file/folder permissions:'
                , $on_error);
            return false;
        }

        // arrays for storing main and thumbnail images sources and destinations
        $thumb_img = array();
        $main_img = array('source' => $tmp_source, 'destination' => $destination);

        if (!is_null($big_img_size) || !is_null($thumb_size)) {
            // processing uploaded image

            $im = new Imagemagick();

            // create thumbnail
            if (!is_null($thumb_size)) {
                // create thumbnail image
                $tmp_thumb_source = $tmp_source . '_thumb';
                if (!$im->thumbnail($tmp_source, $tmp_thumb_source, $thumb_size)) {
                    if (is_writeable($tmp_source)) unlink($tmp_source);
                    if (is_writeable($tmp_thumb_source)) unlink($tmp_thumb_source);
                    $this->_setError('Unable to create thumbnail image', $fail_on_error);
                    return false;
                }

                $thumb_img = array(
                    'source' => $tmp_thumb_source,
                    'destination' => Filenames::appendToName($destination, '_thumb'),
                );
            }

            // resize big image
            if (!is_null($big_img_size)) {
                if (!$im->resize($tmp_source, $tmp_source, $big_img_size)) {
                    if (is_writeable($tmp_source)) unlink($tmp_source);
                    if (is_writeable($tmp_thumb_source)) unlink($tmp_thumb_source);
                    $this->_setError('Unable to resize original image', $fail_on_error);
                    return false;
                }
            }
        }

        // choose destination file name
        $first_run = true;
        $dst = Filenames::pathinfo($main_img['destination']);
        do {
            $renamed = false;
            FileBrowser::autoRenameRecycled($main_img['destination'], true, $dst['filename']);
            foreach (array($main_img, $thumb_img) as $img) {
                if (!$img) continue;
                if (file_exists($img['destination'])) {
                    switch ($if_file_exists) {
                        case 'replace':
                            // move_uploaded_file() or copy() should replace
                            // destination file if it already exists
                            break;

                        case 'skip':
                            // in this special case set error message, but do not
                            // return FALSE (as when real errors happen)
                            if (is_writeable($tmp_source)) unlink($tmp_source);
                            if (is_writeable($tmp_thumb_source)) unlink($tmp_thumb_source);
                            $this->_setError('File or thumbnail exists. Skipped.', false);
                            return $main_img['destination'];

                        case 'rename':
                        default:
                            // rename files
                            $main_img['destination'] = Filenames::rename($main_img['destination']
                                , $first_run);
                            if ($thumb_img) {
                                $thumb_img['destination'] = Filenames::appendToName(
                                    $main_img['destination'], '_thumb');
                            }

                            $renamed = true;
                            $first_run = false;
                            // break to parent for() cycle, to run foreach() again
                            break(2);
                    }
                }
            }

        } while ($renamed);

        // move temporary files to destination folder
        foreach (array($main_img, $thumb_img) as $img) {
            if (!$img) continue;

            if (!is_writeable($img['source'])
                || (file_exists($img['destination']) && !is_writeable($img['destination']))
                || !FileSystem::rename($img['source'], $img['destination']))
            {
                // remove all temporary files
                foreach (array($main_img, $thumb_img) as $img) {
                    if (!$img) continue;

                    if (is_writeable($img['source'])) unlink($img['source']);
                    if (is_writeable($img['destination'])) unlink($img['destination']);
                }

                $this->_setError('File upload/copy failed. Check file/folder permissions:'
                    , $fail_on_error);

                return false;
            } else {
                chmod($img['destination'], $this->_permission);
            }
        }

        return $main_img['destination'];
    }

    /**
     * Process uploaded file
     *
     * Move uploaded file from temporary upload directory to destination folder,
     * or copy file from bunch_upload source folder
     *
     * @see processUploadedImage()
     * @param string $source
     * @param string $destination
     * @param string|NULL $if_file_exists
     * @param bool $fail_on_error
     * @return string|FALSE path to destination filename, or FALSE if error happened
     */
    function processUploadedFile($source, $destination, $if_file_exists = null
        , $fail_on_error = true)
    {
        $this->_clearLastError();

        // default action in case if destination file exists
        if (is_null($if_file_exists)) {
            $if_file_exists = 'rename';
        }

        // init browser for exclusion implements
        FileBrowser::autoRenameRecycled($destination, true);

        // if destination exists
        if (file_exists($destination)) {
            switch ($if_file_exists) {
                case 'replace':
                    // move_uploaded_file() or copy() should replace
                    // destination file if it already exists
                    break;

                case 'skip':
                    // in this special case set error message, but do not
                    // return FALSE (as when real errors happen)
                    $this->_setError('File exists. Skipped.', false);
                    return $destination;

                case 'rename':
                default:
                    $first_run = true;
                    $dst = Filenames::pathinfo($destination);
                    do {
                        $destination = Filenames::rename($destination, $first_run);
                        $first_run = false;
                        FileBrowser::autoRenameRecycled($destination, true, $dst['filename']);
                    } while (file_exists($destination));
            }
        }

        // move_uploaded_file / copy
        if (!move_uploaded_file($source, $destination)
            && (!is_readable($source) || !copy($source, $destination)))
        {
            $this->_setError('File upload/copy failed. Check file/folder permissions:'
                , $fail_on_error);
            return false;
        }

        chmod($destination, $this->_permission);
        return $destination;
    }
}