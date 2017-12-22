<?php
/*
 * @version $Revision: 257 $
 */

/**
 * Page navigation control
 *
 * Class for creating page navigation html controls. << previous [1] .. [5] [6] [7] [8] .. [63] next >>
 *
 * <code>
 * $p = new PageNavigation();
 * $p->set_show_prev_next('always');
 * $p->set_show_first_last(true);
 * $p->set_first_last_type('texts');
 *
 * echo $p->navigation(33, 100, 11);
 * </code>
 *
 * @author Alexandr Chertkov <s6urik@modera.ee>
 */
class PageNavigation
{
    /**#@+
     * @access private
     */

    /**
     * Array of textx used in paging navigation
     *
     * @var array
     */
    var $_texts;

    /**
     * Arrays of diffrent formats
     *
     * @var array
     */
    var $_formats;

    /**
     * Wether to add direction arrows with prev and next page links or not
     *
     * @var bool
     */
    var $_show_arrows;

    /**
     * Controls when to show links to previous and next page
     *
     * @var mixed TRUE - display links to prev and next pages when needed,
     *  FALSE - do not display links, 'always' - always display links to
     *  next and prev pages
     */
    var $_show_prev_next;

    /**
     * Controls when to show links to first and last pages
     *
     * @var mixed TRUE - display links to first and last pages when needed,
     *  FALSE - do not display links, 'always' - always display links to
     *  first and last pages
     */
    var $_show_first_last;

    /**
     * Type of links to first and last pages
     *
     * One of: 'texts' - text labels, 'numbers' - numbers of pages 1 - xxx
     *
     * @var string
     */
    var $_first_last_type;

    /**#@-*/

    /*Constructor**************************************************************/

    function PageNavigation()
    {
        $this->_texts = array('prev' => 'Previous', 'next' => 'Next'
            , 'first' => 'First', 'last' => 'Last');

        $this->_formats = array('label' => ' %s ', 'active_label' => ' [%s] '
            , 'pages_block' => ' |&nbsp;%s&nbsp;| ', 'link' => './?page=%s'
            , 'first' => ' %s ', 'prev' => ' %s ', 'next' => ' %s ', 'last' => ' %s ');

        $this->_show_arrows = true;
        $this->_show_prev_next = 'always';
        $this->_show_first_last = true;
        $this->_first_last_type = 'texts';
    }

    /*Private******************************************************************/

    /**
     * Create link to page
     *
     * @param string $text link text
     * @param int $num if less than 0 than only text will be displayed
     * @return string
     * @access private
     */
    function _link_to_page($text, $pagenum)
    {
        if ($pagenum > 0) {
            $link = sprintf('<a href="%s">%s</a>', htmlspecialchars(sprintf($this->_formats['link']
                , $pagenum)), $text);
        } else {
            $link = $text;
        }

        return $link;
	}

    /*Public*******************************************************************/

    /**
     * Set texts for links
     *
     * Set texts for previous and next page links
     *
     * @param string $prev text of link to previous page
     * @param string $next text of link to next page
     * @param string $first
     * @param string $last
     */
    function set_texts($prev, $next, $first = null, $last = null)
    {
        $this->_texts['prev'] = htmlspecialchars($prev);
        $this->_texts['next'] = htmlspecialchars($next);
        if (!is_null($first)) $this->_texts['first'] = htmlspecialchars($first);
        if (!is_null($last)) $this->_texts['last'] = htmlspecialchars($last);
    }

    /**#@+
     * Set item format string used in {@link sprintf()} php function
     *
     * @param string $format
     */

    function set_label_format($format) { $this->_formats['label'] = $format; }
    function set_active_label_format($format) { $this->_formats['active_label'] = $format; }
    function set_pages_block_format($format) { $this->_formats['pages_block'] = $format; }
    function set_link_format($format) { $this->_formats['link'] = $format; }
    function set_first_format($format) { $this->_formats['first'] = $format; }
    function set_prev_format($format) { $this->_formats['prev'] = $format; }
    function set_next_format($format) { $this->_formats['next'] = $format; }
    function set_last_format($format) { $this->_formats['last'] = $format; }

    /**#@-*/

    /**
     * Wether to show arrows or not
     *
     * @param bool $show_arrows
     */
    function set_show_arrows($show_arrows)
    {
        $this->_show_arrows = $show_arrows;
    }

    /**
     * Set when to show links to first and last pages
     *
     * @param mixed $show_first_last TRUE - show links when needed, FALSE - do not
     *  show links, 'always' - show links always
     */
    function set_show_first_last($show_first_last)
    {
        $this->_show_first_last = $show_first_last;
    }

