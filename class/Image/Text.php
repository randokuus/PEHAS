<?php
/**
 * Class for drawing text on resource.
 * @version $Revision: 331 $
 */
require_once(SITE_PATH . '/class/FactoryPattern.php');
require_once(SITE_PATH . '/class/Image/Resource.php');

/**
 * Class for drawing some text onto resource image.
 * It allowes to use TTF fonts: Free Type must be installed and GD2.
 * If it's not available to use TTF, it can draw simple text.
 *
 * @author Priit Pold <priit.pold@modera.net>
 *
 * @todo : add new option: align  - horizontal aligment (left|right|bottom).
 * @todo : add new option: valign - vertical aligment (top|middle|bottom)
 * @todo : improve text positioning.
 */
class Image_Text extends Image_Resource{

    /**
     * Array of options for Text drawing.
     * @var array
     */
    var $options = array(
        'font_spacing'  => 2,
        'font_size'     => 15,
        'font_size_range'=> 0,
        'font_transparency'=> 0,
        'canvas_height' => 10,

        // TTF fonts
        'font_path'     => '',
        'fonts'         => array(),

        // angle for rotating text characters.
        'angle'         => 0,
        'max_vertical_diff'=>0,

        // text color
        'color'         => '#ff00ff',
        // use text shadows
        'font_shadows'  => false,
        'font_shadow_transparency'=> 127,
    );

    /**
     * Flag shows is TTF is available or not.
     * @var bool
     * @access private
     */
    var $_ttf_enabled = false;

    /**
     * Flag shows that TTF availability check was done or not.
     * @var bool
     * @access private
     */
    var $_ttf_checked = false;



    /**
     * Main constructor.
     * It run TTF font availability.
     * @return Image_Text
     */
    function Image_Text()
    {
        $this->isTtfEnabled();
        parent::Image_Resource();
    }

    /**
     * Draw text onto image resource.
     *
     * @param Image_Resource - Image_Resource object.
     * @param string $text - text to draw.
     * @return resource - image resource.
     */
    function drawText(&$resource, $text='')
    {
        if (!is_resource($resource->getResource())){
            trigger_error('given variable is not an image resource.' . __LINE__, E_ALL);
            return false;
        }
        // check font existence.
        $this->checkFontExistence();

        // loop through and write out selected number of characters
        $code_length = strlen($text);

        $canvas_width  = imagesx($resource->getResource());
        $canvas_height = imagesy($resource->getResource());
        $this->setOption('canvas_height',$canvas_height);

        $posX = (($code_length) * ($this->options['font_size'] + $this->options['font_spacing']))
            / ( $code_length ) - $this->options['font_size'];

        $rgb_orig = Color::string2Rgb($this->options['color']);
        $font_colors = array();

        for ($i = 0; $i < $code_length; $i++) {
            // select color for character.
            if ( $rgb_orig!==false ) {
                $rgb = Color::darker($rgb_orig, rand(0,200),'rgb');

                // shadow colour
                if ($this->options['font_shadows']) {
                    $shadow_rgb = Color::darker($rgb_orig, rand(100,1300),'rgb');
                }
            } else {
                $rgb = array('r'=>rand(20,100),'g'=>rand(20,100), 'b'=>rand(20,100));
                // shadow colour
                if ($this->options['font_shadows']) {
                    $shadow_rgb = array('r'=>rand(60,127),'g'=>rand(60,127), 'b'=>rand(60,127));
                }
            }

            // set text color.
            $chars_color[$i] = $resource->colorAllocate($rgb, $this->options['font_transparency']);

            // set shadow color.
            if ($this->options['font_shadows']) {
                $shadow_colour = $resource->colorAllocate($rgb, $this->options['font_shadow_transparency']);
            }

            // select random font size
            $font_size = rand( $this->options['font_size']-$this->options['font_size_range'],
                $this->options['font_size']+$this->options['font_size_range']);

            // calculate character starting coordinates
            $posX = $posX + $this->options['font_spacing'] + $font_size;
            $posY = ($canvas_height-$font_size)/2+$font_size;


            // select random angle
            $angle = rand(-$this->options['angle'], $this->options['angle']);

            // set font.
            $sCurrentFont = null;
            if ($this->_ttf_enabled){
                $sCurrentFont = $this->options['font_path'] . DIRECTORY_SEPARATOR
                     . $this->options['fonts'][array_rand($this->options['fonts'])];
            }

            // draw current character onto resource
            $this->drawString($resource->getResource(), $font_size, $angle ,
                $posX, $posY, $text[$i], $chars_color[$i], $sCurrentFont);

            // draw shadow onto resource.
            if ($this->options['font_shadows']) {
                $iRandOffsetX = rand(-5, 5);
                $iRandOffsetY = rand(-5, 5);
                $iOffsetAngle = rand(-$this->options['angle'], $this->options['angle']);
                $this->drawString($resource->getResource(), $font_size, $iOffsetAngle,
                    $posX+$iRandOffsetX, $posY+$iRandOffsetY, $text[$i], $shadow_colour, $sCurrentFont);
            }
        }

        return $resource;
    }

