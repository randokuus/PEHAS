<?php
/**
 * @version $Revision: 678 $
 */

require_once(SITE_PATH . '/class/aliases_helpers.php');
require_once(SITE_PATH . '/class/cache_helpers.php');
require_once(SITE_PATH . '/class/Arrays.php');

/**
 * Class for handling content workflow related operations
 *
 * Intented to automatically handle decline, cancel and approve operations on
 * content nodes. Class is aware of permissions and objects versioning.
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class ContentWorkflow
{
    /**
     * Database instance
     *
     * @var Database
     * @access protected
     */
    var $_db;

    /**
     * Permission checking class instance (class Rights)
     *
     * @var Rights
     * @access protected
     */
    var $_rights;

    /**
     * ContentActions instance
     *
     * @var ContentActions
     * @access protected
     */
    var $_contentActions;

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
    var $_contentStructure;

    /**
     * PageTags class instance
     *
     * @var PageTags
     * @access protected
     */
    var $_pageTags;

    /**
     * Class constructor
     *
     * @param Database $database
     * @param Rights $rights
     */
    function ContentWorkflow(&$database, &$rights)
    {
        $this->_db = &$database;
        $this->_rights = &$rights;
    }

    /**
     * Set Versioning instance
     *
     * @param Versioning $versioning
     */
    function setVersioning(&$versioning)
    {
        $this->_versioning = &$versioning;
    }

    /**
     * Set ContentStructure instance
     *
     * @param ContentStructure $contentStructure
     */
    function setContentStructure(&$contentStructure)
    {
        $this->_contentStructure = &$contentStructure;
    }

    /**
     * Set ContentActions instance
     *
     * @param ContentActions $contentActions
     */
    function setContentActions(&$contentActions)
    {
        $this->_contentActions = &$contentActions;
    }

    /**
     * Set PageTags instance
     *
     * @param PageTags $pageTags
     */
    function setPageTags(&$pageTags)
    {
        $this->_pageTags = &$pageTags;
    }

    /**
     * Approve operation performed on content page
     *
     * @param int $content_id
     * @return bool|int FALSE if approving failed, TRUE or trash node id if
     *  everything was successfull
     */
    function approve($content_id)
    {
        // determine changes type and call appropriate decline function
        $pending = $this->_db->fetch_first_value('SELECT `pending` FROM `content`'
            . ' WHERE `content` = ?', $content_id);

        if (!$this->canApprove($content_id, $pending)) {
            return false;
        }

        switch ($pending) {
            case MODERA_PENDING_CHANGES:
                return $this->_approveChanges($content_id);

            case MODERA_PENDING_REMOVAL:
                return $this->_contentActions->moveContentToTrash($content_id);

            case MODERA_PENDING_CREATION:
                return $this->_approveCreation($content_id);

            default:
                return false;
        }
    }

    /**
     * Approve changes made with content page
     *
     * @param int $content_id
     * @return bool
     * @access private
     */
    function _approveChanges($content_id)
    {
        $r = $this->_db->query('UPDATE `content` SET `pending` = 0 WHERE `content` = ?'
            , $content_id);
        if (!$r) {
            return false;
        }

        // get language and tags for page
        list($language, $tags) = $this->_db->fetch_first_row('SELECT `language`'
            . ', `tags` FROM `content` WHERE `content` = ?', $content_id);

        // remove pending notice
        $this->clearNotice($content_id);
        $this->_pageTags->setTags($content_id, $language, $tags);
        $this->_contentActions->refreshStructure();
        $this->_contentActions->logAction('Changes for content page "%s" have'
            . ' been approved by %s', 'content', $content_id, $this->_rights->user);
        return true;
    }

    /**
     * Approve page creation
     *
     * @param int $content_id
     * @return bool
     * @access private
     */
    function _approveCreation($content_id)
    {
        // technically approving page creation and page changes
        // are the same, that's why here we just redirecting to approveChanges method
        return $this->_approveChanges($content_id);
    }

    /**
     * Cancel operations performed with content page
     *
     * @param int $content_id
     * @return mixed
     */
    function cancel($content_id)
    {
        $pending = $this->_db->fetch_first_value('SELECT `pending` FROM `content`'
            . ' WHERE `content` = ?', $content_id);

        if (!$this->canCancel($content_id, $pending)) {
            return false;
        }

        switch ($pending) {
            case MODERA_PENDING_CHANGES:
                // remove the most recent version from the database
                $this->_contentActions->logAction('Content changes for "%s" have been'
                    . ' cancelled by %s', 'content', $content_id, $this->_rights->user);

                $this->_versioning->removeLastVersion('content', $content_id);
                return (bool) $this->_db->query('UPDATE `content` SET `pending` = 0'
                    . ' WHERE `content` = ?', $content_id);

            case MODERA_PENDING_REMOVAL:
                $this->_contentActions->logAction('Removal of content page "%s" has been'
                    . ' cancelled by %s', 'content', $content_id, $this->_rights->user);

                $this->clearNotice($content_id);
                return (bool) $this->_db->query('UPDATE `content` SET `pending` = 0'
                    . ' WHERE `content` = ?', $content_id);

            case MODERA_PENDING_CREATION:
                $this->_contentActions->logAction('Creation of content page "%s" has been'
                    . ' cancelled by %s', 'content', $content_id, $this->_rights->user);

                $this->clearNotice($content_id);
                return $this->_contentActions->moveContentToTrash($content_id);

            default:
                return false;
        }
    }

    /**
     * Decline operation performed with content page
     *
     * @param int $content_id
     * @param string $message decline description message
     * @return bool
     */
    function decline($content_id, $message = '')
    {
        if ('' == $message) {
            $message = 'no message';
        }

        // determine changes type and call appropriate decline function
        $pending = $this->_db->fetch_first_value('SELECT `pending` FROM `content`'
            . ' WHERE `content` = ?', $content_id);

        if (!$this->canDecline($content_id, $pending)) {
            return false;
        }

        switch ($pending) {
            case MODERA_PENDING_CHANGES:
                return $this->_declineChanges($content_id, $message);

            case MODERA_PENDING_REMOVAL:
                return $this->_declineRemoval($content_id);

            case MODERA_PENDING_CREATION:
                return $this->_declineCreation($content_id, $message);

            default:
                return false;
        }
    }

    /**
     * Decline content changes
     *
     * @param int $content_id
     * @param string $message
     * @return bool
     * @access private
     */
    function _declineChanges($content_id, $message)
    {
        // update the most recent record in versioning table with decline message
        // and user
        $this->_versioning->injectToCurrentData('content', $content_id, array(
            '__notice' => $message, '__decline_user' => $this->_rights->user));

        $this->_contentActions->logAction('Changes made to content page "%s" have been'
            . ' declined by %s', 'content', $content_id, $this->_rights->user);

        return true;
    }

    /**
     * Decline content removal
     *
     * @param int $content_id
     * @return bool
     * @access private
     */
    function _declineRemoval($content_id)
    {
        $this->_contentActions->logAction('Removal of content page "%s" has been'
            . ' declined by %s', 'content', $content_id, $this->_rights->user);

        return (bool) $this->_db->query('UPDATE `content` SET `pending` = 0 WHERE '
            . ' `content` = ?' , $content_id);
    }

    /**
     * Decline content creation
     *
     * @param int $content_id
     * @param string $message
     * @return bool
     * @access private
     */
    function _declineCreation($content_id, $message)
    {
        return $this->_declineChanges($content_id, $message);
    }

    /**
     * Check permissions using Rights object
     *
     * @param int $content_id
     * @param string $action
     * @return bool
     * @access protected
     */
    function _checkAccess($content_id, $action)
    {
        return $this->_rights->Access(null, $content_id, $action, null);
    }

    /**
     * Helper method for removing notice message from versioning table
     *
     * @param int $content_id
     */
    function clearNotice($content_id)
    {
        $this->_versioning->removeFromCurrentData('content', $content_id
            , array('__notice', '__decline_user'));
    }

    /**
     * Check if user has permission to approve specified page
     *
     * @param int $content_id
     * @param int $pending
     * @return bool
     */
    function canApprove($content_id, $pending)
    {
        if (!$this->_rights->canPublish()) {
            return false;
        }

        switch ($pending) {
            case MODERA_PENDING_CHANGES:
                return $this->_checkAccess($content_id, 'm');

            case MODERA_PENDING_REMOVAL:
                return $this->_checkAccess($content_id, 'd');

            case MODERA_PENDING_CREATION:
                $parent_id = $this->_contentStructure->getParent($content_id);
                return $this->_checkAccess($parent_id, 'a');

            default:
                return false;
        }
    }

    /**
     * Check if user has permission to decline specified page
     *
     * @param int $content_id
     * @param int $pending
     * @return bool
     */
    function canDecline($content_id, $pending)
    {
        // for declining some action user should has the same permissions
        // as for approving it
        return $this->canApprove($content_id, $pending);
    }

    /**
     * Check if user has permission to cancel changes
     *
     * @param int $content_id
     * @param int $pending
     * @return bool
     */
    function canCancel($content_id, $pending)
    {
        switch ($pending) {
            case MODERA_PENDING_CHANGES:
                return $this->_checkAccess($content_id, 'm');

            case MODERA_PENDING_CREATION:
                return $this->_checkAccess($content_id, 'd');

            case MODERA_PENDING_REMOVAL:
                return $this->_checkAccess($content_id, 'm');

            default:
                return false;
        }
    }

    /**
     * Remove nodes from trash
     *
     * NB! Only nodes for which user have 'd' permission will be removed
     *
     * @return int number of removed nodes
     */
    function emptyTrash()
    {
        $removed = 0;
        $nodes = $this->_db->fetch_first_col("SELECT `content` FROM `content_trash`");
        if ($nodes) {
            foreach ($nodes as $node_id) {
                if ($this->_checkAccess($node_id, 'd')) {
                    $removed += $this->_contentActions->removeFromTrash($node_id);
                }
            }
        }

        return $removed;
    }

    /**
     * Create new template node
     *
     * @param array $node_data
     * @return int|FALSE
     */
    function createTemplate($node_data)
    {
        return $this->_contentActions->createTemplate($node_data);
    }

    /**
     * Create new content node
     *
     * This methods checks if current user has enough permissions for creating
     * new node under specified location and
     *
     * @param array $node_data
     * @param int $ref_node
     * @param string $point 'above', 'below' or 'under'
     * @return int|FALSE
     */
    function createContent($node_data, $ref_node, $point)
    {
        // get parent node id
        if ('below' == $point || 'above' == $point) {
            $parent_id = $this->_contentStructure->getParent($ref_node);
        } else if ('under' == $point) {
            $parent_id = $ref_node;
        } else {
            return false;
        }

        // check if user has add permission on parent node
        if (!$this->_checkAccess($parent_id, 'a')) {
            return false;
        }

        // process publish permission
        if ($this->_rights->canPublish()) {
            $node_data['pending'] = 0;
        } else {
            $node_data['pending'] = MODERA_PENDING_CREATION;
        }

        return $this->_contentActions->createContent($node_data, $ref_node, $point);
    }

    /**
     * Update node
     *
     * Updates template, content and trash nodes. Checks permissions and handles
     * related versioning records.
     *
     * @param string $node_type
     * @param array $node_data associative array of node data
     * @return bool
     */
    function update($node_type, $node_id, $node_data)
    {
        $table = $this->_contentActions->objectTypeToTable($node_type);
        if (!$table) {
            return false;
        }

        if (!$this->_checkAccess($node_id, 'm')) {
            return false;
        }

        switch ($node_type) {
            case 'content':
                if ($this->_rights->canPublish()) {
                    return $this->_contentActions->updateContent($node_id, $node_data);

                } else {
                    // user does not have publish content permission and real
                    // table will not be updated here, code is similar to
                    // ContentActions::updateContent() however contains a few
                    // serious differences.
                    // yes, this is weird hackish solution to place this code here

                    $pending = $this->_db->fetch_first_value('SELECT `pending`'
                        . ' FROM ?f WHERE `content` = ?', $table, $node_id);

                    if (false === $pending) {
                        return false;
                    }

                    if (MODERA_PENDING_REMOVAL == $pending) {
                        trigger_error('Editing pending removal content is not allowed'
                            , E_USER_ERROR);
                        return false;
                    }

                    $old_data = $this->_versioning->getCurrentData($node_type, $node_id);
                    if ($old_data) {
                        // filter out elelemnts with keys that are not exists
                        // in target table
                        $fields = $this->_db->fetch_first_col("SHOW COLUMNS FROM ?f", $table);
                        $old_data = Arrays::array_intersect_key_val($old_data, $fields);

                    } else {
                        return false;
                    }

                    if (MODERA_PENDING_CREATION == $pending) {
                        // update real table
                        $this->_db->query('UPDATE ?f SET ?% WHERE `content` = ?'
                            , $table, $node_data, $node_id);
                        $new_data = $this->_db->fetch_first_row('SELECT * FROM ?f'
                            . ' WHERE `content` = ?', $table, $node_id);

                    } else {
                        // create temporary table and insert data from real table into it
                        // than fetch this data back to get real new_data that can be
                        // compared with old one
                        $tmptable = 'tmp_' . $table;

                        // since CREATE TABLE LIKE is supported only from MySQL 4.1
                        // we manually create DDL for temporary table using
                        // SHOW CREATE TABLE http://dev.mysql.com/doc/refman/4.1/en/show-create-table.html
                        $this->_db->query('SET SQL_QUOTE_SHOW_CREATE = 1');
                        $ddl = end($this->_db->fetch_first_row('SHOW CREATE TABLE `content`'));
                        $ddl = 'CREATE TEMPORARY TABLE ' . $this->_db->quote_field_name($tmptable)
                            . ' ' . substr($ddl, strpos($ddl, '('));
                        $ddl = substr($ddl, 0, strrpos($ddl, ')') + 1);

                        $r = $this->_db->query($ddl);
                        if (false == $r) {
                            trigger_error('Unable to create temporary database table'
                                , E_USER_ERROR);
                            return false;
                        }

                        $this->_db->query('INSERT INTO ?f (?@f) VALUES(?@)', $tmptable
                            , array_keys($old_data), $old_data);
                        $this->_db->query('UPDATE ?f SET ?%', $tmptable, $node_data);

                        $new_data = $this->_db->fetch_first_row('SELECT * FROM ?f LIMIT 1'
                            , $tmptable);
                    }

                    $user = $new_data['moduser'];
                    $new_data = $this->_contentActions->filterVersionedData($new_data);

                    if ($this->_contentActions->dataChanged($new_data, $old_data)) {
                        if (MODERA_PENDING_CREATION == $pending
                            || MODERA_PENDING_CHANGES == $pending)
                        {
                            $this->_versioning->updateCurrentData($node_type, $node_id
                                , $user, $new_data);
                            $this->clearNotice($node_id);

                        } else {
                            $this->_versioning->addData($node_type, $node_id, $user
                                , $new_data);
                            $this->_db->query('UPDATE ?f SET `pending` = ? WHERE'
                                . ' `content` = ?', $table, MODERA_PENDING_CHANGES
                                , $node_id);
                        }

                        $this->_contentActions->logAction('Content page "%s" has been'
                            . ' updated by %s. Changes are not published yet.'
                            , 'content', $node_id, $this->_rights->user);
                    }

                    return true;
                }

                break;

            case 'template':
                return $this->_contentActions->updateTemplate($node_id, $node_data);

            case 'trash':
                return $this->_contentActions->updateTrash($node_id, $node_data);

            default:
                return false;
        }
    }
}