<?php
/**
 * Grid filter.
 * @version $Revision: 331 $
 */

/**
 * Grid filter, which will fill resource with grid lines.
 * Some options are available(all are integers):
 *   You can define default values for both lines:
 *      count: count of vertical lines.
 *      thickness: thickness of vertical and horizontal lines.
 *      space: space between vertical lines.
 *      angle: angle to rotate grid.
 *   Or define for vertical and horizontal lines separately:
 *      vl_count: count of vertical lines.
 *      hl_count: count of horizontal lines.
 *      vl_thickness: thickness of vertical lines.
 *      hl_thickness: thickness of horizontal lines.
 *      vl_space: space between vertical lines.
 *      hl_space: space between horizontal lines.
 *   Color options:
 *      transparency: - trapsparency level of all lines.
 *      color: - line color in RGB representation as array('r'=>123,'g'=>123,'b'=>123).
 *
 * If space is not set, it will be by default 10.
 * If lines count is not set, it determines to set by: image x|y / space
 * If color is not set, it will be by default = black:#000000:array('r'=>0,'g'=>0,'b'=>0)
 *
 * But if you have set space and count, then space will be overwriteb by value:
 *      image x|Y / lines count
 *
 * @author Priit Pold <priit.pold@modera.net>
 *
 */
class Image_Filter_Grid extends Image_Filter {

    /**
     * Grid filtr.
     *
     * @param Image_Resource $resource - Image_Resource object.
     * @param array $options - array of options
     * @return FALSE - return FALSE, if given variable $resource
     *      is not an Image_Resource object
     */
    function applyFilterOnResource(&$resource, $options = array()){

        // check, if given $resource variable is Image_Resource object.
        // if not, returns false.
        if (!is_object($resource)) return false;

        $dim = $resource->getCanvasDimensions();

        $count          = (int)Image_Filter::getOption($options, 'count', 0);
        $thickness      = (int)Image_Filter::getOption($options, 'thickness', 0);
        $space          = (int)Image_Filter::getOption($options, 'space', 10);
        $angle          = (int)Image_Filter::getOption($options, 'angle', 0)%90;

        $vl_count       = (int)Image_Filter::getOption($options, 'vl_count', $count);
        $hl_count       = (int)Image_Filter::getOption($options, 'hl_count', $count);
        $vl_thickness   = (int)Image_Filter::getOption($options, 'vl_thickness',$thickness);
        $hl_thickness   = (int)Image_Filter::getOption($options, 'hl_thickness',$thickness);
        $vl_space       = (int)Image_Filter::getOption($options, 'vl_space', $space);
        $hl_space       = (int)Image_Filter::getOption($options, 'hl_space', $space);
        $vl_angle       = (int)Image_Filter::getOption($options, 'vl_angle', $angle)%90;
        $hl_angle       = (int)Image_Filter::getOption($options, 'hl_angle', $angle)%90;
        $transparency   = (int)Image_Filter::getOption($options, 'transparency', 0);
        $color          = Image_Filter::getOption($options, 'color');
        $random_color  = false;

        if (!is_array($color)){
            $color = Color::string2rgb($color);
            if (!$color) $color = null;
        }elseif (!isset($color['r']) || !isset($color['g']) || !isset($color['b'])){
                $color = null;
        }elseif(is_null($color)){
            $random_color = true;
        }

        if (is_null($color)){ $color = array('r'=>0, 'g'=>0, 'b'=>0); }

        if ($vl_count == 0){
            $vl_count = $dim[0] / $vl_space;
        }else{
            $vl_space = $dim[0] / $vl_count;
        }

        if ($hl_count == 0){
            $hl_count = $dim[1] / $hl_space;
        }else{
            $hl_space = $dim[1] / $hl_count;
        }


        $color    = $resource->colorAllocate($color, $transparency);
        $res      = &$resource->getResource();

        /**
         * Draw vertical lines.
         */
        if ($vl_angle > 0){
            $_v = $dim[1]*tan(deg2rad($vl_angle));
            $vl_count = $dim[0]+$_v / $vl_space;
        }
        for($i=1; $i<=$vl_count; $i++){
            $posx = $i*$vl_space;
            if ($vl_angle > 0){
                imagefilledpolygon($res,array(
                        $posx, 0,
                        $posx+$vl_thickness, 0,
                        $posx+$vl_thickness-$_v, $dim[1],
                        $posx-$_v,$dim[1] ), 4 ,$color);
            }else{
                imagefilledrectangle($res, $posx, 0, $posx+$vl_thickness, $dim[1],$color);
            }
        }

        /**
         * Draw horizontal lines.
         */
        if ($hl_angle > 0){
            $_h = $dim[0]*tan(deg2rad($hl_angle));
            $hl_count = $dim[1]+$_h / $hl_space;
        }
        for($i=1; $i<=$hl_count; $i++){
            $posy = $i*$hl_space;
            if ($hl_angle > 0){
                imagefilledpolygon($res,array(
                        0, $posy - $_h,
                        $dim[0], $posy,
                        $dim[0], $posy+$hl_thickness,
                        0, $posy + $hl_thickness - $_h ), 4 ,$color);
            }else{
                imagefilledrectangle($res, 0, $posy, $dim[0], $posy+$hl_thickness,$color);
            }
        }
    }

}