<?php
/**
 * Class Resource.
 * @version $Revision: 331 $
 */
require_once(SITE_PATH . '/class/Color.php');

/**
 * Main class for manipulating resource image.
 * It allows to fill background with transparent color.
 *
 * @todo - finish function 'applyFilter'.
 * @todo - create function for merging two or more image resources into one.
 * @author Priit Pold.
 */
class Image_Resource{

    /**
     * Flag show, that compatibility check was done or not.
     *
     * @var bool
     * @access private
     */
    var $_compatibility_checked = false;

    /**
     * Flag shows, if it is possible to use GD stuff with
     *  current version of PHP.
     * @var bool
     * @access private
     */
    var $_compatible = false;

    /**
     * Define function for image creation.
     * @var string
     * @access private
     */
    var $_imagecreate = 'imagecreate';

    /**
     * Define function for image resizing.
     * @var string
     * @access private
     */
    var $_imageresize = 'imagecopyresized';

    /**
     * Define function for image allocation.
     * @var string
     * @access private
     */
    var $_imagecolorallocate = 'imagecolorallocate';

    /**
     * System flag shows, if it is possible to use transparency.
     *
     * @var bool
     * @access private
     */
    var $_transparency_enabled = false;

    /**
     * Flag shows, if current resource is transparent or not.
     * @var bool
     */
    var $transparent   = false;

    /**
     * Transparency level.
     * 0 - is not transparent
     * 127 - fully transparent
     *
     * @var int
     */
    var $transparency  = 0;

    /**
     * Default background color in HEX mode.
     * @var string
     */
    var $default_bg_color = '#FFFFFF';

    /**
     * Canvas width.
     * @var int.
     */
    var $canvas_width;
    /**
     * Canvas height.
     * @var int
     */
    var $canvas_height;

    /**
     * Main resource on which all manipulations will accure.
     * @var resource
     * @access private
     */
    var $_resource;

    var $color_alloc=array();

//    var $imagecopyresized;
//    var $getimagesize;

    /**
     * Main constructor.
     * Set all variables to default.
     *
     * @return Image_Resource
     */
    function Image_Resource()
    {

        $this->_imagecreate = 'imagecreate';
        $this->_imageresize = 'imagecopyresized';

        $this->_compatibility_checked   = false;
        $this->_compatible              = false;
        $this->_transparency_enabled    = false;
        $this->transparency             = 0;
        $this->transparent              = false;

        $this->_resource                 = null;

        $this->default_bg_color         = '#FFFFFF';

        // chooce, which function to use for resource generation
        if (function_exists('imagecreatetruecolor')){
            $this->_imagecreate = 'imagecreatetruecolor';
        }

        if (function_exists('imagecopyresampled')){
            $this->_imageresize = 'imagecopyresampled';
        }

        if (function_exists('imagecolorallocatealpha')){
            $this->_imagecolorallocate = 'imagecolorallocatealpha';
            $this->_transparency_enabled = true;
        }
    }

    function getValidTransparencyLevel($transparency=null){
        // check transparency level
        if (is_null($transparency) || $transparency < 0 || $transparency > 127){
            $transparency = 0;
        }else{
            $transparency = (int)$transparency;
        }
        return $transparency;
    }


    /**
     * Create image resource.
     *
     * @param int $width - new resource width
     * @param int $height - new resource height
     * @param strin $bg_color - background color in HEX mode.
     * @param int $transparency - transparency leve between 0 and 127.
     * @return resource - new resource.
     */
    function createResource($width, $height, $bg_color = null, $transparency = null)
    {
        $transparency = $this->getValidTransparencyLevel($transparency);

        // generate RGB array from given color in HEX mode.
        $rgb = Color::string2Rgb($bg_color);
        if ( !$rgb ){
            $rgb = array('r'=>rand(0,255), 'g'=>rand(0,255), 'b'=>rand(0,255));
        }

        // create image resource
        $this->_resource = call_user_func($this->_imagecreate, (int)$width, (int)$height);

        $this->canvas_height = (int)$height;
        $this->canvas_width = (int)$width;

        // allocate color for resource.
        $bg_color = &$this->colorAllocate($rgb, $transparency);

        // if image is transparent, set it here, depending on system.
        if ($this->_transparency_enabled)
        {
            if (function_exists('imagesavealpha')){
                imagesavealpha( $this->_resource,true);
                imagealphablending($this->_resource,true);
            }
        }
        imagefill($this->_resource, 0, 0, $bg_color);

        return $this->_resource;
    }

