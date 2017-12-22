<?php
/**
 * @version $Revision: 571 $
 */

require_once(SITE_PATH . '/class/Strings.php');

/**
 * Highlights matches from/for binary search
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class SearchHighlighter
{
    /**#@+
     * @access private
     */

    /**
     * Length (in bytes) of wrapping text at left and right sides of matches
     *
     * @var int
     */
    var $_match_wrap_length;

    /**
     * Ellipses text
     *
     * @var string
     */
    var $_ellipses;

    /**
     * Highligh format
     *
     * @var string
     */
    var $_hl_fmt;

    /**
     * Escape callback function
     *
     * @var string|NULL
     */
    var $_escape_cb;

    /**
     * Maximum
     *
     * @var int
     */
    var $_out_maxlen;

    /**#@-*/

    /**
     * Class constructor
     *
     * @return SearchHighlighter
     */
    function SearchHighlighter()
    {
        $this->_match_wrap_length = 15;
        $this->_ellipses = '...';
        $this->_hl_fmt = '<span style="background-color: yellow;">%s</span>';
        $this->_escape_cb = null;
        $this->_out_maxlen = 0;
    }

    /**
     * Escape string with function supplied by user
     *
     * @param string $str string to escape
     * @return string escaped string
     * @access protected
     */
    function _escape($str)
    {
        if (!is_null($this->_escape_cb)) {
            return call_user_func($this->_escape_cb, $str);
        } else {
            return $str;
        }
    }

    /**
     * Set wrapping text length per right and left side for match founded in text
     *
     * @param int $length
     */
    function set_match_wrap_length($length)
    {
        $this->_match_wrap_length = $length;
    }

    /**
     * Set ellipses
     *
     * Ellipses are appended to text wrapping a match.<BR>
     * <b>NB!</b> ellipses are not escaped
     *
     * @param string $ellipses
     */
    function set_ellipses($ellipses)
    {
        $this->_ellipses = $ellipses;
    }

    /**
     * Set {@link sprintf()} format for hilghlight matches
     *
     * @param string $format
     */
    function set_hl_format($format)
    {
        $this->_hl_fmt = $format;
    }

    /**
     * Set callback function used for escaping
     *
     * Usually it should be {@link htmlspecialchars()}
     *
     * @param string $callback
     */
    function set_escape_cb($callback)
    {
        if (!function_exists($callback)) {
            trigger_error(sprintf('Unknown callback function "%s"', $callback)
                , E_USER_WARNING);
            return;
        }

        $this->_escape_cb = $callback;
    }

    /**
     * Set maximum length for text returnred by {@link highlight())
     *
     * @param int $maxlen
     */
    function set_output_maxlen($maxlen)
    {
        $this->_out_maxlen = $maxlen;
    }

    /**
     * Hilight search matches
     *
     * <b>NB!</b> Resulting text might be somewhat longer than $maxlen parameter
     * bacause for cropping text {@link Strings::shorten_by_words()} is used
     *
     * @param string $haystack
     * @param string $needle
     * @param int|NULL $maxlen
     * @return string
     */
    function highlight($haystack, $needle, $maxlen = null)
    {
        if (is_null($maxlen)) $maxlen = $this->_out_maxlen;

        $result = '';
        $hl_needle = sprintf($this->_hl_fmt, $this->_escape($needle));
        $nl = strlen($needle);
        $prev_pos = null;

        while (false !== $pos = strpos($haystack, $needle)) {
            // first match
            if ($pos <= $this->_match_wrap_length) {
                // do not display ellipses
                $result .= $this->_escape(substr($haystack, 0, $pos));

            } else {
                // append wrapping part from previous match
                if (strlen($result)) {
                    $result .= $this->_escape(Strings::trailing_words(substr($haystack, 0, $pos)
                        , $this->_match_wrap_length)) . ' ';
                }

                // prepend ellipses and leading wrapping part
                $result .= $this->_ellipses . ' ' . $this->_escape(Strings::leading_words(
                    substr($haystack, 0, $pos), $this->_match_wrap_length));
            }

            // append highlighted needle
            $result .= $hl_needle;

            // cut leading part from haystack
            $haystack = substr($haystack, $pos + $nl);

            if ($maxlen && strlen($result) >= $maxlen) break;
        }

        if ($result) {
            $append = $this->_escape(Strings::trailing_words($haystack, $this->_match_wrap_length));
            $result .= $append;
            // append ellipses if there are some data left in haystack
            if (strlen($append) < strlen($haystack)) $result .= ' ' . $this->_ellipses;
        }

        return $result;
    }
}