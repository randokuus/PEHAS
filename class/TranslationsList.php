<?php
/**
* @version $Revision: 694 $
*/

require_once(SITE_PATH . '/class/PageNavigation.php');
require_once(SITE_PATH . '/class/Arrays.php');
require_once(SITE_PATH . '/class/templatef.class.php');

/**
 * Class used for creating translations table
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 * @access private
 */
class TranslationsList
{
    /**#@+
     * @access private
     */

    /**
     * Translator object
     *
     * @var Translator
     */
    var $_translator;

    /**
     * Database object
     *
     * @var Database
     */
    var $_database;

    /**
     * Array of languages
     *
     * @var array
     */
    var $_languages;

    /**
     * Array fo filters
     *
     * @var array
     */
    var $_filters;

    /**
     * Page number to display
     *
     * @var int
     */
    var $_page;

    /**
     * Number of records on one page
     *
     * @var int
     */
    var $_page_size;

    /**
     * URL's prefix
     *
     * @var string
     */
    var $_urlbase;

    /**
     * Total number of languages
     *
     * @var int
     */
    var $_lang_count;

    /**
     * Number of plural forms for selected languages
     *
     * @var int
     */
    var $_plurals_count;

    /**
     * Array with sorting columns
     *
     * Whether it is empty array of array with 2 elements: 'column' and 'order'
     *
     * @var array
     */
    var $_sorting;

    /**#@-*/

    /*Constructor**************************************************************/

