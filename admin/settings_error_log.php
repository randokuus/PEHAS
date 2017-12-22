<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

require_once('admin_header.php');
require_once(SITE_PATH . '/class/templatef.class.php');
require_once(SITE_PATH . '/class/admin2.class.php');
require_once(SITE_PATH . '/class/Strings.php');

/**
 * Class for error log parsing
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class ErrorLogParser
{
    /**
     * Associative array of options
     *
     * @var array
     * @access private
     */
    var $_options;

    /**
     * Path to log file
     *
     * @var string
     * @access private
     */
    var $_log_file;

    /**
     * Array with parsed data
     *
     * @var array
     * @access private
     */
    var $_parsed_data;

    /**
     * Buffer for storing chunks of data
     *
     * @var string
     * @access private
     */
    var $_chunk_buffer;

    /**
     * Constructor
     *
     * @param string $log_file path to error log file
     * @param array $options associative array of options
     * @return ErrorLogParser
     */
    function ErrorLogParser($log_file, $options)
    {
        $this->_log_file = $log_file;
        $this->_options = $options;
        $this->_chunk_buffer = '';
    }

    /**
     * Parse errorentry record from error log
     *
     * @param string $errorentry errorentry string
     * @return FALSE|null FALSE returned when parsing process should be stopped
     * @access private
     */
    function _parse_errorentry($errorentry)
    {
        if (preg_match_all("|<([^>]+)>(.*)</[^>]+>|U", $errorentry, $m, PREG_SET_ORDER)) {
            $data = array();
            foreach ($m as $match) {
                list(, $tag, $value) = $match;
                $data[$tag] = htmlspecialchars($value);
            }
        } else {
            return;
        }

        // pass errorentry through filter
        if ($this->_errorentry_filter($data)) {
            if ($this->_options["id"]) {
                $this->_parsed_data["list_data"][] = $data;
                return false;
            } else {
                // check if errorentry is on current page and save it to
                // parsed_data array

                $this->_parsed_data["total"]++;
                if ($this->_parsed_data["total"] > $this->_options["start"]) {
                    if ($this->_parsed_data["total"] <= ($this->_options["start"]
                        + $this->_options["page_size"]))
                    {
                        // postprocess data
                        $data['errormsg'] = Strings::shorten($data['errormsg'], 50);
                        $data['usrrequest'] = Strings::shorten($data['usrrequest'], 35);

                        $this->_parsed_data["list_data"][] = $data;
                    } else {
                        return false;
                    }
                }
            }
        }
    }

    /**
     * Check errorentry against filters
     *
     * @param array $errorentry associative array of errorentry
     * @return bool TRUE if entry passed though filters, FALSE if entry should be
     *  filtered out
     * @access private
     */
    function _errorentry_filter($errorentry)
    {
        // select by id
        if ($this->_options["id"]) {
            if ($errorentry["id"] != $this->_options["id"]) {
                return false;
            }
        } else {
            foreach ($errorentry as $k => $v) {
                $filter = $this->_options["filters"][$k];
                if (null !== $filter && "" !== $filter) {
                    if (in_array($k, array("errormsg"))) {
                        // free text filter
                        if (false === strpos(strtoupper($v), strtoupper($filter))) {
                            return false;
                        }
                    } else {
                        // equal filter
                        if ($v != $filter) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Parse chunk of data from error log
     *
     * @param string $data
     * @return FALSE|null FALSE returned when parsing process should be stopped
     * @access private
     */
    function _parse_log_chunk($chunk)
    {
        $chunk .= $this->_chunk_buffer;

        $positions = array();
        $offset = -1;
        while (false !== ($pos = strpos($chunk, "<errorentry>", $offset + 1))) {
            array_unshift($positions, $pos);
            $offset = $pos;
        }

        foreach ($positions as $position) {
            $errorentry = substr($chunk, $position);
            $chunk = substr($chunk, 0, $position);
            if (false === $this->_parse_errorentry($errorentry)) {
                return false;
            }
        }

        $this->_chunk_buffer = $chunk;
    }

    /**
     * Parse log file and return parsed data
     *
     * @return array
     */
    function parse()
    {
        $this->_chunk_buffer = '';
        $this->_parsed_data = array(
            "list_data" => array(),
            "total" => 0,
        );

        if (is_readable($this->_log_file)) {
            $fp = fopen($this->_log_file, "r");
            if ($fp) {
                // read and parse error log file from the end
                $fsize = filesize($this->_log_file);
                $step = 4096;
                $offset = 0;
                while ($offset < $fsize) {
                    if (($offset + $step) > $fsize) {
                        $step = $fsize - $offset;
                    }

                    $offset += $step;
                    if (0 === fseek($fp, $offset * -1, SEEK_END)) {
                        $data = fread($fp, $step);
                        if (false === $this->_parse_log_chunk($data)) {
                            break;
                        }
                    } else {
                        // something is wrong - unable to seek
                        break;
                    }
                }
                fclose($fp);
            }
        }

        // clear parsed data from object variables
        $parsed_data = $this->_parsed_data;
        $this->_parsed_data = null;
        return $parsed_data;
    }
}

$tr =& ModeraTranslator::instance($language2, 'admin_general');
$trf =& ModeraTranslator::instance($language2, 'admin_logs');

if ($max_entries) {
    $general["max_entries"] = $max_entries;
}

$idfield = "id"; // name of the id field (unique field)
if ($show != "modify" || !$id) {
    unset($id);
}

$general["template_list"] = "tmpl/admin_view_list.html";
$general["template_pages"] = "tmpl/pages.html";
$general["next"] = "... " . $general["next"];

$disp_fields = array(
    'datetime' => $trf->tr('datetime'),
    'errortype'=> $trf->tr('errortype'),
    'errornum' => $trf->tr('errornum'),
    'errormsg' => $trf->tr('errormsg'),
    'usrrequest' => $trf->tr('usrrequest'),
    'usrip' => $trf->tr('usrip'),
);

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general["template_main"]);
$tpl->addDataItem("TITLE", $trf->tr("module_title"));

//
// Parse error log file
//

$elp = new ErrorlogParser(SITE_PATH . "/cache/error.log", array(
    "start" => $start,
    "id" => $id,
    "page_size" => $general["max_entries"],
    "start" => $start,
    "filters" => array(
        "errormsg" => $filter,
        "errortype" => $errortype_filter,
        "errornum" => $errornum_filter,
        "ip" => $ip_filter,
    ),
));

$parsed_data = $elp->parse();

// for save selected filters and current page
//$sort = '&errortype_filter=' . $errortype_filter . '&errornum_filter=' . $errornum_filter
//    . '&ip_filter=' . $ip_filter;

$adm = new Admin2("");
$adm->assign("language", $language);

if ($show == "modify" && $id) {
    foreach ($parsed_data['list_data'][0] as $name => $value) {
        if (in_array($name, array("id"))) {
            continue;
        }

        $name = strtolower($name);
        $fields[$name] = $trf->tr($name);
        $adm->assign($name, $value);
        $adm->displayOnly($name);
    }

    $adm->assignHidden('max_entries', $max_entries);
    $adm->assignHidden('start', $start);

    $adm->general["button"] = $general['backtolist'];
    $result = $adm->form($fields, $sort, '', $filter, "update", $id
        , array(1 => array($trf->tr("error_details"), "")));

} else {

    // no filters atm, later can be added if needed at this point

    $result = $adm->show($disp_fields, $parsed_data["list_data"], $start, $sort, '', $filter
        , '', $idfield);

    // pages
    if ($start || $parsed_data["total"] > $start + $general["max_entries"]) {
        $tpl_pages = new template();
        $tpl_pages->setCacheLevel(TPL_CACHE_NOTHING);
        $tpl_pages->setTemplateFile($general["template_pages"]);

        $tpl_pages->addDataItem("PAGES.PAGES", $general["pages"]);
        $pages_navi = $adm->showPages($start, $parsed_data['total']
            , $adm->phpself . "?filter=$filter&max_entries=" . $general["max_entries"]
            . $sort);

        $tpl_pages->addDataItem("PAGES.LINKS", $pages_navi);
        $result .= $tpl_pages->parse();
    }
}

$tpl->addDataItem("CONTENT", $result);
echo $tpl->parse();
