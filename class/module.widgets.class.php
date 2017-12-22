<?php
/**
 * @version $Revision: 1129 $
 */
require_once(SITE_PATH . "/class/JsonEncoder.php");

/**
 * Widgets class
 *
 * @author Victor Buzyka <victor@itworks.biz.ua>
 * @package modera.net
 * @access public
 */
class widgets
{
    /**
     * @var object database
     */
    var $db;

    /**
     * Widget table name
     *
     * @var string
     */
    var $table = "module_widgets";

    /**
     * Constructor
     */
    function widgets()
    {
        global $db, $language;

        if (isset($GLOBALS['database'])) {
            $this->db =& $GLOBALS['database'];
        } else {
            if (is_object($db)) {
                $this->dbc = $db->con;
            } else {
                $db = new DB;
                $this->dbc = $db->connect();
                $sq = new sql();
                $sq->con = $this->dbc;
            }
            // initialize higher level database class
            $this->db = new Database($sq);
        }
    }

    /**
     * Get widgets list
     *
     * @param string $filter filter
     * @param string $sort_field sorting field name
     * @param string $dir sorting direction
     * @return array
     */
    function getList($filter="", $sort_field="name", $dir="asc")
    {
        $list = array();
        if (strtolower($dir) != 'asc') {
            $dir = 'desc';
        }
        if ($sort_field == '') {
            $sort_field = 'name';
        }
        $sort_field = $this->db->quote_field_name($sort_field);
        if ($filter != '') {
            $filter = $this->db->quote($filter);
            $filter = '%'.substr($filter, 1, strlen($filter)-2).'%';
            $sql = "SELECT id, name, content FROM `" . $this->table
                . "` WHERE `name` LIKE '$filter' OR `content` LIKE '$filter'"
                . " ORDER BY $sort_field $dir";
        } else {
            $sql = "SELECT id, name, content FROM `" . $this->table
                . "` ORDER BY $sort_field $dir";
        }
        $res = $this->db->query($sql);
        if (!$res) {
            return $list;
        }
        while ($row = $res->fetch_assoc()) {
            $row['name'] = htmlspecialchars($row['name']);
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Get widget by widget id
     *
     * @param int $id
     * @return mixed
     */
    function getWidgetById($id)
    {
        $sql = "SELECT id, name, content FROM `" . $this->table
            . "` WHERE id = ?";
        $res = $this->db->query($sql, $id);
        if (!$res) {
            return false;
        }
        return $res->fetch_assoc();
    }

    /**
     * Save widget data
     *
     * @param string $name widget name
     * @param string $content widget content
     * @param int $id widget id (for widget update) or empty for add widget
     * @return boolean
     */
    function save($name, $content, $id='')
    {
        if (intval($id) > 0) {
            $sql = "UPDATE `" . $this->table . "` SET `name` = ?, `content` = ?"
                . "WHERE id = ?";
            return $this->db->query($sql, $name, $content, intval($id));
        } else {
            // add new widget
            $sql = "INSERT INTO `" . $this->table . "` (`id`, `name`, `content`)"
                . "VALUES ('', ?, ?)";
            return $this->db->query($sql, $name, $content);
        }
    }

    /**
     * Delete widget by id
     *
     * @param int $id widget id
     * @return boolean
     */
    function delete($id)
    {
        $id = intval($id);
        if ($id > 0) {
            $sql = "DELETE FROM `" . $this->table . "` WHERE id = ?";
            return $this->db->query($sql, $id);
        }
        return false;
    }

    /**
     * Return widgets list in WYSIWYG Editor format
     *
     * @return string
     */
    function getWidgetsForEditor()
    {
        $res = '';
        $tmp = $this->getList();
        if (!empty($tmp)){
            foreach ($tmp as $t) {
                $name = (is_numeric($t['name']))? '"' . $t['name']
                    . '"' : JsonEncoder::encode($t['name']);
                $content = (is_numeric($t['content'])) ? '"' . $t['content']
                    . '"' : JsonEncoder::encode($t['content']);
                if ($res != ''){
                    $res .= ",";
                }
                $res .= '[' . $name . ', ' . $content . ']';
            }
        }
        return $res;
    }
}