    /**
     * How to show links to first and last pages
     *
     * @param string $show_first_last 'texts', 'numbers'
     */
    function set_first_last_type($first_last_type)
    {
        $this->_first_last_type = $first_last_type;
    }

    /**
     * When to show links to previous and last pages
     *
     * @param mixed $show_prev_next TRUE - show links when needed, FALSE - do not
     *  show links, 'always' - show links always
     */
    function set_show_prev_next($show_prev_next)
    {
        $this->_show_prev_next = $show_prev_next;
    }

    /**
     * Return page navigation html
     *
     * @param int $current_page current page number starting from 1
     * @param int $total_pages total available pages number
     * @param int $max_pages_to_display maximum number of page links to display
     * @param int $active_page_center_offset active page label offset from centers
     * @return string
     */
    function navigation_html($current_page, $total_pages, $max_pages_to_display = 10
        , $active_page_center_offset = 0)
    {
        //
        // check parameters and correct it when needed
        //

        if ($total_pages < 1) {
            trigger_error('Pages number cannot be less than one', E_USER_WARNING);
            $total_pages = 1;
        }

        if ($current_page < 1) {
            trigger_error('Current page number cannot be less than one', E_USER_WARNING);
            $current_page = 1;
        }

        if ($current_page > $total_pages) {
            trigger_error('Current page number cannot be greater than total page numbers'
                , E_USER_WARNING);
            $current_page = $total_pages;
        }

        // return empty string if there are only one page
        if (1 == $total_pages) return '&nbsp;';

        // if maximum page number is even than there couldn't be an absolute
        // center element, for example if maximum number of pages will be 6
        // than center element will be ceil(6 / 2) = 3
        if ($active_page_center_offset > ceil($max_pages_to_display / 2) ||
            $active_page_center_offset <= -1 * ceil($max_pages_to_display / 2))
        {
            trigger_error('Too big offset specified', E_USER_WARNING);
            $active_page_center_offset = 0;
        }

        // reset navigation control blocks
        $first = $prev = $pages_block = $next = $last = '';

        $active_page_offset = ceil($max_pages_to_display / 2) + $active_page_center_offset;

        // calculate starting offset
        if ($total_pages > $max_pages_to_display && $current_page > $active_page_offset) {
            // offset needed
            $offset = $current_page - $active_page_offset;
            if ($offset > $total_pages - $max_pages_to_display) {
                $offset = $total_pages - $max_pages_to_display;
            }
        } else {
            $offset = 0;
        }

        // calculate max
        $max = $offset + $max_pages_to_display;
        if ($max > $total_pages) $max = $total_pages;

        // first page link
        if ('always' === $this->_show_first_last || ($this->_show_first_last && $offset)) {
            $first = $this->_link_to_page('texts' == $this->_first_last_type
                ? $this->_texts['first'] : 1, $current_page == 1 ? 0 : 1);

            $first = sprintf($this->_formats['first'], $first);
        }

        // previous page link
        if ('always' === $this->_show_prev_next || ($this->_show_prev_next && $current_page > 1)) {
            $prev = '&#171; ' . $this-> _link_to_page($this->_texts['prev'], $current_page - 1);
            $prev = sprintf($this->_formats['prev'], $prev);
        }

        // construct block with links to pages
        $pages_block = '';
        for ($i = $offset + 1; $i <= $max; $i++) {
            if ($i == $current_page) {
                $pages_block .= sprintf($this->_formats['active_label'] ,$this->_link_to_page($i, 0));
            } else {
                $pages_block .= sprintf($this->_formats['label'], $this->_link_to_page($i, $i));
            }
        }
        $pages_block = sprintf($this->_formats['pages_block'], $pages_block);

        // next page link
        if ('always' === $this->_show_prev_next || ($this->_show_prev_next && $current_page != $total_pages)) {
            $next = $this-> _link_to_page($this->_texts['next'], $current_page != $total_pages
                ? $current_page + 1 : 0)  . ' &#187;';

            $next = sprintf($this->_formats['next'], $next);
        }

        // last page link
        if ('always' === $this->_show_first_last || ($this->_show_first_last && $max != $total_pages)) {
            $last = $this->_link_to_page('texts' == $this->_first_last_type
                ? $this->_texts['last'] : $total_pages
                , $current_page == $total_pages ? 0 : $total_pages);

            $last = sprintf($this->_formats['last'], $last);
        }

        if ('texts' == $this->_first_last_type) {
            return $first . $prev . $pages_block . $next . $last;
        } else {
            return $prev . $first . $pages_block . $last . $next;
        }
    }
}
