<?php
/**
 * Color class for color manipulations.
 * @version $Revision: 332 $
 *
 */
define("IMAGE_TEXT_REGEX_HTMLCOLOR", "/^[#|]([a-f0-9]{2})?([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{1,2})$/i", true);

/**
 * Class with static functions for dealing with colors.
 * It can conver string to rgb representation or backward.
 * Also allows to make some color darker or lighter.
 *
 * @author Priit Pold <priit.pold@modera.net>
 * @static
 */
class Color{

    /**
     * Convert HEX color representation to RGB array.
     *
     * @param string $scolor - HEX representation of color: #FF00FF.
     * @return array|FALSE - returns array representation of this color in RGB mode,
     *  or FALSE if not succeeded.
     */
    function string2Rgb($scolor){
        if (preg_match(IMAGE_TEXT_REGEX_HTMLCOLOR, $scolor, $matches)) {
            return array(
                           'r' => hexdec($matches[2]),
                           'g' => hexdec($matches[3]),
                           'b' => hexdec($matches[4]),
                           'a' => hexdec(!empty($matches[1])?$matches[1]:0),
                           );
        }
        return false;
    }

    /**
     * Converts RGB array into HEX.
     *
     * @param array $color - RGB array.
     * @return string|FALSE - returns HEX || FALSE.
     */
    function rgb2Hex( $color ){
        if (!is_array($color)){
            return false;
        }
        if (!isset($color['r']) || !isset($color['g']) || !isset($color['b']))
            return false;

        if (   $color['r'] >= 0 && $color['r']<256
            && $color['g'] >= 0 && $color['g']<256
            && $color['b'] >= 0 && $color['b']<256
        ){
            return '#'
                . str_pad(dechex($color['r']), 2, '0', STR_PAD_LEFT)
                . str_pad(dechex($color['g']), 2, '0', STR_PAD_LEFT)
                . str_pad(dechex($color['b']), 2, '0', STR_PAD_LEFT);
        }
        return false;
    }

    /**
     * Makes color darker
     *
     * @param mixed $color -
     *      array: RGB representation of color array('r'=>255,'g'=>0,'b'=>0,'a'=>0)<br />
     *      string - string representation of color: #ff0000.<br />
     * @param int $percent - make darker in persentage
     * @param string $mode - return mode: 'rgb'|'hex'
     * @return array|string|FALSE - depending on return mode.
     */
    function darker($color,$percent = 5 , $mode = 'rgb'){

        if (!is_array($color)) {
            $color = Color::string2Rgb($color);
        }
        if (is_array($color)) {
            foreach($color as $c=>$v){
                $color[$c] = ceil($v+$v*($percent/100));
                if ($color[$c] >255) $color[$c] = 255;
            }
            return $color;
        }
        if (!$color) return false;
        switch($mode){
            case 'hex': return Color::rgb2Hex($color);
            case 'rgb': return $color;
        }
    }

    /**
     * Makes color lighter
     *
     * @param mixed $color -
     *      array: RGB representation of color array('r'=>255,'g'=>0,'b'=>0,'a'=>0)<br />
     *      string - string representation of color: #ff0000.<br />
     * @param int $percent - make lighter in persentage
     * @param string $mode - return mode: 'rgb'|'hex'
     * @return array|string - depending on return mode.
     */
    function lighter($color , $percent = 5 , $mode = 'rgb'){
        if (!is_array($color)) {
            $color = Color::string2Rgb($color);
        }
        if (is_array($color)) {
            foreach($color as $c=>$v){
                $color[$c] = ceil($v-$v*($percent/100));
                if ($color[$c] < 0) $color[$c] = 0;
            }
            return $color;
        }
        if (!$color) return false;
        switch($mode){
            case 'hex': return Color::rgb2Hex($color);
            case 'rgb': return $color;
        }
    }

    /**
     * Generate random color between 2 colors.
     * Randomly create color between 2 defined colors.
     *
     * @param string $color1 - first color
     * @param string $color2 - second color
     * @param string $type - return mode, one from: 'rgb','hex'
     * @return mixed - depending on return mode, returns rgb array or hex string.
     */
    function randomColorFromRange($color1,$color2, $type = 'rgb'){
        $rgb1 = Color::string2Rgb($color1);
        $rgb2 = Color::string2Rgb($color2);
        if (!$rgb1 || !$rgb2) {
            return false;
        }
        $rgb3['r'] = rand(min($rgb1['r'],$rgb2['r']), max($rgb1['r'],$rgb2['r']));
        $rgb3['g'] = rand(min($rgb1['g'],$rgb2['g']), max($rgb1['g'],$rgb2['g']));
        $rgb3['b'] = rand(min($rgb1['b'],$rgb2['b']), max($rgb1['b'],$rgb2['b']));
        switch($type){
            case 'hex': return Color::rgb2Hex($rgb3);break;
            case 'rgb':
            default:
                return $rgb3;
        }
    }

    /**
     * Count the avarage number.
     *
     * @param int $int1 - int 1
     * @param int $int2 - int 2
     * @return int - average integer.
     */
    function average($int1 , $int2){
        return round( ((int)$int1 + (int)$int2) / 2) ;
    }

    /**
     * Returns HEX color representation of given color name.
     *
     * Have some small vocabulary.
     * @link http://en.wikipedia.org/wiki/Web_colors
     * @param string $name - name of the color.
     * @return string - HEX representation of given color name.
     *
     */
    function text2Color($name = ''){
        $name = strtolower($name);
        $colors = array(
            'red'       => '#ff0000',
            'green'     => '#008000',
            'blue'      => '#0000ff',
            'aqua'      => '#00ffff',
            'black'     => '#000000',
            'fuchsia'   => '#ff00ff',
            'gray'      => '#808080',
            'lime'      => '#00ff00',
            'maroon'    => '#800000',
            'navy'      => '#000080',
            'olive'     => '#808000',
            'purple'    => '#800080',
            'silver'    => '#c0c0c0',
            'teal'      => '#008080',
            'white'     => '#ffffff',
            'yellow'    => '#ffff00',
            // RED COLORS
            'indianred' => '#cd5c5c',
            'lightcoral'=> '#f08080',
            'darkred'   => '#8b0000',
            'salmon'    => '#fa8072',
            'darksalmon'=> '#e9967a',
            'lightsalmon'=>'#ffa07a',
            'crimson'   => '#dc143c',
            'firebrick' => '#b22222',
        );

        if (!strlen($name)) return $colors;
        if (isset($colors[$name])) return $colors[$name];
        return $colors['white'];
    }

}