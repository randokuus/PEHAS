<?php
/**
 * @version $Revision: 286 $
 */

/**
 * Modera imagemagick wrapper
 *
 * @author Priit Pyld <priit.pold@modera.net>
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class Imagemagick
{
    /**
     * Paths to different imagemagick utilities
     *
     * @var array
     * @access protected
     */
    var $_paths;

    /**
     * @global constant IMAGE_CONVERT
     * @return Imagemagick
     */
    function Imagemagick()
    {
        if (!defined('IMAGE_CONVERT')) {
            trigger_error('Constant IMAGE_CONVERT is not defined in config.php'
                , E_USER_ERROR);
        }

        $this->_paths = array(
            'convert' => IMAGE_CONVERT,
        );
    }

    /**
     * Create thumbnail image
     *
     * @param string $source path to source file
     * @param string $destination path to destination thumbnail path
     * @param string $size thumbnail dimensions (100x100, 120x80, etc)
     * @return bool TRUE if thumbnail was created successfully, FALSE otherwise
     */
    function thumbnail($source, $destination, $size)
    {
        $_dummy = null;
        $return_val = null;

        $source = trim(escapeshellarg($source), "'");
        $destination = trim(escapeshellarg($destination), "'");
        $size = trim(escapeshellarg($size), "'");

        // Modera default server has very old version of imagemagic that do not
        // support -thumbnail option, unfortunately...
//        exec("{$this->_paths['convert']} -thumbnail $size $source $destination"
        exec("{$this->_paths['convert']} -resize $size -comment \"\" $source $destination"
            , $_dummy, $return_val);

        return !$return_val;
    }

    /**
     * Resize image
     *
     * @param string $source path to source file
     * @param string $destination path to destination thumbnail path
     * @param string $size thumbnail dimensions (100x100, 120x80, etc)
     * @return bool TRUE if resized image was created successfully, FALSE otherwise
     */
    function resize($source, $destination, $size)
    {
        $_dummy = null;
        $return_val = null;

        $source = trim(escapeshellarg($source), "'");
        $destination = trim(escapeshellarg($destination), "'");
        $size = trim(escapeshellarg($size), "'");

        exec("{$this->_paths['convert']} -resize $size $source $destination"
            , $_dummy, $return_val);

        return !$return_val;
    }
}
