<?php
/**
 * @version $Revision: 970 $
 */

require_once(SITE_PATH . '/class/Arrays.php');

/**
 * Modera version control system
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class Versioning
{
    /**
     * Databse instance
     *
     * @var Database
     * @access protected
     */
    var $_db;

    /**
     * Database table used for storing data
     *
     * @var string
     * @access protected
     */
    var $_table = 'versioning';

    /**
     * Class constructor
     *
     * @param Databse $database
     * @return Versioning
     */
    function Versioning(&$database)
    {
        $this->_db = &$database;
    }

    /**
     * Get raw data of specified versioned object
     *
     * @param int $version_id version id
     * @return array|FALSE associative array with object data, or FALSE if
     *  no object by that id was found
     */
    function getRawVersion($version_id)
    {
        $data = $this->_db->fetch_all('SELECT * FROM ?f WHERE `id` = ?'
            , $this->_table, $version_id);

        if ($data) {
            list($data) = $data;
            $data['object_data'] = unserialize($data['object_data']);
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Get raw data for the most recent version of object
     *
     * @param string $object_type
     * @param int $object_id
     * @param array $fields array of fields fetched from versioning table, if not
     *  specified all fields will be returned
     * @return array|FALSE associative array with object data, or FALSE if
     *  object was not found
     */
    function getCurrentRawVersion($object_type, $object_id, $fields = array())
    {
        $data = $this->_db->fetch_first_row('
            SELECT
                ' . ($fields ? '!' : '?@f') . '
            FROM
                ?f
            WHERE
                `object_type` = ?
                AND `object_id` = ?
            ORDER BY
                `mod_time` DESC
            LIMIT 1
            ', ($fields ? '*' : $fields), $this->_table, $object_type, $object_id);

        if ($data) {
            if (isset($data['object_data'])) {
                $data['object_data'] = unserialize($data['object_data']);
            }
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Get processed data for the most recent version of object
     *
     * @param string $object_type
     * @param int $object_id
     * @return array|FALSE processed object data, or FALSE if object was not
     *  found
     */
    function getCurrentData($object_type, $object_id)
    {
        $data = $this->getCurrentRawVersion($object_type, $object_id
            , array('object_data'));
        if ($data) {
            return $data['object_data'];
        } else {
            return false;
        }
    }

    /**
     * Get raw data for previous version.
     *
     * @param string $object_type
     * @param int $object_id
     * @param int|null $version_id
     * @return array|FALSE processed object
     */
    function getPreviousRawVersion($object_type, $object_id, $version_id = null)
    {
        $sql_where = '';
        $version_id = (int)$version_id;
        if ($version_id) $sql_where = " AND `id` <= '{$version_id}'";

        $previous = $this->_db->fetch_first_col("SELECT `id`
            FROM
                ?f
            WHERE
                `object_type` = ?
                AND `object_id` = ?
                $sql_where
            ORDER BY
                `mod_time` DESC
            LIMIT 2", $this->_table, $object_type, $object_id);

        if (count($previous) != 2) return false;
        return $this->getRawVersion($previous[1]);
    }

    /**
     * Get object timeline. All data are in RAW format.
     *
     * @param string $object_type - one of 'content' or 'trash'
     * @param int $object_id - Object ID
     * @return array|FALSE
     */
    function getObjectTimeline($object_type, $object_id)
    {
        $timeline_res = $this->_db->query("SELECT `id`
            FROM
                ?f
            WHERE
                `object_type` = ?
                AND `object_id` = ?
            ORDER BY
                `mod_time` DESC"
            , $this->_table, $object_type, $object_id);
        if (!$timeline_res) return false;

        $timeline = array();
        while($row = $timeline_res->fetch_assoc()) {
            $timeline[] = $this->getRawVersion($row['id']);
        }
        return $timeline;
    }

    /**
     * Update record for the most recent version of object
     *
     * @param string $object_type
     * @param int $object_id
     * @param int|NULL $mod_user
     * @param array $data associative array of data to update
     * @param bool $rewrite_data if TRUE than old data will be rewritten with
     *  a new one
     * @return bool
     */
    function updateCurrentData($object_type, $object_id, $mod_user, $data
        , $rewrite_data = true)
    {
        $row = $this->_db->fetch_first_row('
            SELECT
                `id`,
                `object_data` AS `old_data`
            FROM
                ?f
            WHERE
                `object_type` = ?
                AND `object_id` = ?
            ORDER BY
                `mod_time` DESC
            LIMIT 1
            ', $this->_table, $object_type, $object_id);
        extract($row);

        if ($id) {
            if (!$rewrite_data) {
                $old_data = unserialize($old_data);
                $data = array_merge($old_data, $data);

                // removed elements that have NULL value
                foreach ($data as $k => $v) {
                    if (null === $v) {
                        unset($data[$k]);
                    }
                }
            }

            $data = serialize($data);

            if (null === $mod_user) {
                // do not update mod_user field
                $r = $this->_db->query('UPDATE ?f SET `object_data` = ? WHERE `id` = ?'
                    , $this->_table, $data, $id);
            } else {
                $r = $this->_db->query('UPDATE ?f SET `mod_user` = ?, `mod_userdata` = ? `object_data` = ?'
                    . ' WHERE `id` = ?', $this->_table, $mod_user, $this->_getSerializedUserData($mod_user), $data, $id);
            }

            return (bool) $r;

        } else {
            return false;
        }
    }

    /**
     * Update specified fields for the most recent version of object
     *
     * @param string $object_type
     * @param int $object_id
     * @param array $data
     * @return bool
     */
    function injectToCurrentData($object_type, $object_id, $data)
    {
        return $this->updateCurrentData($object_type, $object_id, null, $data
            , false);
    }

    /**
     * Remove specified field from the most recent version of object
     *
     * @param string $object_type
     * @param int $object_id
     * @param mixed $field_name
     * @return bool
     */
    function removeFromCurrentData($object_type, $object_id, $field_name)
    {
        if (is_array($field_name)) {
            $fields = array();
            foreach ($field_name as $k => $_dummy) {
                $fields[$k] = null;
            }
        } else {
            $fields = array($field_name => null);
        }

        return $this->updateCurrentData($object_type, $object_id, null, $fields, false);
    }

    /**
     * Change object type and id
     *
     * @param string $old_object_type
     * @param int $old_object_id
     * @param string $new_object_type
     * @param int $new_object_id
     */
    function changeObjectId($old_object_type, $old_object_id, $new_object_type
        , $new_object_id)
    {
        $this->_db->query('
            UPDATE
                ?f
            SET
                `object_type` = ?
                , `object_id` = ?
                , `mod_time` = `mod_time`
            WHERE
                `object_type` = ?
                AND `object_id` = ?
        ', $this->_table, $new_object_type, $new_object_id, $old_object_type
            , $old_object_id);
    }

    /**
     * Remove versioning data for specified object(s)
     *
     * @param string $object_type
     * @param mixed $object_id if object_id is not passed or NULL than all
     *  versioning data of object_type will be removed
     */
    function removeObject($object_type, $object_id = null)
    {
        if (null === $object_id) {
            $this->_db->query('DELETE FROM ?f WHERE `object_type` = ?'
                , $this->_table, $object_type);
        } else if (is_array($object_id)) {
            $this->_db->query('DELETE FROM ?f WHERE `object_type` = ?'
                . ' AND `object_id` IN(?@)', $this->_table, $object_type, $object_id);
        } else {
            $this->_db->query('DELETE FROM ?f WHERE `object_type` = ?'
                . ' AND `object_id` = ?', $this->_table, $object_type, $object_id);
        }
    }

    /**
     * Add new versioning record
     *
     * @param string $object_type
     * @param int $object_id
     * @param int $mod_user
     * @param array $data
     * @return bool
     */
    function addData($object_type, $object_id, $mod_user, $data)
    {
        return (bool) $this->_db->query('
            INSERT INTO ?f
                (`object_type`, `object_id`, `mod_user`, `mod_userdata`, `object_data`)
            VALUES (?@)
            ', $this->_table, array($object_type, $object_id, $mod_user, $this->_getSerializedUserData($mod_user), serialize($data)));
    }

    /**
     * Remove record about latest version of object
     *
     * @param string $object_type
     * @param int $object_id
     * @return bool
     */
    function removeLastVersion($object_type, $object_id)
    {
        // get last version id
        $id = $this->_db->fetch_first_value('SELECT `id` FROM ?f WHERE'
            . ' `object_type` = ? AND `object_id` = ? ORDER BY `mod_time` DESC LIMIT 1'
            , $this->_table, $object_type, $object_id);

        if ($id) {
            return (bool) $this->_db->query('DELETE FROM ?f WHERE `id` = ?'
                , $this->_table, $id);
        } else {
            return false;
        }
    }

    /**
     * Get timeline array
     *
     * @param array $objects array of objects to fetch
     * @param mixed $data_keys array of keys to extract from versioning data
     * @return array
     */
    function getTimeline($objects, $data_keys = null)
    {
        if (null !== $data_keys && !is_array($data_keys)) {
            $data_keys = array((string)$data_keys);
        }

        // Get modified pages with number of changes.
        $_tmp_objects = &$this->_db->fetch_all("
            SELECT
                MAX(`id`) AS `id`
                , COUNT(`v`.`id`) AS `changes`
            FROM
                ?f AS `v`
            WHERE
                `object_type` IN (?@)
            GROUP BY
                `object_type`, `object_id`
            ", $this->_table, $objects);

        // collect number of changes and versioning ID's in special array.
        $_objects = array();
        foreach ($_tmp_objects as $_obj) {
            $_objects[$_obj['id']] = $_obj['changes'];
        }

        $timeline = &$this->_db->fetch_all("
            SELECT
                `v`.`object_type`
                , `v`.`object_id`
                , `v`.`object_data`
                , `v`.`id`
                , `v`.`mod_time`
                , IF (`u`.`user` IS NULL, `v`.`mod_userdata`, CONCAT(`u`.`name`, ' (', `u`.`username`, ')')) AS `user`
                , IF (`u`.`user` IS NULL, 1, 0) AS `user_removed`
            FROM
                ?f AS `v` LEFT JOIN `adm_user` AS `u`
                    ON `v`.`mod_user` = `u`.`user`
            WHERE
                `v`.`id` IN (?@)
            ORDER BY `mod_time` DESC
            ", $this->_table, array_keys($_objects));

        $content_nodes = array();
        foreach ($timeline as $k => $row) {
            $object_data = unserialize($row['object_data']);

            $timeline[$k]['changes'] = $_objects[$row['id']];

            if ($timeline[$k]['user_removed']) {
                list($_uname, $_uusername) = unserialize($timeline[$k]['user']);
                $timeline[$k]['user'] = "$_uname ($_uusername)";
            }
            if ($data_keys) {
                $timeline[$k]['data'] = Arrays::array_intersect_key_val($object_data
                    , $data_keys);
            } else {
                $timeline[$k]['data'] = $object_data;
            }

            // collect content node id's in special array
            // later pending status should be taken and stored for each
            // content node
            if ('content' == $row['object_type']) {
                $content_nodes[$k] = $row['object_id'];
            }
        }


        // get content nodes pending status
        // this is special case, pending status can be taken only for
        // content nodes, since it stored in related field of "content" table
        if ($content_nodes) {
            $res = &$this->_db->query('SELECT `content`, `pending` FROM `content`'
                . ' WHERE `content` IN (?@)', $content_nodes);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $timeline[array_search($row['content'], $content_nodes)]['pending']
                        = $row['pending'];
                }
            }
        }

        return $timeline;
    }

    /**
     * Get serialized user data.
     *
     * Used for retriving serialized user
     * data to store with content version.
     *
     * @param int $user
     * @return string
     */
    function _getSerializedUserData($user){
        $_ud = $this->_db->fetch_first_row("
            SELECT
                *
            FROM
                `adm_user`
            WHERE
                `user` = ?"
            , (int)$user);
        return serialize(array($_ud['name'], $_ud['username'], $_ud['email']));
    }
}