    /**
     * Create image resource from image.
     * Use image file from options 'image_as_bg'.
     *
     * @param int $width - image resource width
     * @param int $height - image resource height
     * @param string $image - image full file path
     * @return resource|FALSE - on success returns image resource, otherwise FALSE
     */
    function createFromImage($width, $height, $image = null)
    {
        // check, if given image file exists in file system and is readable.
        if (!file_exists($image)){
            return false;
        }
        // get image info.
        $image_info = getimagesize($image);

        // chooce function for image creation according to image type.
        switch($image_info[2]){
            case IMAGETYPE_TIFF_II:
            case IMAGETYPE_TIFF_MM:
                $createfrom = 'imagecreatefromjpeg';
                break;

            case IMG_JPG:
            case IMG_JPEG:
            case IMAGETYPE_JPEG:
                $createfrom = 'imagecreatefromjpeg';
                break;

            case IMG_PNG:
            case IMAGETYPE_PNG:
                $createfrom = 'imagecreatefrompng';
                break;

            case IMG_GIF:
            case IMAGETYPE_GIF:
                $createfrom = 'imagecreatefromgif';
                break;

            default: return false;
        }

        // try to create resource from image file.
        $bg_resource = call_user_func($createfrom, $image);

        // if creation not succeeded, set last error and return FALSE
        if (!$bg_resource){
            return false;
        }

        // create resource image with given width and height.
        $tmp_res = call_user_func_array($this->_imagecreate, array($width, $height));

        // set source image box to resize.
        $sx=0; $sy=0;
        if ($image_info[0] > $width*2){
            $sx = rand(0, $image_info[0] - $width);
        }
        if ($image_info[1] > $height*2){
            $sy = rand(0, $image_info[1] - $height);
        }

        $resize_res = call_user_func_array($this->_imageresize, array(
            $tmp_res, $bg_resource, 0, 0, $sx, $sy, $width, $height, $width, $height
        ));
        // if resize not succeeded, return false.
        if ($resize_res !== false){
            $bg_resource = $tmp_res;
        }else{
            imagedestroy($tmp_res);
            return false;
        }
        imagedestroy($tmp_res);
        $this->setResource($bg_resource);
        return $bg_resource;
    }

    /**
     * Apply filter to given resource.
     * Checks, if given filter exists or not, and if exists, applies it,
     *  otherwise returns given resource without changes.
     *
     * @todo - implement this function to allow applying some filters.
     * @param resource $resource - resource, on which you want to apply thouse filters.
     * @param mixed $filter_name - filter name as string, or array as set of filter names.
     * @return resource - return resource.
     */
    function applyFilter(&$resource, $filter_name = '')
    {
//        require_once( dirname(__FILE__) . 'Filter.php' );
        if ( Filter::filterExists($filter) ){
            echo 'ok';
        }
    }

    /**
     * Allocate color for image resource.
     *
     * @param resource $resource - image resource, for which you want to allocate color.
     * @param array $rgb - rgb colro representation. Must be array: array('r'=>127,'g'=>127,'b'=>127)
     * @param int $alpha - transparency level, integer between 0 and 127.
     * @return int - image color allocator for current resource.
     */
    function &colorAllocate($rgb , $alpha = null)
    {
        $int = count($this->color_alloc);

        if ($this->_transparency_enabled){
            if (is_null($alpha) || $alpha < 0 || $alpha > 127){
                $alpha = 0;
            }else{
                $alpha = (int)$alpha;
            }
            $this->color_alloc[$int] = call_user_func_array($this->_imagecolorallocate,
                array($this->_resource, $rgb['r'], $rgb['g'], $rgb['b'], $alpha));

            if ($alpha > 0){
                $this->color_alloc[$int] = imagecolortransparent($this->_resource, $this->color_alloc[$int]);
            }
        }else{
            $this->color_alloc[$int] = call_user_func_array($this->_imagecolorallocate, array(
                $this->_resource, $rgb['r'], $rgb['g'], $rgb['b']
            ));
        }
        return $this->color_alloc[$int];
    }

    /**
     * Checks compatibility with server.
     * Checks, if you can use this feature on your server.
     *
     * @return bool - TRUE if compatible, otherwise FALSE.
     */
    function checkCompatibility()
    {
        if ($this->_compatibility_checked) return $this->_compatible;

        $this->_compatible = false;
        $this->_compatibility_checked = true;

        $funcs = array(
            'imagecreate','imagecopyresized','getimagesize'//, 'gd_info' //,'imagettfbbox'
        );
        foreach ($funcs as $func) {
            if (!function_exists($func)) return false;
        }
        $this->_compatible = true;
        return true;
    }

    /**
     * Get dimensions of given resource.
     *
     * @return array - dimensions (width,height)
     */
    function getCanvasDimensions(){
        return array($this->canvas_width,$this->canvas_height);
    }

    /**
     * Set resource for current file.
     *
     * @param resource $resource
     */
    function setResource(&$resource)
    {
        if (is_resource($resource)) $this->_resource = &$resource;
    }

    /**
     * Get current image resource.
     * @return resource.
     */
    function &getResource()
    {
        return $this->_resource;
    }

    /**
     * Generate image file.
     *
     * According to image type and file name
     *  it will output image to output device, or save it in to the file.
     *
     * @todo - implement image storing into file.
     *
     * @param resource - resource which to ouput.
     * @param int $image_type - image type to generate
     * @param string $file - full file path fot stroing image output.
     * @return binary - outputs image
     */
    function outputImage($img_res , $image_type = IMG_PNG, $file=nuill)
    {
        if (!is_resource($img_res)) return false;
        switch($image_type){
            case IMAGETYPE_BMP:
            case IMAGETYPE_JPEG:
            case IMAGETYPE_JPEG2000:
            case IMG_JPEG:
            case IMG_JPG:
                header("Content-Type: image/jpeg");
                imagejpeg($img_res);
                break;
            case IMAGETYPE_PNG:
            case IMG_PNG:
                header("Content-Type: image/png");
                imagepng($img_res);
                break;
            case IMAGETYPE_GIF:
            case IMG_GIF:
                header("Content-Type: image/gif");
                imagegif($img_res);
                break;
            default:
                return false;
        }
        imagedestroy($img_res);
    }
}