    /**b
     * @param Translator $translator
     * @param Database $database
     * @return TranslationsList
     */
    function TranslationsList(&$translator, &$database)
    {
        $this->_translator =& $translator;
        $this->_database =& $database;

        $this->_languages = array();
        $this->_filters = array();
        $this->_page = 1;
        $this->_page_size = 10;
        $this->_urlbase = '';

        $this->set_sorting('domain', 'ASC');

        // fetch total number of languages and plural forms from database
        $row = $this->_database->fetch_first_row('SELECT COUNT(*) AS `count`, SUM(nplurals) AS `sum`
            FROM `languages`');
        $this->_lang_count = $row['count'];
        $this->_plurals_count = $row['sum'];
    }

    /**#@+
     * @access private
     */

    /**
     * Make url
     *
     * @param string $query
     * @return string url
     */
    function _make_url($query)
    {
        return $this->_urlbase . '?' . $query;
    }

    /**
     * Create html select tag
     *
     * @param string $name element name and id
     * @param array $items array of select elements
     * @param string $selected_item selected item
     * @param array $attributes array with attributes
     * @return string html
     * @todo actually this should be an independant class for creating html tags
     */
    function _make_select($name, $items, $selected_item = null, $attributes = array())
    {
        // construct attributes
        $attributes_str = '';
        foreach ($attributes as $k => $v) {
            $attributes_str .= ' ' . htmlspecialchars($k) . '="'
                . htmlspecialchars($v) . '"';
        }

        $html = "<select id=\"$name\" name=\"$name\"$attributes_str>\n";
        foreach ($items as $k => $v) {
            $html .= sprintf('<option value="%s"%s>%s</option>' . "\n", htmlspecialchars($k)
                , $k == $selected_item ? ' selected' : '', htmlspecialchars($v));
        }

        $html .= '</select>';
        return $html;
    }

    /**
     * Get all available domains
     *
     * @return array associative array, keys are domain names, values are domain titles
     */
    function _get_domains()
    {
        $domains = array();
        $res =& $this->_database->query("
            SELECT
                `domain`, COUNT(`token`)
            FROM
                `tokens`
            GROUP BY
                `domain`
        ");

        while (list($domain_id, $domain_title) = $res->fetch_row()) {
            $domains[$domain_id] = "$domain_id ($domain_title)";
        }
        return $domains;
    }

    /**
     * Create where sql
     *
     * @return array
     */
    function _get_where_filter()
    {
        $where = array();
        $having = array();
        $lang_filters = array();

        foreach ($this->_filters as $name => $value) {
            // skip empty values
            if (empty($value)) continue;

            switch ($name) {
                // simple filters
                case 'domain':
                    $where[] = sprintf('%s=%s', $this->_database->quote_field_name("tk.$name")
                        , $this->_database->quote($value));
                    break;

                case 'token':
                    $where[] = sprintf('%s LIKE %s', $this->_database->quote_field_name("tk.$name")
                        , $this->_database->quote("%$value%"));
                    break;

                default:
                    $m = array();
                    if (preg_match('/^lang_([a-z]{2})$/', $name, $m)) {
                        // filter for specified language translation
                        $lang_filters[] = sprintf('(`tr`.`language`=%s AND `tr`.`translation` LIKE %s)'
                            , $this->_database->quote($m[1]), $this->_database->quote("%$value%"));
                    }

                    break;
            }
        }

        // postprocess where clause
        $where = implode(' AND ', $where);

        // append translations filters
        if (!empty($lang_filters)) {
            if (!empty($where)) {
                $where .= ' AND (' . implode(' OR ', $lang_filters) . ')';
            } else {
                $where = implode(' OR ', $lang_filters);
            }
        }

        return $where;
    }

    /**
     * Fetches how much translations are matched to filtering
     *
     * @return int number of pages
     */
    function _get_translations_count()
    {
        // get where sql clause part
        $where = $this->_get_where_filter();
        if (!empty($where)) $where = "WHERE $where";

        if (isset($this->_filters['items_type']) && 'untranslated' == $this->_filters['items_type']) {

            $res =& $this->_database->query("
                SELECT
                    `tk`.`domain`
                    , `tk`.`token`
                    , `tk`.`is_plural`
                    , `tr`.`language`
                    , `tr`.`translation`
                    , COUNT(DISTINCT `tr`.`language`) AS `languages`
                    , COUNT(`tr`.`translation`) AS `translations`
                FROM
                    `tokens` AS `tk`
                LEFT JOIN
                    `translations` AS `tr` ON (`tr`.`domain`=`tk`.`domain`
                        AND `tr`.`token`=`tk`.`token` AND `tr`.`language` IN (?@))
                $where
                GROUP BY
                    `tk`.`domain`, `tk`.`token`
                HAVING
                    `languages` \!= ? OR
                    (`tk`.`is_plural` = 1 AND `translations` \!= ?)
            ", array_keys($this->_languages), count($this->_languages), $this->_plurals_count);

            $translations_count = $res->num_rows();
            $res->free();

        } else {
            $translations_count = $this->_database->fetch_first_value("
                SELECT
                    COUNT(DISTINCT `tk`.`domain`, `tk`.`token`)
                FROM
                    `tokens` AS `tk` LEFT JOIN `translations` AS `tr` USING(`domain`, `token`)
                $where
            ");
        }

        return $translations_count;
     }

    /**
     * Get array with translations for building translations table
     *
     * @return array
     */
    function _get_translations()
    {
        $translations = array();

        // get where sql clause part
        $where = $this->_get_where_filter();
        if (!empty($where)) $where = "WHERE $where";

        // create limit
        $limit = sprintf('LIMIT %d, %d', ($this->_page -1 ) * $this->_page_size, $this->_page_size);

        // create order
        $order = sprintf('ORDER BY %s %s', $this->_database->quote_field_name('tk.' . $this->_sorting['column'])
            , $this->_sorting['order']);

        // get count of translations matched by filter

        if (isset($this->_filters['items_type']) && 'untranslated' == $this->_filters['items_type']) {
            //
            // select untranslated domain->token pairs for curent page
            //
            $res =& $this->_database->query("
                SELECT
                    `tk`.`domain`
                    , `tk`.`token`
                    , `tk`.`is_plural`
                    , `tr`.`language`
                    , `tr`.`translation`
                    , COUNT(DISTINCT `tr`.`language`) AS `languages`
                    , COUNT(`tr`.`translation`) AS `translations`
                FROM
                    `tokens` AS `tk` LEFT JOIN `translations` AS `tr` ON (`tr`.`domain`=`tk`.`domain`
                        AND `tr`.`token`=`tk`.`token` AND `tr`.`language` IN (?@))
                $where
                GROUP BY
                    `tk`.`domain`, `tk`.`token`
                HAVING
                    `languages` \!= ? OR
                    (tk.is_plural = 1 AND `translations` \!= ?)
                $limit
            ", array_keys($this->_languages), count($this->_languages), $this->_plurals_count);

            // populate array of untranslated domain->token pairs
            $tokens_domains = array();
            while ($row = $res->fetch_assoc()) {
                $tokens_domains[] = sprintf("(`tk`.`domain` = %s AND `tk`.`token` = %s)"
                    , $this->_database->quote($row['domain']), $this->_database->quote($row['token']));
            }

            if (!empty($tokens_domains)) {
                $where = implode(' OR ', $tokens_domains);
            } else {
                $where = '0';
            }

            $sql = "
                SELECT
                    `tk`.`domain`
                    ,`tk`.`token`
                    ,`tr`.`language`
                    ,`tr`.`translation`
                    , COUNT(`tr`.`plural`) AS `plurals` /* needed to check if not all plural forms are translated */
                    , `tk`.`is_plural`
                FROM
                    `tokens` AS `tk` LEFT JOIN `translations` AS `tr` USING(`domain`, `token`)
                WHERE
                    $where
                GROUP BY
                    `tk`.`domain`, `tk`.`token`, `tr`.`language`
                $order
            ";

        } else {

            //
            // since we are going to draw cross table, we have to select index first for paging
            //

            $res =& $this->_database->query("
                SELECT
                    tk.domain
                    , tk.token
                FROM
                    `tokens` AS `tk` LEFT JOIN `translations` AS `tr` USING(`domain`, `token`)
                $where
                GROUP BY
                    `tk`.`domain`, `tk`.`token`
                $limit
            ");

            //
            // populate array of displayed on current page domain->token pairs
            //

            $tokens_domains = array();
            while (list($domain, $token) = $res->fetch_row()) {
                $tokens_domains[] = sprintf("(`tk`.`domain` = %s AND `tk`.`token` = %s)"
                    , $this->_database->quote($domain), $this->_database->quote($token));
            }

            if (!empty($tokens_domains)) {
                $where = "WHERE " . implode(' OR ', $tokens_domains);
            } else {
                $where = 'WHERE 0';
            }

            //
            // SQL for fetching actual data
            //

            $sql = "
                SELECT
                    `tk`.`domain`
                    ,`tk`.`token`
                    ,`tr`.`language`
                    ,`tr`.`translation`
                    , COUNT(`tr`.`plural`) AS `plurals` /* needed to check if not all plural forms are translated */
                    , `tk`.`is_plural`
                FROM
                    `tokens` AS `tk` LEFT JOIN `translations` AS `tr` USING(`domain`, `token`)
                $where
                GROUP BY
                    `tk`.`domain`, `tk`.`token`, `tr`.`language`
                $order
            ";
        }

        // select translations
        $res =& $this->_database->query($sql);

        //
        // populate translatios array used to build translations table
        //

        $prev_rows = array();
        while ($repeat = true) {
            if (!$curr_row = $res->fetch_assoc()) $repeat = false;

            // save previous data if last record or new token record
            if (!empty($prev_rows) && (empty($curr_row) || $curr_row['token'] != $prev_rows[0]['token']
                || $curr_row['domain'] != $prev_rows[0]['domain']))
            {
                // create a record for current translation
                $record = array();
                $is_plural = null;
                foreach ($prev_rows as $row) {
                    if (!is_null($row['language'])) {
                        $record[$row['language']] = Arrays::array_intersect_key_val($row
                            , array('translation', 'plurals'));
                    }

                    if (is_null($is_plural)) $is_plural = $row['is_plural'];
                }

                // add translation to array
                $translations[][$row['token']][$row['domain']] = array(
                    'is_plural' => $is_plural,
                    'translations' => $record,
                );

                $prev_rows = array();
            }

            $prev_rows[] = $curr_row;
            if (!$repeat) break;
        }

        return $translations;
    }

    /**#@-*/

    /**
     * Set column for sorting
     *
     * @param string $column column name
     * @param string $type whether ASC or DESC
     */
    function set_sorting($column, $type)
    {
        if (!in_array($type, array('ASC', 'DESC'))) return;
        if (!in_array($column, array('domain', 'token'))) return;

        $this->_sorting = array('column' => $column, 'order' => $type);
    }


    /**
     * Set urlbase
     *
     * @param string $urlbase
     */
    function set_urlbase($urlbase)
    {
        $this->_urlbase = $urlbase;
    }

    /**
     * Set languages displayed in translations table
     *
     * @param array $languages array with language codes
     */
    function set_languages($languages)
    {
        if (empty($languages)) return;

        $this->_languages = array();
        $this->_plurals_count = 0;

        $res =& $this->_database->query('
            SELECT
                `language`, `title`, `nplurals` FROM `languages`
            WHERE
                `language` IN(?@)'
        , $languages);

        // store retrived info in internal array saving $languages positions
        while ($row = $res->fetch_assoc()) {
            if (FALSE === array_search($row['language'], $languages)) continue;

            $this->_languages[$row['language']] = $row;
            $this->_plurals_count += $row['nplurals'];
        }

        // sort array to restore intial elements order
        ksort($this->_languages);
    }

    /**
     * Set array of filters appliyed to table
     *
     * @param array $filters
     */
    function set_filters($filters)
    {
        $this->_filters = $filters;
    }

    /**
     * Set active page number
     *
     * @param int $page
     */
    function set_page($page)
    {
        if ($page < 1) $page = 1;
        $this->_page = $page;
    }

    /**
     * Set number of records (translations) displayed on one page
     *
     * @param int $page_size
     */
    function set_page_size($page_size)
    {
        if ($page_size < 1) $page_size = 1;
        $this->_page_size = $page_size;
    }

    /**
     * Get translations list html source
     *
     * @return string
     */
    function __toString()
    {
        $tpl = new template();
        $tpl->setCacheLevel(TPL_CACHE_NOTHING);
        $tpl->setTemplateFile('tmpl/admin_translator_translations_list.html');
        $tpl->addDataItem('FORM_ACTION', $this->_make_url('do=apply_filters'));

        //
        // Setup domains filtering list
        //

        $domain_options = sprintf('<option value="_all"%s>%s</option>', !isset($this->_filters['domain'])
            ? ' selected' : '', htmlspecialchars($this->_translator->tr('all_domains')));

        foreach ($this->_get_domains() as $domain => $title) {
            $title = htmlspecialchars($title);
            $domain = htmlspecialchars($domain);

            if (isset($this->_filters['domain']) && $this->_filters['domain'] == $domain) {
                // save in session current domain for add new token
                if (!isset($_SESSION['admin']['translator']['domain'])
                    || $_SESSION['admin']['translator']['domain'] != $this->_filters['domain'])
                {
                    $_SESSION['admin']['translator']['domain'] = $this->_filters['domain'];
                }
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }

            $domain_options .= sprintf('<option value="%s"%s>%s</option>', $domain, $selected, $title);
        }

        $tpl->addDataItem('DOMAINS_OPTIONS', $domain_options);

        //
        // Setup menu
        //

        $tpl->addDataItem('MANAGE_LANG_COLS', $this->_translator->tr('manage language columns'));
        $tpl->addDataItem('COMPILE_FILES', $this->_translator->tr('compile language files'));

        // filter: show all|untranslated records

        if (!isset($this->_filters['items_type']) || 'all' == $this->_filters['items_type']) {
            $items_type_txt_arr = array(sprintf('<strong>%s</strong>', $this->_translator->tr('all')));
        } else {
            $items_type_txt_arr = array(sprintf('<a href="%s">%s</a>', $this->_make_url('do=apply_filters&filters[items_type]=all')
                , $this->_translator->tr('all')));
        }

        foreach (array('untranslated') as $item_type) {
            if (isset($this->_filters['items_type']) && $this->_filters['items_type'] == $item_type) {
                $items_type_txt_arr[] = sprintf('<strong>%s</strong>', $this->_translator->tr($item_type));
            } else {
                $items_type_txt_arr[] = sprintf('<a href="%s">%s</a>', $this->_make_url('do=apply_filters&filters[items_type]='
                    . htmlspecialchars($item_type)), $this->_translator->tr($item_type));
            }
        }

        $tpl->addDataItem('MENU_SHOW_TRANSLATIONS_TYPE', sprintf($this->_translator->tr('show %s items')
            , implode(' | ', $items_type_txt_arr)));

        // rows on page

        $pagesizes_txt_arr = array();
        foreach (array(10, 25, 50, 100) as $size) {
            if ($this->_page_size == $size) {
                $pagesizes_txt_arr[] = "<strong>$size</strong>";
            } else {
                $pagesizes_txt_arr[] = sprintf('<a href="%s">%s</a>', $this->_make_url("do=apply_filters&filters[rows]=$size"), $size);
            }
        }

        $tpl->addDataItem('MENU_ROWS_ON_PAGE', $this->_translator->tr('rows_on_page')
            . ': ' . implode(' | ', $pagesizes_txt_arr));

        //
        // Setup tanslations table
        //

        // pager initialization
        $pager = new PageNavigation();
        $pager->set_texts($this->_translator->tr('previous'), $this->_translator->tr('next'));
        $pager->set_show_first_last(true);
        $pager->set_first_last_type('numbers');
        $pager->set_pages_block_format(' %s ');
        $pager->set_last_format('.. %s ');
        $pager->set_first_format(' %s .. ');
        $pager->set_link_format($this->_make_url('do=list&page=%d'));

        // calculate column width for token and each language columns
        $col_width = round(100 / (count($this->_languages) + 1)) . '%';

        //
        // claculate pages count, starting and ending offset
        //

        $translations_count = $this->_get_translations_count();
        $pages_count = ceil($translations_count / $this->_page_size);
        if ($pages_count < 1) $pages_count = 1;

        // correct page number if it is too big
        if ($this->_page > $pages_count) $this->_page = $pages_count;

        $displaying_from = ($this->_page - 1) * $this->_page_size + 1;
        $displaying_to = ($this->_page) * $this->_page_size + 1;
        if ($displaying_to > $translations_count) $displaying_to = $translations_count;

        $tpl->addDataItem('PAGER_INFO', sprintf($this->_translator->tr('displaying %d - %d of %d translations')
            , $displaying_from, $displaying_to, $translations_count));

        $tpl->addDataItem('PAGER', $pager->navigation_html($this->_page, $pages_count));
        $tpl->addDataItem('TOTALCOLS', count($this->_languages) + 3);
        $tpl->addDataItem('DOMAIN', $this->_translator->tr('domain'));

        //
        // what image and link to display for sorting by domain and token
        //

        foreach (array('domain', 'token') as $col) {
            $order = 'ASC';
            if ($col == $this->_sorting['column']) {
                if ('ASC' == $this->_sorting['order']) {
                    $order = 'DESC';
                    $direction = 'dn';
                } else {
                    $direction = 'up';
                }
                $tpl->addDataItem(strtoupper($col) . '_CLASS', "active $direction");
            }

            $tpl->addDataItem('URL_SORT_BY_' . strtoupper($col), $this->_make_url("do=sort&column=$col&order=$order"));

        }

        $tpl->addDataItem('TOKEN', $this->_translator->tr('token'));
        $tpl->addDataItem('TOKEN_COL_WIDTH', $col_width);

        // languages headers
        $show_remove_lang = count($this->_languages) > 1;
        foreach ($this->_languages as $lang_arr) {
            $tpl->addDataItem('LANG_HEADERS.LANG_COL_WIDTH', $col_width);
            $tpl->addDataItem('LANG_HEADERS.LANGUAGE', htmlspecialchars($lang_arr['title']));
            if ($show_remove_lang) {
                $tpl->addDataItem('LANG_HEADERS.REMOVE_LANG', sprintf('<a href="%s"><img src="pic/close.gif" width="11" height="11" border="0" alt=""/></a>'
                    , $this->_make_url("do=remove_lang_col&lang=$lang_arr[language]")));
            }
        }

        //
        // filters
        //

        $tpl->addDataItem('CLEAR_FILTERS', $this->_translator->tr('clear_filters'));
        $tpl->addDataItem('RUN_FILTER', $this->_translator->tr('run_filter'));
        $tpl->addDataItem('FILTER_TOKEN_VALUE', $this->_filters['token']);

        // language filters
        foreach ($this->_languages as $lang_arr) {
            $tpl->addDataItem('LANG_FILTERS.LANG_CODE', $lang_arr['language']);
            $tpl->addDataItem('LANG_FILTERS.FILTER_LANG_VALUE', $this->_filters["lang_$lang_arr[language]"]);
        }

        //
        // translations
        //

        $i = ($this->_page - 1) * $this->_page_size;
        $j = 0;

        foreach ($this->_get_translations() as $translation_arr) {
            $j++;
            // extract token and go deeper
            list($token, $translation_arr) = each($translation_arr);
            // extract domain and go deeper
            list($domain, $translation_arr) = each($translation_arr);
            // extract translations and is_plural
            $is_plural = $translation_arr['is_plural'];
            $translation_arr = $translation_arr['translations'];

            //
            // pass variables to template
            //

            $url_edit = $this->_make_url(sprintf('do=edittr&token=%s&domain=%s'
                , htmlspecialchars($token), htmlspecialchars($domain)));
            $url_delete = $this->_make_url(sprintf('do=deltr&token=%s&domain=%s'
                , htmlspecialchars($token), htmlspecialchars($domain)));
            $url_edit_save = processUrl(SITE_URL . $_SERVER['PHP_SELF'] , $_SERVER["QUERY_STRING"]
                ,sprintf('do=savetr&token=%s&domain=%s', htmlspecialchars($token)
                        , htmlspecialchars($domain)) ,array('do', 'token', 'domain', 'lan', 'value'));

            $tpl->addDataItem('TRANSLATION_ROW.CLASS', $j % 2 ? 'row-2' : 'row-1');
            $tpl->addDataItem('TRANSLATION_ROW.NUM', $i + $j);
            $tpl->addDataItem('TRANSLATION_ROW.DELETE', $this->_translator->tr('delete'));
            $tpl->addDataItem('TRANSLATION_ROW.URL_DELETE', $url_delete);
            $tpl->addDataItem('TRANSLATION_ROW.DOMAIN', htmlspecialchars($domain));
            $tpl->addDataItem('TRANSLATION_ROW.TOKEN_ICON', sprintf('<img src="pic/%s.gif" width="11" height="11" alt="" />'
                , $is_plural ? 'plural' : 'singular'));
            $tpl->addDataItem('TRANSLATION_ROW.TOKEN', htmlspecialchars($token));
            $tpl->addDataItem('TRANSLATION_ROW.URL_TOKEN', $url_edit);
            $tpl->addDataItem('TRANSLATION_ROW.CONFIRM_DELETE', $this->_translator->tr(
                'confirm delete translation %s', array(htmlspecialchars($token)), 'js'));


            foreach ($this->_languages as $lang_arr) {
                // by default css class for translation cell is empty
                $class = '';
                if (array_key_exists($lang_arr['language'], $translation_arr)) {
                    //
                    // translation exists
                    //
                    $cur_translation_arr = $translation_arr[$lang_arr['language']];
                    $translation = htmlspecialchars($cur_translation_arr['translation']);
                    if ($is_plural && $lang_arr['nplurals'] != $cur_translation_arr['plurals']) {
                        $class = 'plural-untr';
                        $td = sprintf('<a href="%s">%s</a>', $url_edit, $translation);
                    } else {
                        $td = sprintf('<div class="edit" name="%s">%s</div>'
                            , $url_edit_save . "&lan={$lang_arr['language']}", $translation);
                    }

                } else {
                    //
                    // translation do not exists
                    //
                    $translation = $this->_translator->tr('untranslated');
                    $class = 'untr';
                    if ($is_plural) {
                        $td = sprintf('<a href="%s">%s</a>', $url_edit, $translation);
                    } else {
                        $td = sprintf('<div class="edit" name="%s">%s</div>'
                            , $url_edit_save . "&lan={$lang_arr['language']}", $translation);
                    }
                }

                $tpl->addDataItem('TRANSLATION_ROW.LANGUAGES.CLASS', $class);
                $tpl->addDataItem('TRANSLATION_ROW.LANGUAGES.TD', $td);
           }
        }

        return $tpl->parse() . $html;
    }
}
