<?php
/**
 * @version $Revision: 257 $
 */

/**
 * Static string methods
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 * @static
 */
class Strings
{
    ///////////////////////////////////////////////////////////////////////////
    // Private
    ///////////////////////////////////////////////////////////////////////////

    /**#@+
     * @access private
     */

    /**
     * Return array with word devider symbols
     *
     * @return array
     */
    function _word_deviders()
    {
        return array(' ', "\t", "\r", "\n", ';', ':', '.', ',');
    }

    /**
     * Return TRUE if char is devider symbol, FALSE otherwise
     *
     * @param strng $char
     * @return bool
     */
    function _is_devider($char)
    {
        return in_array($char, Strings::_word_deviders());
    }

    /**#@-*/

    ///////////////////////////////////////////////////////////////////////////
    // Public
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Cut string to specified width and appends custom string at the end
     *
     * @param string $str
     * @param int $length
     * @param string $append
     * @return string
     */
    function shorten($str, $length, $append = '...')
    {
        return (strlen($str) > $length) ? substr($str, 0, $length) . $append : $str;
    }

    /**
     * Shorten string without cutting words
     *
     * If last word cannot fit in specified length, than bounds will be inscreased to fit
     * this word
     *
     * @param string $str
     * @param int $length maximum length in bytes, actually result string might be loner
     * @param string $append
     * @return string shortened string
     */
    function shorten_by_words($str, $length, $append = '...')
    {
        $result = Strings::trailing_words($str, $length, false);
        return ($str != $result) ? $result . $append : $str;
    }

    /**
     * Take substring from the end of supplyed string without breaking leading words
     *
     * @param string $str
     * @param int $length maximum string length
     * @param bool $strict if TRUE than bounds will be expanded if leftmost word cannot
     *  fit into it
     * @return string
     */
    function leading_words($str, $length, $strict = false)
    {
        $l = strlen($str);
        if ($l <= $length) return $str;
        $result = substr($str, -1 * $length);

        if ($strict) {
            // only words that fit in specified bounds
            for ($i = 0, $l = strlen($result); $i < $l; $i++) {
                if (Strings::_is_devider($result[0])) break;
                // cut one byte from the beginning of string
                $result = substr($result, 1);
            }

        } else {
            // expand bounds if leftmost word cannot fit in it
            for ($i = $l - $length - 1; $i >= 0; $i--) {
                if (Strings::_is_devider($str[$i])) break;
                $result = $str[$i] . $result;
            }
        }

        return ltrim($result, implode('', Strings::_word_deviders()));
    }

    /**
     * Take substring from the beginning of supplyed string without breaking trailing words
     *
     * @param string $str
     * @param int $length
     * @param bool $strict if TRUE that bounds will be expanded if rightmost word cannot fit
     *  into it
     * @return string
     */
    function trailing_words($str, $length, $strict = false)
    {
        $l = strlen($str);
        if ($l <= $length) return $str;
        $result = substr($str, 0, $length);

        if ($strict) {
            // only words that can fit in specified bounds
            for ($i = strlen($result) - 1; $i >= 0; $i--) {
                if (Strings::_is_devider($str[$i])) break;
                $result = substr($result, 0, $i);
            }

        } else {
            // expand bounds if rightmost word cannot fit in it
            for ($i = $length; $i < $l; $i++) {
                if (Strings::_is_devider($str[$i])) break;
                $result .= $str[$i];
            }
        }

        return rtrim($result, implode('', Strings::_word_deviders()));
    }
}