    /**
     * Draw string onto image resource.
     *
     * @param resource $resource - image resource
     * @param int $font_size - font size
     * @param int $posX - position X
     * @param int $posY - position Y
     * @param string $text - text to draw
     * @param int $color - allocated color.
     */
    function drawString(&$resource, $font_size, $angle, $posX, $posY, $text, $color, $font=null)
    {
        // TTF is enabled and given font file exists, then draw character using this TTF
        // otherwise draw simple text.
        if ($this->_ttf_enabled && file_exists($font)){
            imagefttext($resource, $font_size, $angle, $posX, $posY,
                    $color, $font, $text);
        }else{
            imagestring($resource, 5, $posX, $posY-25, $text, $color);
        }
    }

    /**
     * Set font path.
     *
     * @param string $path - font path.
     * @return bool - returns TRUE if path exists, otherwise FALSE.
     */
    function setFontPath($path)
    {
        if (!file_exists($path)){
            return false;
        }
        $this->options['font_path'] = $path;
//        $this->checkFontExistence();
        return true;
    }

    /**
     * Set fonts to use.
     * @param array - fonts array, contains font file names to use.
     */
    function setFonts($fonts)
    {
        $this->options['fonts'] = $fonts;
//        $this->checkFontExistence();
    }

    /**
     * Check, if it is possible to use TTF fonts.
     * @return bool - returns TRUE, if allowed, otherwise FALSE.
     */
    function isTtfEnabled()
    {
        // FreeType Support
        $funcs = array( 'imagefttext','imagettfbbox','gd_info');
        $this->_ttf_checked = true;

        foreach ($funcs as $func)
            if (!function_exists($func))
                return $this->_ttf_enabled = false;

        return $this->_ttf_enabled = true;
    }

    /**
     * Check, if fonts exists in you system or not.
     * If you want to make CAPTCHA work, fonts must be set properly
     *
     * @return bool - if font are set normally then TRUE, otherwise FALSE.
     */
    function checkFontExistence()
    {
        if (!file_exists($this->options['font_path'])
            || !is_readable($this->options['font_path']))
        {
            return $this->_ttf_enabled = false;
        }

        // check if fonts array exists.
        if (!isset($this->options['fonts']) || !is_array($this->options['fonts'])){
            return $this->_ttf_enabled = false;
        }

        // check every font file, and if some is missing, remove it from fonts array
        foreach ($this->options['fonts'] as $font){
            $font_file = $this->options['font_path'] . DIRECTORY_SEPARATOR . $font;

            if (!file_exists($font_file)){
                $key = array_search($font,$this->options['fonts']);
                unset($this->options['fonts'][$key]);
            }
        }

        // finnaly, check, if there is some fonts in array.
        if (!count($this->options['fonts'])){
            return $this->_ttf_enabled = false;
        }
        return $this->_ttf_enabled = true;
    }

    /**
     * Set option for text drawing.
     * Will set given value to given key, if this key exists in options array.
     *
     * @param string $key - option key
     * @param mixed $val - value for given option key
     */
    function setOption($key,$val)
    {
        if (isset($this->options[$key])) $this->options[$key] = $val;
    }

    /**
     * Set options from array.
     * Will set all options in given array using setOption function.
     *
     * @param array $options - array of options.
     */
    function setOptions($options)
    {
        if (!is_array($options)) return;
        foreach ($options as $key=>$val) $this->setOption($key,$val);
    }
}