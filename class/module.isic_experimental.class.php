<?php
//error_reporting(E_ALL);
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . '/JsonEncoder.php');
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicEncoding.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicPayment.php");
//require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Database.php");

class isic_experimental {
    /**
     * @var array merged array with _GET and _POST data
     */
    var $vars = array();

    /**
     * Current language code
     *
     * @var string
     * @access protected
     */
    var $language;

    /**
     * Active template set
     *
     * @var int
     * @access private
     */
    var $tmpl;

    /**
     * Database instance
     *
     * @var Database
     * @access protected
     */
    var $db;
    /**
     * @var boolean does this module provide additional parameters to admin page admin
     */
    var $content_module = true;
    /**
     * @var array additional parameters set at the page admin for this template
     */
    var $module_param = array();
    /**
     * @var integer active user id
     */
    var $userid = false;
    /**
     * @var integer user group ID
     */
    var $usergroup = false;
    /**
     * @var array user groups
     */
    var $usergroups = false;
    /**
     * @var user type (1 - can view all cards from the school his/her usergroup belongs to, 2 - only his/her own cards)
     */
    var $user_type = false;

    /**
     * @var user code (personal number (estonian id-code for example))
     */
    var $user_code = false;

    /**
     * @var boolean is page login protected, from $GLOBALS["pagedata"]["login"]
     */
    var $user_access = 0;

    /**
     * Users that are allowed to access the same cards as current user
     *
     * @var array
     * @access protected
     */
    var $allowed_users = array();

    /**
     * Maximum number of results in listview
     *
     * @var int
     * @access protected
     */
    var $maxresults = 10;

    /**
     * Level of caching of the pages
     *
     * @var const
     * @access protected
     */

    var $cachelevel = TPL_CACHE_NOTHING;

    /**
     * Cache time in minutes
     *
     * @var int
     * @access protected
     */

    var $cachetime = 1440;


    /**
     * Default language for new isic card
     *
     * @var int
     * @access protected
     */

    var $language_default = 3;

    /**
     * Default kind type for new isic card
     *
     * @var int
     * @access protected
     */

    var $kind_default = 1;

    /**
     * List view type
     *
     * @var string (all, ordered, void)
     * @access protected
     */

    var $list_type = "all";

    /**
     * Image size
     *
     * @var string
     * @access protected
     */

//    var $image_size = '261x261';
    var $image_size = '307x372';
    var $image_size_x = '307';
    var $image_size_y = '372';

    /**
     * Image size - thumbnail
     *
     * @var string
     * @access protected
     */

    var $image_size_thumb = '83x100';

    /**
     * Collateral sum array for card_types
     *
     * @var array
     * @access protected
     */

    var $collateral_sum = false;

    /**
     * First payment sum array for card_types
     *
     * @var array
     * @access protected
     */

    var $first_sum = false;

    /**
     * Card cost sum array for card_statuses
     *
     * @var array
     * @access protected
     */

    var $cost_sum = false;

    /**
     * Default translation module to use
     *
     * @var string
     * @access protected
     */
    var $translation_module_default = "module_isic_card";

    var $fieldData = false;

    /**
     * Card type in case it's specified in content page parameters
     *
     * @var int
     * @access protected
     */

    var $card_type_current = 0;

   /**
     * Class constructor
     *
     * @global $GLOBALS['site_settings']['template']
     * @global $GLOBALS['language']
     * @global $GLOBALS['database']
     */

    function isic_experimental () {
        $this->vars = array_merge($_GET, $_POST);
        $this->tmpl = $GLOBALS['site_settings']['template'];
        $this->language = $GLOBALS['language'];
        $this->db = &$GLOBALS['database'];
        $this->userid = $GLOBALS["user_data"][0];
        $this->usergroup = $GLOBALS["user_data"][4];
        $this->usergroups = $GLOBALS["user_data"][5];
        $this->user_type = $GLOBALS["user_data"][6];
        $this->user_code = $GLOBALS["user_data"][7];

        if ($this->content_module == true) {
            $this->getParameters();
        }

        if ($this->module_param["isic_experimental"]) {
            $this->card_type_current = $this->module_param["isic_experimental"];
        }

        // assigning common methods class
        $this->fieldData = $this->getFieldData($this->card_type_current);
        $this->isic_common = IsicCommon::getInstance();
        $this->isic_encoding = new IsicEncoding();
        $this->isic_payment = new IsicPayment();

        $this->allowed_schools = $this->isic_common->allowed_schools;
        $this->allowed_card_types = $this->isic_common->createAllowedCardTypes();
        setlocale(LC_ALL, 'en_US.UTF-8');
    }

