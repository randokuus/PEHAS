<?php
/**
 * Html tag generator.
 *
 * @version $Revision: 295 $
 */

/**
 * List of static functions for generating Html tags
 *
 * @author Priit Pyld <priit.pold@modera.net>
 * @static
 */
class HTMLTags{

    /**
     * Returns array of tags which requires end tag.
     *
     * @return array - array of tags wich must be closed
     */
    function requiredEndTag()
    {
        return array(
            'a', 'abbr', 'acronym', 'address', 'applet', 'b', 'bdo', 'big'
            , 'blockquote', 'body', 'button', 'caption', 'center', 'cite', 'code'
            , 'colgroup', 'dd', 'del', 'dfn', 'dir', 'div', 'dl', 'dt', 'em'
            , 'fieldset', 'font', 'form', 'frameset', 'h1', 'h2', 'h3', 'h4', 'h5'
            , 'h6', 'head', 'html', 'i', 'iframe', 'ins', 'kbd', 'label', 'legend'
            , 'li', 'map', 'menu', 'noframes', 'noscript', 'object', 'ol'
            , 'optgroup', 'option', 'p', 'pre', 'q', 's', 'samp', 'script'
            , 'select', 'small', 'span', 'strike', 'strong', 'style', 'sub', 'sup'
            , 'table', 'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'title'
            , 'tr', 'tt', 'u', 'ul', 'var'
        );
    }

    /**
     * Generate string of attributes fot tag
     *
     * @param array $attrs - assoc array with attributes.
     * @return string - returns string representation of attributes
     */
    function array2attrs( $attrs = array() )
    {
        if( !is_array( $attrs ) ) return '';
        $str = '';
        foreach ( $attrs as $name=>$value ) $str.= " ".$name.'="'.$value.'"';
        return $str;
    }

    /**
     * Main function for tag generation
     *
     * Generate selected tag with attributes given in array
     *  and returns formated string.
     *
     * @param string $tag_name - tag name
     * @param string $content - text between start and end tags.
     * @param array $attrs - array of attributes for this tag
     * @return string - formated tag string
     */
    function createTag( $tag_name = '' , $content = '' , $attrs = array() )
    {
        if( !strlen($tag_name) ) return '';
        $tag = "<$tag_name".HTMLTags::array2attrs($attrs);
        if( in_array( $tag_name , HTMLTags::requiredEndTag() ) )
            $tag.=">".(strlen($content)?$content:'')."</$tag_name>";
        else
            $tag.=" />".(strlen($content)?$content:'');
        return $tag;
    }

    /**
     * Function for options list generation.
     *
     * Create list of options from data array and returns formated string
     *
     * @param array $data - assoc array of options _VALUE_ => _TEXT_
     * @param string $selected - index of _SELECTED_ option. must be equal with value
     * @return string - returns formated string of options list
     *   Example: <option value="_VALUE_" _SELECTED_ >_TEXT_</option>...
     */
    function createOptions( $data , $selected = null )
    {
        if( !is_array( $data ) ) return '';
        $str = '';
        foreach ( $data as $value=>$lang ){
            $attrs = array('value'=>$value);
            if( $selected == $value ) $attrs['selected']='selected';
            $str .= HTMLTags::createTag( 'option' , $lang , $attrs );
        }
        return $str;
    }
}

