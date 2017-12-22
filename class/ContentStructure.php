<?php
/**
 * @version $Revision: 908 $
 */

/**
 * Modera menu structure class
 *
 * Class for working with Modera structured menu. Menu is based on materialized
 * path. This menu is backward compatible with old one, pages are still identified
 * by their numeric id numbers (`content` field). Items structure is stored as
 * materialized path inside `mpath` field. Path glue is dot symbol.
 *
 * Materialized path is stored in varchar(255) database field, dot is used as
 * separator. Page id is unsigned int(4) value, maximum value has 10 symbols
 * length. Minimum depth can be calculated with this formula floor(255/(10+1) -1)
 * and equals to 22 levels.
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 * @author Priit Pyld <priit.pold@modera.net>
 */
class ContentStructure
{
    /**
     * Content table name
     *
     * @var string
     * @access protected
     */
    var $_tbl_name = 'content';

    /**
     * Starting value for zort fields
     *
     * For backward compatibility we have to use this number as starting
     * value for `zort` field
     *
     * @var int
     * @access protected
     */
    var $_start_zort = 10000;

    /**
     * Database instance
     *
     * @var Database
     * @access protected
     */
    var $_db;

    /**
     * @param Database $db
     * @return Structure
     */
    function ContentStructure(&$db)
    {
        $this->_db =& $db;
    }