    /**
     * Main module display function
     *
     * @return string html ISIC content
    */

    function show () {
        if ($this->checkAccess() == false) return "";

        if (!$this->userid) {
            trigger_error("Module 'ISIC' requires an authorized user. Configure the site to be password protected.", E_USER_ERROR);
        }

        if ($this->user_type == 2 && !$this->user_code) {
            trigger_error("Module 'ISIC' user must have ID-code to be assigned. Contact administrator.", E_USER_ERROR);
        }
        if ($this->vars["getlist"]) {
            $this->getCardList();
        } elseif ($this->vars["getdetail"]) {
            $this->getCardDetail();
        } elseif ($this->vars["submitform"]) {
            $this->saveCard();
        } else {
            $result = $this->showCardContainer();
        }
        return $result;
    }

    /**
     * Creates actual sub-template which will be the placeholde for the Ext grid and form elments
     * also creates all the needed json-formatted data for the filters etc.
     *
     * @return string html of the sub-template
    */

    function showCardContainer()
    {
        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_list2.html";

        $tpl->setInstance($_SERVER["PHP_SELF"] . "?language=" . $this->language . "&module=isic_experimental");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "isic_experimental";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module isic_experimental cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        $tpl->addDataItem("DATA_URL", "/?content={$this->vars['content']}");
        $tpl->addDataItem("DATA_MAXROWS", $this->maxresults);
        $tpl->addDataItem("DATA_FIELDS", $this->getFieldList($this->fieldData['listview'], 'list'));
        $tpl->addDataItem("DATA_GRID_COLUMN", $this->getFieldList($this->fieldData['listview'], 'grid_column'));
        $tpl->addDataItem("DATA_FILTERS", $this->getFieldList($this->fieldData['filterview'], 'filters'));

        $tpl->addDataItem("DATA_FORM_COLUMNS", $this->getColumnList());
        $fs_list = $this->getFieldSetList();
        $tpl->addDataItem("DATA_FORM_FIELDSETS", JsonEncoder::encode($fs_list));
        $tpl->addDataItem("DATA_FORM_FIELDS", $this->getFieldList($this->fieldData['detailview'], 'detail', $fs_list));
        $tpl->addDataItem("DATA_FORM_FIELDS_MAPPING", $this->getFieldList($this->fieldData['detailview'], 'list'));
        return $tpl->parse();
    }

    /**
     * Creates array of all the field information from database
     *
     * @param int $card_type_current card type that we need field data for
     * @return array of field data with 3 main sub-arrays (detailview, listview and filterview)
    */

