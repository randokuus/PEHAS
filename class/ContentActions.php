<?php
/**
 * @version $Revision: 621 $
 */

require_once(SITE_PATH . "/class/aliases_helpers.php");
require_once(SITE_PATH . "/class/cache_helpers.php");
require_once(SITE_PATH . "/class/Arrays.php");

/**
 * Class for handling common operations on nodes
 *
 * This class does not care about permissions so they should be handled outside
 * of this class. Methods of this class are aware of versioning and will create,
 * remove or update versioning records of processed objects autoimatically.
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class ContentActions
{
    /**
     * Database instance
     *
     * @var Database
     * @access protected
     */
    var $_db;

    /**
     * PageTags instance
     *
     * @var PageTags
     * @access protected
     */
    var $_pageTags;

    /**
     * Versioning instance
     *
     * @var Versioning
     * @access protected
     */
    var $_versioning;

    /**
     * ContentStructure instance
     *
     * @var ContentStructure
     * @access protected
     */
    var $_contentStrcucture;

    /**
     * SystemLog instance
     *
     * @var SystemLog
     */
    var $_systemLog;

    /**
     * Class constructor
     *
     * @param Database $database
     * @param PageTags $pageTags
     * @param Versioning $versioning
     * @param ContentStructure $contentStructure
     * @return ContentActions
     */
    function ContentActions(&$database, &$pageTags, &$versioning, &$contentStructure)
    {
        $this->_db = &$database;
        $this->_pageTags = &$pageTags;
        $this->_versioning = &$versioning;
        $this->_contentStrcucture = &$contentStructure;
        $this->_systemLog = &SystemLog::instance($database);
    }

    /**
     * Move template node to trash
     *
     * @param int $template_id
     * @return int|FALSE trash node id, or FALSE in case of error
     */
    function moveTemplateToTrash($template_id)
    {
        // get columns copied between tables
        $fields = $this->_tablesIntersect('content_trash', 'content_templates'
            , 'content');

        $r = $this->_db->query("
            INSERT INTO
                `content_trash`
                (?@f)
            SELECT
                ?@f
            FROM
                `content_templates`
            WHERE
                `content` = ?
        ", $fields, $fields, $template_id);

        if ($r) {
            $trash_id = $this->_db->insert_id();
            $this->_db->query('DELETE FROM `content_templates` WHERE `content` = ?'
                , $template_id);
            $this->_versioning->changeObjectId('template', $template_id, 'trash'
                , $trash_id);
            $this->logAction('Template "%s" has been moved to recycle bin by %s'
                , 'trash', $trash_id);
            return $trash_id;
        } else {
            return false;
        }
    }

    /**
     * Recursively move content node (with all children) to trash
     *
     * @param int $content_id
     * @return int|FALSE trash node id, or FALSE in case of error
     */
    function moveContentToTrash($content_id)
    {
        $fields = $this->_tablesIntersect('content', 'content_trash', 'content');
        $mpath = $this->_contentStrcucture->childrenMpath($content_id);

        if (!$mpath) {
            return false;
        }

        // get nodes IDs
        $removing_nodes = $this->_db->fetch_first_col('
            SELECT
                `content`
            FROM
                `content`
            WHERE
                `content` = ?
                OR `mpath` = ?
                OR `mpath` LIKE ?
            ', $content_id, $mpath, "$mpath.%");

        if (!$removing_nodes) {
            return false;
        }

        // NB! following operations should be done in transaction
        // but unfortunately we are using transaction less database
        // however we can emulate transactions with MySQL LOCK TABLES
        // but it requires LOCK TABLES priviledge

        $trash_id = false;
        foreach ($removing_nodes as $removing_node_id) {
            // copy node data into trash table
            $this->_db->query('
                INSERT INTO
                    `content_trash` (?@f)
                SELECT
                    ?@f
                FROM
                    `content`
                WHERE
                    `content` = ?
                ', $fields, $fields, $removing_node_id);

            if ($removing_node_id == $content_id) {
                $trash_id = $this->_db->insert_id();
            }

            // change versioning object
            $this->_versioning->changeObjectId('content', $removing_node_id
                , 'trash', $this->_db->insert_id());

            // remove tags
            $this->_pageTags->removeTags($removing_node_id);

            $this->logAction('Content page "%s" has been moved to recyle bin by %s'
                , 'content', $removing_node_id);
        }

        // remove real nodes records from content table
        $this->_contentStrcucture->deleteNode($content_id, true);
        $this->refreshStructure();
        return $trash_id;
    }

    /**
     * Remove node from trash
     *
     * @param int $trash_id
     * @return bool
     */
    function removeFromTrash($trash_id)
    {
        $msg = $this->prepareLogMessage('Page "%s" has been removed from recyle'
            . ' bin by %s', 'trash', $trash_id);

        $r = $this->_db->query("DELETE FROM `content_trash` WHERE `content` = ?"
            , $trash_id);

        if ($r) {
            $this->_versioning->removeObject('trash', $trash_id);
            $this->logAction($msg);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Restore template node
     *
     * @param int $trash_id
     * @return int|FALSE new template node id, or FALSE in case of error
     */
    function restoreTemplate($trash_id)
    {
        $fields = $this->_tablesIntersect('content_trash', 'content_templates'
            , 'content');

        $r = $this->_db->query("
            INSERT INTO
                `content_templates`
                (?@f)
            SELECT
                ?@f
            FROM
                `content_trash`
            WHERE
                `content` = ?
            ", $fields, $fields, $trash_id);

        if ($r) {
            $template_id = $this->_db->insert_id();
            $this->_db->query('DELETE FROM `content_trash` WHERE `content` = ?'
                , $trash_id);
            $this->_versioning->changeObjectId('trash', $trash_id, 'template'
                , $template_id);
            $this->logAction('Template page "%s" has been restored from recyle bin by %s'
                , 'template', $template_id);

            return $template_id;

        } else {
            return false;
        }
    }

    /**
     * Restore content node
     *
     * New record in content table will be created based on record from
     * content_trash table. Source record will be removed from content_trash
     * and versioning history will be changed to bind to new content node.
     *
     * @param int $trash_id
     * @param int $ref_node
     * @param string $point one of 'above', 'below' or 'append'
     * @param bool $pending TRUE if node should be marked as pending creation
     * @return int|FALSE new content node id, or FALSE in case of error
     */
    function restoreContent($trash_id, $ref_node, $point, $pending = false)
    {
        $content_id = $this->_contentFrom('content_trash', $trash_id, $ref_node
            , $point, array('pending' => $pending ? MODERA_PENDING_CREATION : 0));
        if ($content_id) {
            $this->_db->query('DELETE FROM `content_trash` WHERE `content` = ?'
                , $trash_id);
            $this->_versioning->changeObjectId('trash', $trash_id, 'content'
                , $content_id);
            $this->logAction('Content page "%s" has been restored from recyle bin by %s'
                , 'content', $content_id);

            return $content_id;

        } else {
            return false;
        }
    }

    /**
     * New content node based on template
     *
     * Creates new content node based on source record from content_templates
     * table. Versioning object is created for new content node.
     *
     * @param int $template_id
     * @param int $ref_node
     * @param string $point one of 'above', 'below' or 'append'
     * @param int $user owner of content node
     * @param bool $pending TRUE if node should be marked as pending creation
     * @return int|FALSE new content node id, or FALSE in case of error
     */
    function contentFromTemplate($template_id, $ref_node, $point, $user, $pending = false)
    {
        $content_id = $this->_contentFrom('content_templates', $template_id
            , $ref_node, $point, array('pending' => $pending ? MODERA_PENDING_CREATION : 0
            , 'owner' => $user, 'moduser' => $user));
        if ($content_id) {
            // create new versioning record
            $this->_versionFrom('content', $content_id);
            $this->logAction('New content page has been created using template "%s" by %s'
                , 'content', $content_id);

            return $content_id;

        } else {
            return false;
        }
    }

    /**
     * Create new versioning object from specified source object
     *
     * @param string $source_type
     * @param int $source_id
     * @return bool
     * @access protected
     */
    function _versionFrom($source_type, $source_id)
    {
        $table = $this->objectTypeToTable($source_type);
        if (!$table) {
            return false;
        }

        $data = $this->_db->fetch_first_row('SELECT * FROM ?f WHERE `content` = ?'
            , $table, $source_id);
        if (!$data) {
            return false;
        }

        // version author is data moduser (user who made last change on data)
        $user = $data['moduser'];

        // remove some fields from versioning data
        $data = $this->filterVersionedData($data);

        return $this->_versioning->addData($source_type, $source_id, $user, $data);
    }

    /**
     * Compare two arrays of data
     *
     * Method process $new_data with ContentActions::fitlerVersionedData() and
     * than compares all elements from it with data from $old_data array. If
     * the same key has different values in both arrays method returns TRUE,
     * otherwise FALSE will be returned.
     *
     * @param array $new_data
     * @param array $old_data
     * @return bool
     */
    function dataChanged($new_data, $old_data)
    {
        $new_data = $this->filterVersionedData($new_data);

        foreach ($new_data as $field => $value) {
            if ($value != $old_data[$field]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove some elements from data array
     *
     * Method will remove some elements from input array that should not be
     * stored in versioning record.
     *
     * @param array $data
     * @return array
     */
    function filterVersionedData($data)
    {
        foreach ($data as $k => $v) {
            if (in_array($k, array('content', 'moduser', 'moddate', 'pending'
                , 'mpath', 'zort', 'structure', 'first')))
            {
                unset($data[$k]);
            }
        }

        return $data;
    }

    /**
     * Get db table name for storing specified type of objects
     *
     * @param string $object_type
     * @return string|FALSE
     */
    function objectTypeToTable($object_type)
    {
        switch ($object_type) {
            case 'content':
                return 'content';
            case 'template':
                return 'content_templates';
            case 'trash':
                return 'content_trash';
            default:
                return false;
        }
    }

    /**
     * Create new content node based on node from other (source) table
     *
     * @param string $source_table source table name
     * @param int $source_id source node id
     * @param int $ref_node
     * @param string $point one of 'above', 'below' or 'append'
     * @param array $overwrite associative array with fields data that will
     *  be overwritten in the source record
     * @return int|FALSE content node id, or FALSE in case of error
     * @access protected
     */
    function _contentFrom($source_table, $source_id, $ref_node, $point
        , $overwrite = array())
    {
        $fields = $this->_tablesIntersect($source_table, 'content', 'content');
        if (!$fields) {
            return false;
        }

        $data = $this->_db->fetch_first_row("SELECT ?@f FROM ?f WHERE `content` = ?"
            , $fields, $source_table, $source_id);
        if (!$data) {
            return false;
        }

        $data['moddate'] = $this->_db->now();
        if ($overwrite) {
            $data = array_merge($data, $overwrite);
        }

        switch ($point) {
            case 'above':
                $content_id = $this->_contentStrcucture->createNodeAbove($data, $ref_node);
                break;

            case 'below':
                $content_id = $this->_contentStrcucture->createNodeBelow($data, $ref_node);
                break;

            case 'append':
                $content_id = $this->_contentStrcucture->createNodeUnder($data, $ref_node);
                break;

            default:
                return false;
        }

        if ($content_id) {
            if (isset($data['pending']) && 0 == $data['pending']) {
                $this->_pageTags->setTags($content_id, $data['language']
                    , $data['tags']);
                $this->refreshStructure();
            }

            return $content_id;
        } else {
            return false;
        }
    }

    /**
     * New template node from content
     *
     * @param int $content_id
     * @param int $owner new template owner id
     * @return int|FALSE new template node id, or FALSE in case of error
     */
    function templateFromContent($content_id, $owner)
    {
        $fields = $this->_tablesIntersect('content_templates', 'content', 'content');
        $data = $this->_db->fetch_first_row('SELECT * FROM `content` WHERE `content` = ?'
            , $content_id);
        if ($data) {
            // alter data before saving it to templates table
            $data = Arrays::array_intersect_key_val($data, $fields);
            $data['owner'] = $owner;
            $data['moduser'] = $owner;
            $data['moddate'] = $this->_db->now();

            $r = $this->_db->query('INSERT INTO `content_templates` (?@f) VALUES (?@)'
                , array_keys($data), $data);

            if ($r) {
                // create versioning record for new template node
                $template_id = $this->_db->insert_id();
                $this->_versionFrom('template', $template_id);
                $this->logAction('New template page has been created from content page "%s" by %s'
                    , 'template', $template_id);

                return $template_id;
            }
        }

        return false;
    }

    /**
     * Get tables intersection
     *
     * Returns array of columns that present in both tables
     *
     * @param string $table1
     * @param string $table2
     * @param mixed $ignore_fields if specified than these fields will be
     *  ignored from result array
     * @return array|FALSE array of columns, or FALSE in case of error
     * @access protected
     */
    function _tablesIntersect($table1, $table2, $ignore_fields = null)
    {
        if (null !== $ignore_fields && !is_array($ignore_fields)) {
            $ignore_fields = array($ignore_fields);
        }

        $fields1 = $this->_db->fetch_first_col("SHOW COLUMNS FROM ?f", $table1);
        $fields2 = $this->_db->fetch_first_col("SHOW COLUMNS FROM ?f", $table2);

        if (!$fields1 || !$fields2) {
            return false;
        } else {
            $fields = array_intersect($fields1, $fields2);
            if ($ignore_fields) {
                return array_diff($fields, $ignore_fields);
            } else {
                return $fields;
            }
        }
    }

    /**
     * Perform some actions after changes in site structure
     */
    function refreshStructure()
    {
        refresh_rewrite_map($this->_db);
        clearXSLPfiles();
    }

    /**
     * Create new template node
     *
     * @param array $data associative array of template data
     * @return int|FALSE
     */
    function createTemplate($data)
    {
        $r = $this->_db->query('INSERT INTO `content_templates` (?@f) VALUES(?@)'
            , array_keys($data), $data);
        if ($r) {
            $template_id = $this->_db->insert_id();
            $this->_versionFrom('template', $template_id);
            $this->logAction('New template page "%s" has been created by %s'
                , 'template', $template_id);

            return $template_id;

        } else {
            return false;
        }
    }

    /**
     * Create new content node
     *
     * @param array $data associative array of content data
     * @param int $ref_node
     * @param string $point
     * @return int|FALSE
     */
    function createContent($data, $ref_node, $point)
    {
        switch ($point) {
            case 'above':
                $content_id = $this->_contentStrcucture->createNodeAbove($data
                    , $ref_node);
                break;

            case 'below':
                $content_id = $this->_contentStrcucture->createNodeBelow($data
                    , $ref_node);
                break;

            case 'under':
                $content_id = $this->_contentStrcucture->createNodeUnder($data
                    , $ref_node);
                break;

            default:
                return false;
        }

        if ($content_id) {
            $this->_versionFrom('content', $content_id);
            if (!$data['pending']) {
                $this->_pageTags->setTags($content_id, $data['language']
                    , $data['tags']);
                $this->refreshStructure();
                $this->logAction('Published content page "%s" has been created by %s'
                    , 'content', $content_id);
            } else {
                $this->logAction('Unpublished content page "%s" has been created by %s'
                    , 'content', $content_id);
            }

            return $content_id;
        } else {
            return false;
        }
    }

    /**
     * Update content node
     *
     * @param int $content_id
     * @param array $data
     * @return bool
     */
    function updateContent($content_id, $data)
    {
        // get pending status of content node
        $old_pending = $this->_db->fetch_first_value('SELECT `pending` FROM `content`'
            . ' WHERE `content` = ?', $content_id);
        if (false === $old_pending) {
            return false;
        }

        if (MODERA_PENDING_REMOVAL == $old_pending) {
            trigger_error('Editing pending removal content is not allowed', E_USER_ERROR);
            return false;
        }

        $old_data = $this->_versioning->getCurrentData('content', $content_id);
        if (!$old_data) {
            return false;
        }

        // pending status alway will be switched to no pending
        $data['pending'] = 0;

        $r = $this->_db->query('UPDATE `content` SET ?% WHERE `content` = ?'
            , $data, $content_id);

        if ($r) {
            $new_data = $this->_db->fetch_first_row('SELECT * FROM `content`'
                . ' WHERE `content` = ?', $content_id);
            $user = $new_data['moduser'];
            $new_data = $this->filterVersionedData($new_data);

            switch ($old_pending) {
                case MODERA_PENDING_CHANGES:
                    // update last record in versioning table
                    $this->_versioning->updateCurrentData('content', $content_id
                        , $user,  $new_data);

                    $this->logAction('Changes for content page "%s" have been'
                        . ' approved by %s', 'content', $content_id, $user);
                    break;

                case MODERA_PENDING_CREATION:
                    // update last record in versioning table
                    $this->_versioning->updateCurrentData('content', $content_id
                        , $user,  $new_data);

                    $this->logAction('Content page "%s" has been published by %s'
                        , 'content', $content_id, $user);
                    break;

                default:
                    if ($this->dataChanged($new_data, $old_data)) {
                        $this->_versioning->addData('content', $content_id, $user
                            , $new_data);

                        $this->logAction('Content page "%s" has been modified by %s'
                            , 'content', $content_id, $user);
                    }
            }

            $this->_pageTags->setTags($content_id, $new_data['language'], $new_data['tags']);
            $this->refreshStructure();
            return true;

        } else {
            return false;
        }
    }

    /**
     * Update template node
     *
     * @param int $template_id
     * @param array $data
     * @return bool
     */
    function updateTemplate($template_id, $data)
    {
        $old_data = $this->_versioning->getCurrentData('template', $template_id);
        if (!$old_data) {
            return false;
        }

        $r = $this->_db->query('UPDATE `content_templates` SET ?% WHERE `content` = ?'
            , $data, $template_id);
        if ($r) {
            $new_data = $this->_db->fetch_first_row('SELECT * FROM `content_templates`'
                . ' WHERE `content` = ?', $template_id);

            // create new versioning record if data really was changed
            if ($this->dataChanged($new_data, $old_data)) {
                $this->_versionFrom('template', $template_id);
                $this->logAction('Template page "%s" has been updated by %s'
                    , 'template', $template_id);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update removed node
     *
     * @param int $trash_id
     * @param array $data
     * @return bool
     */
    function updateTrash($trash_id, $data)
    {
        $old_data = $this->_versioning->getCurrentData('trash', $trash_id);
        if (!$old_data) {
            return false;
        }

        $r = $this->_db->query('UPDATE `content_trash` SET ?% WHERE `content` = ?'
            , $data, $trash_id);
        if ($r) {
            $new_data = $this->_db->fetch_first_row('SELECT * FROM `content_trash`'
                . ' WHERE `content` = ?', $trash_id);

            // create new versioning record if data really was changed
            if ($this->dataChanged($new_data, $old_data)) {
                $this->_versionFrom('trash', $trash_id);
                $this->logAction('Removed page "%s" has been updated by %s'
                    , 'trash', $trash_id);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Prepare log message
     *
     * @param string $msg
     * @param string $node_type
     * @param int|NULL $node_id
     * @param int|NULL $user_id
     * @return string
     */
    function prepareLogMessage($msg, $node_type, $node_id = null, $user_id = null)
    {
        if (null === $node_id) {
            $user_id = $node_type;
        }

        // hackish solution to get currently logged in user id from
        // globals, usually it gonna work under admin/
        if (null === $user_id && isset($GLOBALS['user'])) {
            $user_id = $GLOBALS['user'];
        }

        // get username
        $username = $this->_db->fetch_first_value("SELECT CONCAT(`name`, ' (',"
            . " `username`, ')') FROM `adm_user` WHERE `user` = ?", $user_id);

        $page_title = $this->_db->fetch_first_value('SELECT `title` FROM ?f WHERE'
            . ' `content` = ?', $this->objectTypeToTable($node_type), $node_id);

        return sprintf($msg, $page_title, $username);
    }

    /**
     * Send message to system log
     *
     * @param string $msg
     * @param string $node_type
     * @param int $node_id
     * @param int $user_id
     */
    function logAction($msg, $node_type = null, $node_id = null, $user_id = null)
    {
        if (null === $node_type) {
            $this->_systemLog->log('content_actions', $msg);
        } else {
            $this->_systemLog->log('content_actions', $this->prepareLogMessage($msg
                , $node_type, $node_id, $user_id));
        }
    }
}