    /**
     * Get site structure as XML string
     *
     * @param string $language Language for which menu will be returned
     * @param bool $all_visible if TRUE than all visible items will be returned
     *  even those with `menu` set to 0
     * @param mixed $show_disabled can have several values
     *             bool true - show all pages regardless of their visibility status
     *             bool false - not show
     *             string 'val' - not visible status name each nede display
     *             array(string, string ... string) - array with not visible status
     *          Not visible status list:
     *                 not publised - not published pages
     *                 expired - expired pages
     * @return string site structure as XML string
     */
    function getAsXml($language, $all_visible = false, $show_disabled = false, $show_not_published = false)
    {
        require_once(SITE_PATH . '/class/aliases_helpers.php');
        // Show disabled set to this function format
        if (is_string($show_disabled)){
            $show_disabled = array($show_disabled);
        }
        $allowed_groups = $GLOBALS["user_data"][5];
        $allowed_user_type = $GLOBALS["user_data"][6];
        $not_visible = array();
        $not_in_menu = array();
//        $sql = "UPDATE `" . $this->_tbl_name . "` SET `visible`=0
//                WHERE  `visible` != 0
//                AND (`publishing_date` > NOW()
//                OR (UNIX_TIMESTAMP(`expiration_date`) > 0
//                AND  `expiration_date` < NOW()))";
//        $this->_db->query($sql);
//        $sql = "UPDATE `" . $this->_tbl_name . "` SET `visible`=1, `is_published`=1
//                WHERE  `visible` = 0
//                AND `is_published` = 0
//                AND `publishing_date` <= NOW()
//                AND (UNIX_TIMESTAMP(`expiration_date`) <= 0
//                OR  `expiration_date` > NOW())";
//        $this->_db->query($sql);
        $tree = array();
        $result = &$this->_db->query('
            SELECT
                `content`
                , `title`
                , `uri_alias`
                , `menu`
                , `mpath`
                , `lead`
                , `visible`
                , `pending`
                , `new_window`
                , `is_published`
                , `login`
                , `logingroups`
                , `loginusertypes`
                , UNIX_TIMESTAMP(`expiration_date`) as expiration_date
                , UNIX_TIMESTAMP(`publishing_date`) as publishing_date
            FROM
                ?f
            WHERE
                `language` = ?
            ORDER BY
                `mpath` ASC,
                `zort` ASC
            ', $this->_tbl_name, $language);
        while ($row = $result->fetch_assoc()) {
            // check if current page is not visible OR is pending creation
            // or this is a child element of some not visible page.
            if ($show_disabled !== true) {
                if (!$row['visible'] || $row['pending'] == MODERA_PENDING_CREATION
                    || in_array($row["mpath"], $not_visible))
                {
                    $tmp_visible = false;
                    if (is_array($show_disabled)){
                        foreach ($show_disabled as $s){
                            switch (strtolower($s)){
                                case 'not published':
                                        if ($row['is_published'] == 0) {
                                            $tmp_visible = true;
                                        }
                                        break;
                                case 'expired':
                                        if ($row['expiration_date'] < time()) {

                                        }
                                        break;
                            }
                        }
                    }
                    if (!$tmp_visible){
                        $not_visible[] = $row['mpath'] ? ($row['mpath'] . '.' . $row['content'])
                            : $row['content'];
                        continue;
                    }
                }
            }

            if (!$all_visible) {
                if ($row['login']) {
                    $t_lgrp = array_intersect(explode(',', $row['logingroups']), $allowed_groups);
                    $t_lut = in_array($allowed_user_type, explode(',', $row['loginusertypes']));

                    if ($row['logingroups'] && $row['loginusertypes']) {
                        $show_in_menu = $t_lgrp && $t_lut;
                    } elseif ($row['logingroups']) {
                        $show_in_menu = $t_lgrp;
                    } elseif ($row['loginusertypes']) {
                        $show_in_menu = $t_lut;
                    } else {
                        $show_in_menu = false;
                    }
                } else {
                    $show_in_menu = true;
                }

                //$show_in_menu = (!$row['login'] || $row['login'] && (array_intersect(explode(',', $row['logingroups']), $allowed_groups) || in_array($allowed_user_type, explode(',', $row['loginusertypes']))));

                if (!$row['menu'] || !$show_in_menu || in_array($row["mpath"], $not_in_menu)) {
                    $not_in_menu[] = $row['mpath'] ? ($row['mpath'] . '.' . $row['content'])
                        : $row['content'];
                    continue;
                }
            }

            $node =& $tree;
            if ($row['mpath']) {
                foreach (explode('.', $row['mpath']) as $node_id) {
                    if ($node === $tree) {
                        $node = &$node[$node_id];
                    } else {
                        $node = &$node['children'][$node_id];
                    }
                }
            }

            if ($node === $tree) {
                $node = &$node[$row['content']];
            } else {
                if (isset($node['data']['publishing_date']) && $node['data']['publishing_date'] > time() && $node['data']['publishing_date'] > $row['publishing_date']){
                    $row['publishing_date'] = $node['data']['publishing_date'];
                }
                $node = &$node['children'][$row['content']];
            }

            //edit link...
            $_pos = (FALSE !== strpos(SITE_URL, "https"))? 8 : 7;

            if (strpos(SITE_URL, "/", $_pos) === false) {
                $engine_url = "/";
            } else {
                $engine_url = substr(SITE_URL, strpos(SITE_URL, "/", $_pos)) . "/";
            }

            if ($GLOBALS['site_settings']['niceurls'] && strlen($row['uri_alias']) > 0)
            {
                list($link) = dispatch_aliases($row['uri_alias']);
                $link = (('/' == substr($link, 0, 1)) ? substr($engine_url, 0, -1) : $engine_url) . $link;
            } else {
                $link = $engine_url."?content=".$row["content"];
            }
            $row['link'] = $link;

            // append current item id to mpath to simplify xslt processing
            $row['mpath'] .= ($row['mpath'] ? '.' : '') . $row['content'];
            $node = array('data' => $row);
            unset($node);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= "<menu>";
        foreach ($tree as $node) {
            $xml .= ContentStructure::_node2Xml($node);
        }

        $xml .= "</menu>";

        return $xml;
    }

    /**
     * Recursive function used for converting content structure tree into XML
     *
     * @param array $node
     * @return string
     * @static
     * @access private
     */
    function _node2Xml($node)
    {
        $data = $node['data'];
        $xml = '<item'
            . ' first="0"'
            . ' link="' . $data['link'] . '"'
            . ' structure="' . $data['mpath'] . '"'
            . ' content="' . $data['content'] .'"'
            . ' menu="' . $data['menu']. '"'
            . ' new_window="' . $data['new_window'] .'"'
            . '>';

        $xml .= '<name>' . validXML($data['title']) . '</name>';
        $xml .= '<nameenc>' . addslashes(urlencode($data['title'])) . '</nameenc>';
        $xml .= '<lead>' . validXML($data['lead']) . '</lead>';
        $data['publishing_date'] = $data['publishing_date'] > time() ? date('d/m/Y', $data['publishing_date']) : '';
        $xml .= '<publishing>' . validXML($data['publishing_date']) . '</publishing>';

        if (isset($node['children'])) {
            foreach ($node['children'] as $child) {
                $xml .= ContentStructure::_node2Xml($child);
            }
        }

        $xml .= '</item>';
        return $xml;
    }

    /**
     * Create new node as sibling node after node specified by id
     *
     * @param array $node associative array with node data, array keys are mapped
     *  to content table fields
     * @param int $ref_node_id ID of node after which new node will be created
     * @return int|FALSE new node ID, FALSE if node was not created
     */
    function createNodeBelow($node, $ref_node_id)
    {
        return $this->_createNode($node, $ref_node_id, 'below');
    }

    /**
     * Create new node as sibling node before node specified by id
     *
     * @param array $node associative array with node data, array keys are mapped
     *  to content table fields
     * @param int $ref_node_id ID of node before which new node will be created
     * @return int|FALSE new node ID, FALSE if node was not created
     */
    function createNodeAbove($node, $ref_node_id)
    {
        return $this->_createNode($node, $ref_node_id, 'above');
    }

    /**
     * Create new node as last child under another node
     *
     * @param array $node associative array with node data, array keys are mapped
     *  to content table fields
     * @param int $ref_node_id ID of node after which new node will be created
     * @return int|FALSE new node ID, FALSE if node was not created
     */
    function createNodeUnder($node, $ref_node_id)
    {
        // construct materialized path for new node
        if (0 != $ref_node_id) {
            $mpath = $this->childrenMpath($ref_node_id);
            if (!$mpath) {
                return false;
            }
        } else {
            // first level node is created
            $mpath = '';
        }

        $node['zort'] = $this->_start_zort + $this->_nodesCount($mpath);
        $node['mpath'] = $mpath;
        $result = $this->_db->query('INSERT INTO ?f (?@f) VALUES (?@)'
            , $this->_tbl_name, array_keys($node), $node);

        if ($result) {
            return $this->_db->insert_id();
        } else {
           return false;
        }
    }

    /**
     * Moves one node after another
     *
     * @param int $node_id ID of node that will be moved
     * @param int $ref_node_id
     * @return bool TRUE if node was moved successfully, FALSE otherwise
     */
    function moveNodeBelow($node_id, $ref_node_id)
    {
        return $this->_moveNode($node_id, $ref_node_id, 'below');
    }

    /**
     * Move one node before another
     *
     * @param int $node_id OF of node that will be moved
     * @param int $ref_node_id
     * @return bool TRUE if node was moved successfully, FALSE otherwise
     */
    function moveNodeAbove($node_id, $ref_node_id)
    {
        return $this->_moveNode($node_id, $ref_node_id, 'above');
    }

    /**
     * Move page as first child under another page
     *
     * @param int $node_id
     * @param int $parent_node_id
     * @return bool TRUE if page was moved successfully, FALSE otherwise
     */
    function moveNodeUnder($node_id, $parent_node_id)
    {
        // get moving node information
        $row = $this->_db->fetch_first_row('SELECT `mpath`, `zort` FROM ?f '
            . 'WHERE `content` = ?', $this->_tbl_name, $node_id);

        if (false === $row) {
            return false;
        }

        $old_mpath = $row['mpath'];
        $old_zort = $row['zort'];
        $old_parent_id = $this->parentIdFromMpath($row['mpath']);

        if ($old_parent_id == $parent_node_id) {
            // page is already located under this parent node
            return false;
        }

        if (0 == $parent_node_id) {
            // move page under the root node
            $new_mpath = '';

        } else {

            // new mpath for moving node
            $new_mpath = $this->_db->fetch_first_value('SELECT `mpath` FROM ?f'
                . ' WHERE `content` = ?', $this->_tbl_name, $parent_node_id);

            if (false === $new_mpath) {
                return false;
            }

            // check if we are trying to move node under one of it's children
            // that's of course not possible
            if ($this->_isParent($node_id, $new_mpath)) {
                return false;
            }

            $new_mpath = $new_mpath ? "$new_mpath.$parent_node_id" : $parent_node_id;
        }

        //
        // NB! executing following queries without database transation is dangerous
        // hovewer negative impact is minimied because mpath modifications are
        // made using only one query
        //

        $this->_changeMpath($node_id, $old_mpath, $new_mpath);

        // change new siblings zort
        $this->_db->query('UPDATE ?f SET `zort` = `zort` + 1 WHERE `mpath` = ?'
            , $this->_tbl_name, $new_mpath);

        // change old siblings zort
        $this->_db->query('UPDATE ?f SET `zort` = `zort` - 1 WHERE `mpath` = ?'
            . ' AND `zort` > ?', $this->_tbl_name, $old_mpath, $old_zort);

        // set zort value for moved page
        $this->_db->query('UPDATE ?f SET `mpath` = ?, `zort` = ? WHERE `content` = ?'
            , $this->_tbl_name, $new_mpath, $this->_start_zort, $node_id);

        return true;
    }

    /**
     * Move page up
     *
     * Moves page with all linked items (pages) up
     *
     * @param int $node_id ID of moving page
     * @return bool TRUE if page was moved successfully, FALSE otherwise
     */
    function moveNodeUp($node_id)
    {
        $siblings = $this->_getSiblings($node_id, array('content', 'zort')
            , '`zort` ASC');

        if (!$siblings) {
            return false;
        }

        for ($pos = 1; $row = array_shift($siblings); $pos++) {
            if ($row['content'] == $node_id) {
                if (1 == $pos) {
                    // page is already first in the list
                    return false;
                } else {
                    // move item up
                    $this->_db->query('UPDATE ?f SET `zort` = ? WHERE `content` = ?'
                        , $this->_tbl_name, $row_prev['zort'], $row['content']);
                    $this->_db->query('UPDATE ?f SET `zort` = ? WHERE `content` = ?'
                        , $this->_tbl_name, $row['zort'], $row_prev['content']);
                    return true;
                }
            }

            // save current row data to be available for next iteration
            $row_prev = $row;
        }

        return false;
    }

    /**
     * Move page down
     *
     * Moves page with all linked items (pages) down
     *
     * @param int $node_id ID of moving page
     * @return bool TRUE if page was moved successfully, FALSE otherwise
     */
    function moveNodeDown($node_id)
    {
        $siblings = $this->_getSiblings($node_id, array('content', 'zort')
            , '`zort` ASC');

        if (!$siblings) {
            return false;
        }

        $items_num = count($siblings);
        for ($pos = 1; $row = array_shift($siblings); $pos++) {
            if ($row['content'] == $node_id) {
                if ($pos == $items_num) {
                    // page is already last on the list
                    return false;
                } else {
                    // move node down
                    $row_next = array_shift($siblings);
                    $this->_db->query('UPDATE ?f SET `zort` = ? WHERE `content` = ?'
                        , $this->_tbl_name, $row_next['zort'], $row['content']);
                    $this->_db->query('UPDATE ?f SET `zort` = ? WHERE `content` = ?'
                        , $this->_tbl_name, $row['zort'], $row_next['content']);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Delete node
     *
     * @param int $node_id
     * @param bool $recursive if TRUE than all children will be removed, otherwise children
     *  nodes will be moved on one level upper
     * @return bool TRUE if node removed successfully, FALSE otherwise
     */
    function deleteNode($node_id, $recursive)
    {
        if (0 == $node_id) {
            // remove all nodes
            return $this->_db->query('TRUNCATE TABLE ?f', $this->_tbl_name);
        }

        // get removing node mpath and zort
        $row = $this->_db->fetch_first_row('SELECT `mpath`, `zort` FROM ?f'
            . ' WHERE `content` = ?', $this->_tbl_name, $node_id);

        if (!$row) {
            return false;
        } else {
            // creating $mpath and $zort variables
            extract($row);
        }

        $children_mpath = $mpath ? "$mpath.$node_id" : $node_id;
        if ($recursive) {
            // change siblings zort value
            $this->_db->query('UPDATE ?f SET `zort` = `zort` - 1 WHERE'
                . ' `mpath` = ? AND `zort` > ?', $this->_tbl_name, $mpath, $zort);
            // remove node with all it's children
            $this->_db->query('DELETE FROM ?f WHERE `content` = ? OR'
                . ' `mpath` = ? OR `mpath` LIKE ?', $this->_tbl_name, $node_id
                , $children_mpath, "$children_mpath.%");
        } else {

            // get child nodes count of removing node
            $children_count = $this->_nodesCount($children_mpath);
            if ($children_count > 0) {
                // move children nodes
                $this->moveChildren($node_id, $this->parentIdFromMpath($mpath), $zort);

                // change sibling zort (removing node is not removed yet)
                $this->_db->query('UPDATE ?f SET `zort` = `zort` - 1 WHERE'
                    . ' `mpath` = ? AND `zort` > ?', $this->_tbl_name, $mpath
                    , $zort + $children_count);

            } else {
                // change siblings zort value
                $this->_db->query('UPDATE ?f SET `zort` = `zort` - 1 WHERE'
                    . ' `mpath` = ? AND `zort` > ?', $this->_tbl_name, $mpath
                    , $zort);
            }

            // remove node
            $this->_db->query('DELETE FROM ?f WHERE `content` = ?', $this->_tbl_name
                , $node_id);
        }

        return true;
    }

    /**
     * Move children nodes
     *
     * Moves all child nodes of one one under another node into specified
     * order position.
     *
     * @param int $old_parent_id id of old parent node
     * @param int $new_parent_id id of new parent node, if equals zero than child
     *  nodes will be moved to the parent level
     * @param int $zort inserted child nodes zort will be started from this number,
     *  if there was already nodes with this zort value they will be moved down,
     *  if parameter is not specified new nodes will be inserted above old ones
     * @return bool
     */
    function moveChildren($old_parent_id, $new_parent_id, $zort = null)
    {
        // if zort was not passed, children nodes will be inserted before
        // other children (if exists) of new parent
        if (null == $zort) {
            $zort = $this->_start_zort;
        }

        if (0 == $old_parent_id) {
            return false;

        } else {
            // get old mpath
            $old_mpath = $this->childrenMpath($old_parent_id);
            if (false === $old_mpath) {
                return false;
            }
        }

        // get new mpath
        $new_mpath = $this->childrenMpath($new_parent_id);
        if (false === $new_mpath) {
            return false;
        }

        if ($this->_isParent($old_parent_id, $new_mpath)) {
            return false;
        }

        $children_count = $this->_nodesCount($old_mpath);
        if ($children_count) {
            // get number of new sibling nodes
            $new_siblings_count = $this->_nodesCount($new_mpath);

            // process passed zort value
            if (0 == $new_siblings_count) {
                // no new siblings ignore passed zort value
                $zort = $this->_start_zort;

            } else if ($zort < $this->_start_zort) {
                // invalid zort value passed
                $zort = $this->_start_zort;

            } else if ($zort > $this->_start_zort + $new_siblings_count) {
                // invalid zort value passed (too big)
                $zort = $this->_start_zort + $new_siblings_count;
            }

            if ($zort < $this->_start_zort + $new_siblings_count) {
                // change zort value of new siblings
                $this->_db->query('UPDATE ?f SET `zort` = `zort` + ? WHERE `mpath` = ?'
                    . ' AND `zort` >= ?', $this->_tbl_name, $children_count, $new_mpath
                    , $zort);
            }

            if ($zort > $this->_start_zort) {
                // change zort value of moving nodes
                $this->_db->query('UPDATE ?f SET `zort` = `zort` + ? WHERE `mpath` = ?'
                    , $this->_tbl_name, $zort - $this->_start_zort, $old_mpath);
            }

            // move nodes
            $cut_pos = strlen($old_mpath) + 2;
            $this->_db->query("
                UPDATE
                    ?f
                SET
                    `mpath` = CONCAT(
                        ?
                        , IF(? \\!= '' AND SUBSTRING(`mpath`, $cut_pos) \\!= '', '.', '')
                        , SUBSTRING(`mpath`, $cut_pos)
                     )
                WHERE
                    `mpath` = ?
                    OR `mpath` LIKE ?
                "
                , $this->_tbl_name, $new_mpath, $new_mpath, $old_mpath, "$old_mpath.%" );

        }

        return true;
    }

    /**
     * Recursively move node
     *
     * @param int $node_id
     * @param int $ref_node_id
     * @param string $insert_style should be rather 'below' or 'above', 'below' -
     *  node will be moved immidiately after node identified by $node_id
     * @return bool TRUE if node was moved successfully, FALSE otherwise
     * @access private
     */
    function _moveNode($node_id, $ref_node_id, $insert_style)
    {
        // get nodes details
        $result = &$this->_db->query('SELECT `zort`, `mpath`, `content` FROM ?f '
            . ' WHERE `content` = ? OR `content` = ?', $this->_tbl_name
            , $node_id, $ref_node_id);

        if (!$result || 2 != $result->num_rows()) {
            return false;
        }

        $nodes = array();
        while ($row = $result->fetch_assoc()) {
            $row['parent_id'] = $this->parentIdFromMpath($row['mpath']);
            $nodes[$row['content']] = $row;
        }

        if ($nodes[$node_id]['parent_id'] == $nodes[$ref_node_id]['parent_id']) {
            // nodes are siblings, change only zort values
            $this->_changeItemOrder($node_id, $nodes[$ref_node_id]['zort']
                , $insert_style);

        } else {

            $old_mpath = $nodes[$node_id]['mpath'];
            $new_mpath = $nodes[$ref_node_id]['mpath'];

            // check if we are trying to move parent node under one of it's children
            if ($this->_isParent($node_id, $new_mpath)) {
                return false;
            }

            if ('below' == $insert_style) {
                // insert node after
                $compare_op = '>';
                $new_zort = $nodes[$ref_node_id]['zort'] + 1;
            } else {
                // insert node before
                $compare_op = '>=';
                $new_zort = $nodes[$ref_node_id]['zort'];
            }

            //
            // NB! executing following queries without transation is dangerous :-)
            // hovewer negative impact is minimied because mpath modifications
            // are made using only one query
            //

            $this->_changeMpath($node_id, $old_mpath, $new_mpath);

            // change order for new location
            $this->_db->query('UPDATE ?f SET `zort` = `zort` + 1 WHERE '
                . " `mpath` = ? AND `zort` $compare_op ?", $this->_tbl_name
                , $new_mpath, $nodes[$ref_node_id]['zort']);

            // set zort value for moving node
            $this->_db->query('UPDATE ?f SET `zort` = ? WHERE `content` = ?'
                , $this->_tbl_name, $new_zort, $node_id);

            // change order for old location siblings
            $this->_db->query('UPDATE ?f SET `zort` = `zort` - 1 WHERE `mpath` = ?'
                . ' AND `zort` > ?', $this->_tbl_name, $old_mpath
                , $nodes[$node_id]['zort']);
        }

        return true;
    }

    /**
     * Recalculate zval values
     *
     * Recalculate zval values for nodes with specified mpath, after calling this
     * method zval values will be continious starting from 10000
     *
     * @param string $mpath
     * @return bool
     * @access protected
     */
    function _recalcNodesOrder($mpath)
    {
        $nodes = $this->_getNodes($mpath, array('content'), '`zort` ASC');

        if (!$nodes) {
            return false;
        }

        $zort = $this->_start_zort;
        foreach ($nodes as $node_data) {
            $this->_db->query('UPDATE ?f SET `zort` = ? WHERE `content` = ?'
                , $this->_tbl_name, $zort++, $node_data['content']);
        }

        return true;
    }

    /**
     * Get siblings information
     *
     * @param int $node_id
     * @param array $columns columns that will be fetched
     * @param string $order_by order by clause
     * @return array|FALSE array with sibilings data, or FALSE if page or columns
     *  was not found
     * @access protected
     */
    function _getSiblings($node_id, $columns, $order_by = '')
    {
        $mpath = $this->_db->fetch_first_value('SELECT `mpath` FROM ?f WHERE'
            . ' `content` = ?', $this->_tbl_name, $node_id);

        if (false === $mpath) {
            return false;
        }

        return $this->_getNodes($mpath, $columns, $order_by);
    }

    /**
     * Get nodes information
     *
     * @param string $mpath
     * @param array $columns
     * @param string $order_by
     * @return array|FALSE array with children data, or FALSE if pages or columns
     *  was not found
     * @access protected
     */
    function _getNodes($mpath, $columns, $order_by = '')
    {
        if ($order_by) {
            $order_by = ' ORDER BY ' . $order_by;
        }

        return $this->_db->fetch_all('SELECT ?@f FROM ?f WHERE `mpath` = ?'
            . $order_by, $columns, $this->_tbl_name, $mpath);
    }

    /**
     * Get number of nodes with specified mpath
     *
     * @param string $mpath
     * @return int
     * @access protected
     */
    function _nodesCount($mpath)
    {
        return $this->_db->fetch_first_value('SELECT COUNT(*) FROM ?f WHERE `mpath` = ?'
            , $this->_tbl_name, $mpath);
    }

    /**
     * Get id of parent node from materialized path
     *
     * If materialized path is empty string than parent node id is zero (0)
     *
     * @param string $mpath
     * @return int
     * @static
     */
    function parentIdFromMpath($mpath)
    {
        if ($mpath) {
            $pos = strrpos($mpath, '.');
            if (!$pos) {
                return $mpath;
            }
            return substr($mpath, $pos + 1);
        } else {
            return 0;
        }
    }

    /**
     * Change order of sibling items
     *
     * @param int $node_id
     * @param int $zort zort value of another item
     * @param string $insert_style  should be rather 'below' or 'above', 'below' -
     *  node will be moved immitiately after node with zort = $zort, 'above' -
     *  node will be moved before node with zort = $zort2
     * @access protected
     */
    function _changeItemOrder($node_id, $zort, $insert_style)
    {
        switch ($insert_style) {
            case 'below':
                $compare_op = '>';
                $new_zort = $zort + 1;
                break;

            case 'above':
                $compare_op = '>=';
                $new_zort = $zort;
                break;

            default:
                trigger_error('Invalid insert style specified!', E_USER_ERROR);
                return;
        }

        // get mpath
        $mpath = $this->_db->fetch_first_value('SELECT `mpath` FROM ?f WHERE `content` = ?'
            , $this->_tbl_name, $node_id);
        if (false === $mpath) {
            return;
        }

        $this->_db->query('UPDATE ?f SET `zort` = `zort` + 1 WHERE `mpath` = ?'
            . " AND `zort` $compare_op ?", $this->_tbl_name, $mpath, $zort);
        $this->_db->query('UPDATE ?f SET `zort` = ? WHERE `content` = ?'
            , $this->_tbl_name, $new_zort, $node_id);

        $this->_recalcNodesOrder($mpath);
    }

    /**
     * Recursively change mpath
     *
     * This method used inside another nodes moving methods
     *
     * @param int $node_id
     * @param string $old_mpath
     * @param string $new_mpath
     * @return bool
     * @access protected
     */
    function _changeMpath($node_id, $old_mpath, $new_mpath)
    {
        if ($old_mpath) {
            $old_children_mpath = "$old_mpath.$node_id";
            $cut_pos = strlen($old_mpath) + 2;
        } else {
            $old_children_mpath = $node_id;
            $cut_pos = 1;
        }

        return $this->_db->query("
            UPDATE
                ?f
            SET
                `mpath` = CONCAT(
                    ?
                    , IF(? \\!= '' AND SUBSTRING(`mpath`, $cut_pos) \\!= '', '.', '')
                    , SUBSTRING(`mpath`, $cut_pos)
                 )
            WHERE
                `content` = ?
                OR `mpath` = ?
                OR `mpath` LIKE ?
            "
            , $this->_tbl_name, $new_mpath, $new_mpath, $node_id
            , $old_children_mpath, "$old_children_mpath.%");
    }

    /**
     * Create new node
     *
     * @param array $node associative array with node data, array keys are mapped
     *  to content table fields
     * @param int $ref_node_id
     * @param string $insert_style shoudl be wether 'below' or 'above'
     * @access private
     */
    function _createNode($node, $ref_node_id, $insert_style)
    {
        $row = $this->_db->fetch_first_row('SELECT `mpath`, `zort` FROM ?f WHERE'
            . ' `content` = ?', $this->_tbl_name, $ref_node_id);

        if (!$row) {
            return false;
        } else {
            extract($row);
        }

        switch ($insert_style) {
            case 'below':
                $compare_op = '>';
                $new_zort = $zort + 1;
                break;

            case 'above':
                $compare_op = '>=';
                $new_zort = $zort;
                break;

            default:
                trigger_error('Invalid insert style specified!', E_USER_ERROR);
                return false;
        }

        // change zort of sibling nodes
        $this->_db->query('UPDATE ?f SET `zort` = `zort` + 1 WHERE `mpath` = ?'
            . " AND `zort` $compare_op ?", $this->_tbl_name, $mpath, $zort);

        // insert new node
        $node['zort'] = $new_zort;
        $node['mpath'] = $mpath;

        $result = $this->_db->query('INSERT INTO ?f (?@f) VALUES (?@)'
            , $this->_tbl_name, array_keys($node), $node);

        if ($result) {
            return $this->_db->insert_id();
        } else {
           return false;
        }
    }

    /**
     * Check if one node is parent of another
     *
     * @param int $node_id id of parent node
     * @param string $mpath materialized path of children node
     * @return bool
     * @access protected
     */
    function _isParent($node_id, $mpath)
    {
        if (0 == $node_id) {
            return true;
        }

        $id = $node_id;
        if (preg_match("/^$id$|^$id\\.|\\.$id\\.|\\.$id$/", $mpath)) {
            return true;
        }

        return false;
    }

    /**
     * Get parent node id for specified node
     *
     * @param int $node_id
     * @return int parent node id or 0 if there is no parent node
     */
    function getParent($node_id)
    {
        $mpath = $this->_db->fetch_first_value('SELECT `mpath` FROM ?f WHERE'
            . ' `content` = ?', $this->_tbl_name, $node_id);
        if ($mpath) {
            return $this->parentIdFromMpath($mpath);
        } else {
            return 0;
        }
    }

    /**
     * Get children nodes mpath value
     *
     * @param int $node_id
     * @return string|FALSE
     */
    function childrenMpath($node_id)
    {
        if (0 == $node_id) {
            return '';
        }

        $mpath = $this->_db->fetch_first_value('SELECT `mpath` FROM ?f WHERE'
            . ' `content` = ?', $this->_tbl_name, $node_id);

        if (false === $mpath) {
            return false;
        }

        return $mpath ? "$mpath.$node_id" : $node_id;
    }
}