    function getFieldData($card_type_current = 0)
    {
        $txt = new Text($this->language, $this->translation_module_default);
        $result = array();
        $ct_filter = false;
        $ct_list = false;
        $ct_detail = false;

        if ($card_type_current) {
            // list, filter and detail info for current card type
            $res =& $this->db->query("
                SELECT
                    `field_filter`,
                    `field_list`,
                    `field_detail`
                FROM
                    `module_isic_card_type`
                WHERE
                    `id` = !
            ", $this->card_type_current);
            if ($data = $res->fetch_assoc()) {
                if ($data["field_filter"]) {
                    $ct_filter = explode(",", $data["field_filter"]);
                }
                if ($data["field_list"]) {
                    $ct_list = explode(",", $data["field_list"]);
                }
                if ($data["field_detail"]) {
                    $ct_detail = explode(",", $data["field_detail"]);
                }
            }
        }

        // fields for listview
        $res =& $this->db->query("
            SELECT
                `module_isic_card_field`.*,
                `module_isic_card_field_list`.`translation` AS `list_translation`,
                `module_isic_card_field_list`.`hidden`,
                `module_isic_card_field_list`.`sortable`
            FROM 
                `module_isic_card_field`,
                `module_isic_card_field_list` 
            WHERE
                `module_isic_card_field`.`id` = `module_isic_card_field_list`.`field_id`
            ORDER BY 
                `module_isic_card_field_list`.`sort_order`
        ");
        while ($data = $res->fetch_assoc()) {
            if ($data["list_translation"]) {
                $data["translation"] = $data["list_translation"];
                unset($data["list_translation"]);
            }
            if (!$ct_list || is_array($ct_list) && in_array($data["id"], $ct_list)) {
                $result["listview"][$data["field"]] = $data;
            }
        }

        // fields for filterview
        $res =& $this->db->query("
            SELECT
                `module_isic_card_field`.*,
                `module_isic_card_field_filter`.`translation` AS `filter_translation`
            FROM 
                `module_isic_card_field`,
                `module_isic_card_field_filter` 
            WHERE
                `module_isic_card_field`.`id` = `module_isic_card_field_filter`.`field_id`
            ORDER BY 
                `module_isic_card_field_filter`.`sort_order`
        ");
        while ($data = $res->fetch_assoc()) {
            if ($data["filter_translation"]) {
                $data["translation"] = $data["filter_translation"];
                unset($data["filter_translation"]);
            }
            if (!$ct_filter || is_array($ct_filter) && in_array($data["id"], $ct_filter)) {
                $result["filterview"][$data["field"]] = $data;
            }
        }

        // fields for detailview
        $res =& $this->db->query("
            SELECT 
                `module_isic_card_field`.*,
                `module_isic_card_field_detail`.`translation` AS `deatil_translation`,
                `module_isic_card_field_detail`.`fieldset_id`,
                `module_isic_card_field_detail`.`required`,
                `module_isic_card_field_detail`.`disabled`,
                `module_isic_card_field_detail`.`tooltip`
            FROM 
                `module_isic_card_field`,
                `module_isic_card_field_detail`,
                `module_isic_card_fieldset`
            WHERE
                `module_isic_card_field`.`id` = `module_isic_card_field_detail`.`field_id` AND
                `module_isic_card_field_detail`.`fieldset_id` = `module_isic_card_fieldset`.`id`
            ORDER BY 
                `module_isic_card_fieldset`.`column_order`,
                `module_isic_card_fieldset`.`sort_order`,
                `module_isic_card_fieldset`.`name`,
                `module_isic_card_field_detail`.`sort_order`
        ");
        while ($data = $res->fetch_assoc()) {
            if ($data["detail_translation"]) {
                $data["translation"] = $data["detail_translation"];
                unset($data["detail_translation"]);
            }
            $data['tooltip'] = nl2br(stripslashes($data['tooltip'] ? $txt->display($data['tooltip']) : ''));
            $data["required"] = explode(",", $data["required"]);
            $data["disabled"] = explode(",", $data["disabled"]);
            if (!$ct_detail || is_array($ct_detail) && in_array($data["id"], $ct_detail)) {
                $result["detailview"][$data["field"]] = $data;
            }
        }

        return $result;
    }

    function getColumnList()
    {
        $columns = array(
            0 => array(
                'width' => 290,
                'labelWidth' => 100,
            ),
            1 => array(
                'width' => 290,
                'labelWidth' => 100,
            ),
            2 => array(
                'width' => 330,
                'labelWidth' => 100,
            )
        );
        return JsonEncoder::encode($columns);
    }

    function getFieldSetList()
    {
        $txt = new Text($this->language, $this->translation_module_default);
        $res =& $this->db->query("SELECT * FROM `module_isic_card_fieldset` ORDER BY `column_order`, `sort_order`, `name`");
        $result = array();
        $col_count = 0;
        $prev_col = 0;
        while ($data = $res->fetch_assoc()) {
            if (!$prev_col) {
                $prev_col = $data["column_order"];
            } elseif ($prev_col != $data["column_order"]) {
                $col_count++;
                $prev_col = $data["column_order"];
            }
            $result[$col_count][] = array(
                'legend' => $data["translation"] ? $txt->display($data["translation"]) : $data["name"],
                'fieldset_id' => $data['id']
            );
        }
        return $result;
    }

    /**
     * Creates json-encoded list of fields for Ext elements (grid, form elements, data store etc.)
     *
     * @param array $field_data - array with all the field parameters for building the needed data structure
     * @param string $type - type of the list that should be created (grid_column, filters, list)
     * @return string json-encoded data
    */

    function getFieldList($field_data = false, $type = 'list', $fs_data = false)
    {
        if ($field_data) {
            $txt = new Text($this->language, $this->translation_module_default);
            $result = array();
            $fs_count = 0;
            $prev_fs = 0;
            foreach ($field_data as $data) {
                switch ($type) {
                    case 'grid_column':
                        $result[] = array(
                            'header' => $txt->display($data['translation'] ? $data['translation'] : $data['field']),
                            'dataIndex' => $data['field'],
                            'sortable' => $data['sortable'] ? true : false,
                            'hidden' => $data['hidden'] ? true : false
                        );
                    break;
                    case 'filters':
                        switch ($data['type']) {
                            case 1: // textfield
                                $result[] = array(
                                    'name' => $data['field'],
                                    'field_title' => $txt->display($data['translation'] ? $data['translation'] : $data['field']),
                                    'type' => 'textfield'
                                );
                            break;
                            case 2: // combobox
                                $result[] = array(
                                    'name' => $data['field'],
                                    'field_title' => $txt->display($data['translation'] ? $data['translation'] : $data['field']),
                                    'type' => 'combobox',
                                    'emptyText' => $this->getFieldRelationAllPhrase($data['relation_table']),
                                    'ds_fields' => array(
                                        'id',
                                        'name'
                                    ),
                                    'ds_data' => array(
                                        0 => '***ASSOC_ARRAY***',
                                        1 => $this->getFieldRelationList($data['relation_table'], true)
                                    )
                                );
                            break;
                            case 3: // checkbox
                                // todo ?
                            break;  
                            case 5: // date
                                $result[] = array(
                                    'name' => $data['field'],
                                    'field_title' => $txt->display($data['translation'] ? $data['translation'] : $data['field']),
                                    'type' => 'textfield'
                                );
                            break;
                            default:
                            break;
                        }
                    break;
                    case 'detail':
                        switch ($data['type']) {
                            case 1: // textfield
                                $result[$data["fieldset_id"]][] = array(
                                    'type' => 'textfield',
                                    'name' => $data['field'],
                                    'tooltip' => $data['tooltip'],
                                    'field_title' => $txt->display($data['translation'] ? $data['translation'] : $data['field'])
                                );
                            break;
                            case 2: // combobox
                                $result[$data["fieldset_id"]][] = array(
                                    'name' => $data['field'],
                                    'tooltip' => $data['tooltip'],
                                    'field_title' => $txt->display($data['translation'] ? $data['translation'] : $data['field']),
                                    'type' => 'combobox',
                                    //'emptyText' => $this->getFieldRelationAllPhrase($data['relation_table']),
                                    'ds_fields' => array(
                                        'id',
                                        'name'
                                    ),
                                    'ds_data' => array(
                                        0 => '***ASSOC_ARRAY***',
                                        1 => $this->getFieldRelationList($data['relation_table'], false)
                                    )
                                );
                            break;
                            case 3: // checkbox
                                $result[$data["fieldset_id"]][] = array(
                                    'type' => 'checkbox',
                                    'name' => $data['field'],
                                    'tooltip' => $data['tooltip'],
                                    'field_title' => $txt->display($data['translation'] ? $data['translation'] : $data['field'])
                                );
                            break;
                            case 4: // file
                                $result[$data["fieldset_id"]][] = array(
                                    'type' => 'pic',
                                    'name' => $data['field']
                                );
                            break;
                            case 5: // date
                                $result[$data["fieldset_id"]][] = array(
                                    'type' => 'datefield',
                                    'name' => $data['field'],
                                    'tooltip' => $data['tooltip'],
                                    'field_title' => $txt->display($data['translation'] ? $data['translation'] : $data['field'])
                                );
                            break;
                            default:
                            break;
                        }
                    break;
                    default :
                        $result[] = array('name' => $data["field"]);
                    break;
                }
            }

            if ($type == 'detail' && $fs_data) {
                $t_result = array();
                for ($i = 0; $i < sizeof($fs_data); $i++) {
                    for ($j = 0; $j < sizeof($fs_data[$i]); $j++) {
                        if (!array_key_exists($fs_data[$i][$j]['fieldset_id'], $result)) {
                            $result[$fs_data[$i][$j]['fieldset_id']] = false;
                        }
                        $t_result[$i][$j] = $result[$fs_data[$i][$j]['fieldset_id']];
                    }
                }
                $result = $t_result;
            }
        }
        return JsonEncoder::encode($result);
    }

    /**
     * Creates array for fields that are relation to other tabels (school data for example)
     *
     * @param string $relation_table - db-table to query data from
     * @param boolean $show_all_title - wether the all title shold be shown or not in combobox
     * @return array of field relation data
    */

    function getFieldRelationList($relation_table = '', $show_all_title = true) {
        $txt = new Text($this->language, $this->translation_module_default);
        $result = array();
        switch ($relation_table) {
            case 'module_isic_school':
                $allowed_ids = $this->allowed_schools;
            break;
            case 'module_isic_card_type':
                if ($this->card_type_current) {
                    $allowed_ids = array_intersect($this->allowed_card_types, array($this->card_type_current));
                } else {
                    $allowed_ids = $this->allowed_card_types;
                }
            break;
            default :
                $allowed_ids = false;
            break;
        }
        $all_title = $this->getFieldRelationAllPhrase($relation_table);

        if ($allowed_ids) {
            $res =& $this->db->query("SELECT * FROM ?f WHERE `id` IN (!@) ORDER BY `name`", $relation_table, $allowed_ids);
        } else {
            $res =& $this->db->query("SELECT * FROM ?f ORDER BY `name`", $relation_table);
        }

        if ($show_all_title) {
            $result[0] = $all_title;
        }
        while ($data = $res->fetch_assoc()) {
            $result[$data["id"]] = $data["name"];
        }
        return $result;
    }

    /**
     * Helper method for getting correct translation phrase for relation-fields e.g. 'all schools'
     *
     * @param string $table_name - name of the table that is used to determine which translation phrase to use
     * @return string translation phrase
    */

    function getFieldRelationAllPhrase($table_name = '')
    {
        $txt = new Text($this->language, $this->translation_module_default);
        switch ($table_name) {
            case 'module_isic_school':
                return $txt->display('all_schools');
            break;
            case 'module_isic_card_type':
                return $txt->display('all_types');
            break;
            default:
                return '';
            break;
        }
    }

    /**
     * Creates actual json-encoded list of card-recors for the Ext grid-element
     *
     * @return will output json-encoded string and terminate the execution of the script
    */

    function getCardList()
    {
        $txt = new Text($this->language, $this->translation_module_default);

        // sorting order of the query
        if (!$this->vars["sort"]) {
            $sort_order = 'person_name';
        } else {
            $sort_order = $this->vars["sort"];
        }

        if (!$this->vars["dir"]) {
            $sort_dir = "ASC";
        } else {
            $sort_dir = $this->vars["dir"];
        }

        // amount of records to query at once
        if (!$this->vars["start"]) {
            $start_row = 0;
        } else {
            $start_row = $this->vars["start"];
        }

        if (!$this->vars["limit"]) {
            $max_rows = $this->maxresults;
        } else {
            $max_rows = $this->vars["limit"];
        }

        $condition = array();
        if ($this->card_type_current) {
            $condition[] = "`module_isic_card`.`type_id` = " . $this->card_type_current;
        }
        // filter conditions
        foreach ($this->fieldData['filterview'] as $filter_data) {
            if ($this->vars[$filter_data['field']]) {
                switch ($filter_data['type']) {
                    case 1: // textfield
                        $condition[] = $filter_data['field'] . " LIKE '%" . mysql_escape_string($this->vars[$filter_data['field']]) . "%'";
                    break;
                    case 2: // combobox
                        $condition[] = $filter_data['field'] . " = " . mysql_escape_string($this->vars[$filter_data['field']]);
                    break;
                    default :
                    break;
                }
            }
        }

        $sql_condition = implode(" AND ", $condition);
        if ($sql_condition) {
            $sql_condition = "AND " . $sql_condition;
        }

        $res =& $this->db->query("
                SELECT
                    `module_isic_card`.*,
                    IF(`module_isic_school`.`id`, `module_isic_school`.`name`, '') AS school_name,
                    IF(`module_isic_card_kind`.`id`, `module_isic_card_kind`.`name`, '') AS card_kind_name,
                    IF(`module_isic_bank`.`id`, `module_isic_bank`.`name`, '') AS bank_name,
                    IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name
                FROM
                    `module_isic_card`
                LEFT JOIN
                    `module_isic_school` ON `module_isic_card`.`school_id` = `module_isic_school`.`id`
                LEFT JOIN
                    `module_isic_card_kind` ON `module_isic_card`.`kind_id` = `module_isic_card_kind`.`id`
                LEFT JOIN
                    `module_isic_bank` ON `module_isic_card`.`bank_id` = `module_isic_bank`.`id`
                LEFT JOIN
                    `module_isic_card_type` ON `module_isic_card`.`type_id` = `module_isic_card_type`.`id`
                WHERE
                    `module_isic_card`.`school_id` IN (!@) AND
                    `module_isic_card`.`type_id` IN (!@)
                    ! 
                ORDER BY ! ! 
                LIMIT !, !", 
                $this->allowed_schools,
                $this->allowed_card_types,
                $sql_condition, 
                $sort_order, 
                $sort_dir, 
                $start_row, 
                $max_rows
        );
//echo($this->db->show_query());
        $result = array();
        while ($data = $res->fetch_assoc()) {
            $t_pic = str_replace(".jpg", "_thumb.jpg", $data["pic"]);
            if ($t_pic && file_exists(SITE_PATH . $t_pic)) {
                $data["pic"] = "<img src=\"" . $t_pic . "\">";
            }
            $data["pic"] .= "<img src=\"img/tyhi.gif\" height=\"100\" width=\"1\">";
            $data["person_birthday"] = date("d/m/Y", strtotime($data["person_birthday"]));
            $data["expiration_date"] = date("m/y", strtotime($data["expiration_date"]));
            $data["active"] = $txt->display("active" . $data["active"]);
            $data["confirm_payment_collateral"] = $this->isic_payment->getCardCollateralRequired($data["school_id"], $data["type_id"]) ? $txt->display("active" . $data["confirm_payment_collateral"]) : "-";
            $data["confirm_payment_cost"] = $this->isic_payment->getCardCostRequired($data["school_id"], $this->isic_common->getCardStatus($data["prev_card_id"]), $data["type_id"], $is_card_first) ? $txt->display("active" . $data["confirm_payment_cost"]) : "-";
            $result[] = $data;
        }

        $res =& $this->db->query("
            SELECT COUNT(*) AS total 
            FROM 
                `module_isic_card` 
            WHERE
                `module_isic_card`.`school_id` IN (!@) AND
                `module_isic_card`.`type_id` IN (!@)
                ! 
            ", 
            $this->allowed_schools,
            $this->allowed_card_types,
            $sql_condition);
        if ($data = $res->fetch_assoc()) {
            $total = $data["total"];
        }
            
        echo JsonEncoder::encode(array('total' => $total, 'rows' => $result));
        exit();
    }

    /**
     * Creates json-encoded string for the detail-view of a single card
     *
     * @return json-encoded string will be outputed and script exited
    */

    function getCardDetail()
    {
        $disabled = array();
        $required = array();
        
        if ($this->vars["card_id"]) {
            $action = 2; // modify
            // find all the disabled fields for current action
            foreach ($this->fieldData["detailview"] as $dkey => $dval) {
                if (in_array($action, $dval["disabled"])) {
                    $disabled[] = $dkey;
                }
            }
            // find all the required fields for current action
            foreach ($this->fieldData["detailview"] as $rkey => $rval) {
                if (in_array($action, $rval["required"])) {
                    $required[] = $rkey;
                }
            }

            $res =& $this->db->query("SELECT * FROM `module_isic_card` WHERE `id` = !", $this->vars["card_id"]);
            $result = array();
            if ($data = $res->fetch_assoc()) {
                $t_pic = str_replace("_thumb.jpg", ".jpg", $data["pic"]);
                if (file_exists(SITE_PATH . $t_pic)) {
                    $data["pic"] = $t_pic;
                } else {
                    $data["pic"] = "";
                }
                $data["person_birthday"] = date("d/m/Y", strtotime($data["person_birthday"]));
                $data["expiration_date"] = date("d/m/Y", strtotime($data["expiration_date"]));
                $data["status_id"] = $data["status_id"] ? $data["status_id"] : '';
                $data["bank_id"] = $data["bank_id"] ? $data["bank_id"] : '';
                $result[] = $data;
                echo JsonEncoder::encode(array('success' => true, 'data' => $result, 'disable' => $disabled, 'require' => $required));
            } else {
                echo JsonEncoder::encode(array('error' => 'cold not load data'));
            }
        } else {
            $action = 1; // add
            // find all the disabled fields for current action
            foreach ($this->fieldData["detailview"] as $dkey => $dval) {
                if (in_array($action, $dval["disabled"])) {
                    $disabled[] = $dkey;
                }
            }
            // find all the required fields for current action
            foreach ($this->fieldData["detailview"] as $rkey => $rval) {
                if (in_array($action, $rval["required"])) {
                    $required[] = $rkey;
                }
            }
            $result[] = array("pic" => "");
            echo JsonEncoder::encode(array('success' => true, 'data' => $result, 'disable' => $disabled, 'require' => $required));
        }
        exit();
    }

    /**
     * Saves card-data submitted by user into DB
     *
     * @return outputs json-encoded result data and terminates script exection.
    */

    function saveCard()
    {
        $table = 'module_isic_card';
        $card = $this->vars["card_id"];
        $this->convertValueFieldKeys();
        //print_r($this->vars);
        // there are 2 possibilites for saving card (modify existing or add new)
        if ($card) { // modify existing card

            $row_old = $this->isic_common->getCardRecord($card);
            $t_field_data = $this->getFieldData($row_old["type_id"]);


            $r = &$this->db->query('
                UPDATE
                    `module_isic_card`
                SET
                    `module_isic_card`.`moddate` = NOW(),
                    `module_isic_card`.`moduser` = ?,
                    `module_isic_card`.`person_name` = ?,
                    `module_isic_card`.`person_addr1` = ?,
                    `module_isic_card`.`person_addr2` = ?,
                    `module_isic_card`.`person_addr3` = ?,
                    `module_isic_card`.`person_addr4` = ?,
                    `module_isic_card`.`person_email` = ?,
                    `module_isic_card`.`person_phone` = ?,
                    `module_isic_card`.`person_position` = ?,
                    `module_isic_card`.`person_class` = ?,
                    `module_isic_card`.`person_stru_unit` = ?,
                    `module_isic_card`.`person_bankaccount` = ?,
                    `module_isic_card`.`person_bankaccount_name` = ?,
                    `module_isic_card`.`person_newsletter` = ?,
                    `module_isic_card`.`confirm_user` = !,
                    `module_isic_card`.`confirm_payment_collateral` = !,
                    `module_isic_card`.`confirm_payment_cost` = !,
                    `module_isic_card`.`confirm_admin` = !
                WHERE
                    `module_isic_card`.`id` = !
                ',  $this->userid,
                    $this->vars["person_name"],
                    $this->vars["person_addr1"],
                    $this->vars["person_addr2"],
                    $this->vars["person_addr3"],
                    $this->vars["person_addr4"],
                    $this->vars["person_email"],
                    $this->vars["person_phone"],
                    $this->vars["person_position"],
                    $this->vars["person_class"],
                    $this->vars["person_stru_unit"],
                    $this->vars["person_bankaccount"],
                    $this->vars["person_bankaccount_name"],
                    $this->vars["person_newsletter"] ? 1 : 0,
                    $this->vars["confirm_user"] ? 1: 0,
                    $this->user_type == 1 ? ($this->vars["confirm_payment_collateral"] ? 1 : 0) : $row_old["confirm_payment_collateral"],
                    $this->user_type == 1 ? ($this->vars["confirm_payment_cost"] ? 1 : 0) : $row_old["confirm_payment_cost"],
                    $this->user_type == 1 ? ($this->vars["confirm_admin"] ? 1 : 0) : $row_old["confirm_admin"],
                    $card
            );
            if ($r) {
                $success = true;
                $this->isic_common->saveCardChangeLog(2, $card, $row_old, $this->isic_common->getCardRecord($card));
                $message = 'card saved ...';
            } else {
                $success = false;
                $message = 'card modify failed ...';
            }

        } else { // adding new card
            $success = false;
            $action = 1; // add
            $t_field_data = $this->getFieldData($this->vars["type_id"]);
            //print_r($t_field_data);
            foreach ($t_field_data["detailview"] as $fkey => $fval) {
                // check for disabled fields, setting these values to empty
                if (in_array($action, $fval["disabled"])) {
                    unset($this->vars[$fkey]);
                    continue;
                }
                // check for requried fields
                if (in_array($action, $fval["required"])) {
                    if (!$this->vars[$fkey]) {
                        $error = $error_required_fields = true;
                        break;
                    }
                }
                if (!$error) {
                    $insert_fields[] = $this->db->quote_field_name("{$fkey}");
                    $t_value = '';
                    switch ($fval['type']) {
                        case 1: // textfield
                            $t_value = $this->db->quote($this->vars[$fkey] ? $this->vars[$fkey] : '');
                        break;
                        case 2: // combobox
                            $t_value = $this->vars[$fkey] ? $this->vars[$fkey] : 0;
                        break;
                        case 3: // checkbox
                            $t_value = $this->vars[$fkey] ? 1 : 0;
                        break;
                        case 5: // date
                            $t_date = $this->convertDate($this->vars[$fkey]);
                            $t_value = $this->db->quote($t_date);
                        break;
                        default :
                        break;
                    }
                    $insert_values[] = $t_value;
                }
            }
            if (!$error) {
                $r = &$this->db->query('INSERT INTO ' . $this->db->quote_field_name($table) . ' FIELDS (' . implode(',', $insert_fields) . ') VALUES (' . implode(',', $insert_values) . ')');
                echo "<!-- " . $this->db->show_query() . " -->\n";
                $card = $this->db->insert_id();

            }

            if ($r && $card) {
                $success = true;
                $this->isic_common->saveCardChangeLog(1, $card, array(), $this->isic_common->getCardRecord($card));
                $message = 'new card saved ...';
            } else {
                $success = false;
                $message = 'card add failed ...';
            }
        }

        echo JsonEncoder::encode(array('success' => $success, 'msg' => $message));
        exit();
        
    }


    /**
     * Converts all of the values from comboboxes with names like schoold_id_value into school_id 
     * by removing the "_value" from the end of the key and also removing the existing key
     *
     * @access private
     * @return boolean
     */
    
    function convertValueFieldKeys()
    {
        foreach ($this->vars as $key => $val) {
            if (substr($key, -6) == '_value') {
                $this->vars[substr($key, 0, -6)] = $val;
                unset($this->vars[$key]);
            }
        }
    }


    function convertDate($date)
    {
        return substr($date, 6, 4) . '-' . substr($date, 3, 2) . '-' . substr($date, 0, 2);
    }

    /**
     * Check does the active user have access to the page/form
     *
     * @access private
     * @return boolean
     */

    function checkAccess () {
        if ($GLOBALS["pagedata"]["login"] == 1) {
            if ($this->userid && $GLOBALS["user_show"] == true) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return true;
        }
    }

    /**
     * Returns module parameters array
     *
     * @return array module parameters
    */

    function getParameters() {
        $ar = split(";", $GLOBALS["pagedata"]["module"]);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $this->module_param[$a[0]] = $a[1];
        }
    }

    /**
     * Creates array of module parameter values for content admin
     *
     * @return array module parameters
    */

    function moduleOptions() {
        $sq = new sql;
        $txt = new Text($this->language, "module_isic_card");

        $list = array();
        $list[""] = $txt->display("choose_card_type");
        $res =& $this->db->query("SELECT * FROM `module_isic_card_type` ORDER BY `name`");
        while ($data = $res->fetch_assoc()) {
            $list[$data["id"]] = $data["name"];
        }

        /*
        $list = array();
        $list[""] = $txt->display("choose_form_type");
        $list[1] = $txt->display("form_type1");
        $list[2] = $txt->display("form_type2");
        $list[3] = $txt->display("form_type3");
        */
        // ####
        return array($txt->display("card_type"), "select", $list);
        // name, type, list
    }
}
