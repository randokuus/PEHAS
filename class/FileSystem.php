<?php
/**
 * @version $Revision: 834 $
 */

/**
 * Static class with collection of filesystem methods
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 * @static
 */
class FileSystem
{
    /**
     * Creates directory
     *
     * Main purpose for creating this method is adding support for recoursive
     * directory making for php versions before 5. This method is capable to
     * understand paths like "/home/./user/../user2/dir//"
     *
     * @param string $pathname
     * @param int $mode
     * @param bool $recursive
     * @param string $rec_from recursive creating of directories will start from
     *  this path
     * @return bool TRUE if directory was created successfully or already exists,
     *  otherwise FALSE
     * @see mkdir()
     * @static
     */
    function mkdir($pathname, $mode = 0777, $recursive = true, $rec_from = '')
    {
        $pathname = str_replace('\\', '/', $pathname);
        $pathname = preg_replace('|/{2,}|', '/', $pathname);

        if ($recursive) {
            $dirs = explode('/', $pathname);
            $path = '';
            foreach ($dirs as $dir) {
                // empty dir is actually root dir
                if ("" == $dir) {
                    $path = '/';
                    continue;
                }

                // append dir to path
                $path .= ('/' == $path || '' == $path) ? $dir : "/$dir";

                // use realpath
//                if ($temp_path = realpath($path)) {
//                    $path = $temp_path;
//                }

                if (!$rec_from || 0 === strncmp($path, $rec_from, strlen($rec_from))) {
                    if (file_exists($path)) {
                        continue;
                    }

                    if (!mkdir($path, $mode)) {
                        return false;
                    }
                }
            }

        } else if (!file_exists($pathname)) {
            return mkdir($pathname, $mode);
        }

        return true;
    }

    /**
     * Delete a file, or a folder and its contents
     *
     * @param string $path path to directory or file to delete
     * @return bool TRUE if all files/directories was removed otherwise FALSE
     * @static
     */
    function rmr($path)
    {
        $success = true;

        // if it's file try to remove it
        if (is_file($path) || is_link($path)) {
            $success = @unlink($path);
        } else if (false !== $dir = @dir($path)) {
            while (false !== ($entry = $dir->read())) {
                if ('.' != $entry && '..' != $entry) {
                    $removed = (is_file($path) || is_link($path)) ? @unlink($path . DIRECTORY_SEPARATOR
                        . $entry) : @FileSystem::rmr($path . DIRECTORY_SEPARATOR . $entry);
                    $success = $removed ? $success : false;
                }
            }

            // remove directory itself
            $removed = @rmdir($path);
            $success = $removed ? $success : false;
        }

        if (!$success) trigger_error(sprintf('Couldn\'t remove directory: %s', $path), E_USER_WARNING);
        return $success;
    }

    /**
     * Safe rename function
     *
     * At first this function will try to use PHP's internal rename() implementation
     * and if it fails than copy() and unlink() will be used.
     *
     * @static
     * @param string $oldname
     * @param string $newname
     * @return bool
     */
    function rename($oldname, $newname)
    {
        $er = error_reporting(0);
        $result = true;
        if (!rename($oldname, $newname)) {
            if (copy($oldname, $newname)) {
                unlink($oldname);
            } else {
                $result = false;
            }
        }

        error_reporting($er);
        return $result;
    }


    /**
     * Filters folder name by replacing forbidden characters for it.
     *
     * @param string $name
     * @return string
     */
    function filterFolderName($name){
        $new_name = preg_replace("/ /", "_", preg_replace("/[ ]{2,}/", " ", $name));
        $new_name = ereg_replace("[^[:space:]a-zA-Z0-9*_-]", "", $new_name);
        return $new_name;
    }
}
