<?php
/**
 * @version $Revision: 560 $
 */

/**
 * Log class
 *
 * Provides general logging functionality. Example:
 * <pre>
 * // static log call
 * SystemLog::staticLog('core', 'Description of message from code');
 *
 * // normal log calls
 * $log = &SystemLog::instance($database);
 * $log->log('file_manager', 'File xxxx.xx uploaded by xxx to /xxx');
 * $log->log('content_editor', 'Changes to xxx page submitted by xxx');
 *
 * print_r($log->getLog());
 * </pre>
 *
 * @author Stanislav Chichkan <stas@itworks.biz.ua>
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class SystemLog
{
    /**
     * Database instance
     *
     * @var Database
     * @access protected
     */
    var $_db;

    /**
     * Database table for storing logs
     *
     * Table with the following structure:
     * CREATE TABLE `systemlog` (
     *   `log_id` int(10) unsigned NOT NULL auto_increment,
     *   `time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
     *   `source` varchar(50) NOT NULL,
     *   `message` varchar(255) NOT NULL,
     *   PRIMARY KEY  (`log_id`),
     *   KEY `time` (`time`),
     *   KEY `source` (`source`)
     * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
     * </pre>
     *
     * @var string
     * @access private
     */
    var $_table = 'systemlog';

    /**
     * Class constructor
     *
     * @param Database $database
     * @return SystemLog
     */
    function SystemLog(&$database)
    {
        $this->_db =& $database;
    }

    /**
     * Get Log instance
     *
     * Returns Log instance, when needed creates object.
     *
     * @param Database $database Database instance
     * @return SystemLog
     * @static
     */
    function &instance(&$database)
    {
        static $instance;
        if (!$instance) {
            $instance = new SystemLog($database);
        }

        return $instance;
    }

    /**
     * Write message to system log
     *
     * @param string $source log source (core, file manager, content manager, etc.)
     * @param string $message log message
     * @return bool TRUE if message was logged successfully, FALSE otherwise
     */
    function log($source, $message)
    {
        return (bool) $this->_db->query('INSERT INTO ?f (`source`, `message`) values(?,?)'
            , $this->_table, $source, $message);
    }

    /**
     * Write message to system log
     *
     * Static version of SystemLog::log() method.
     *
     * @param string $source
     * @param string $message
     * @param Database $database
     * @return bool TRUE if message was logged successfully, FALSE otherwise
     * @static
     */
    function staticLog($source, $message, &$database)
    {
        $log = &SystemLog::instance($database);
        return $log->log($source, $message);
    }

    /**
     * Get log messages
     *
     * @param int|NULL $offset
     * @param int|NULL $count
     * @param string|NULL $source if paramer specified than log messages from
     *  this source only will be returned
     * @return array
     */
    function getLog($offset = null, $count = null, $source = null)
    {
        if (null === $count && null !== $offset) {
            // wrong input, silently reipairing
            trigger_error('Wrong input. Offset could not be used without Count'
                , E_USER_ERROR);
            return;
        }

        // construct LIMIT clause
        if (null !== $count) {
            $count = (int) $count;
            if (null !== $offset) {
                $offset = (int) $offset;
                $limit = "LIMIT $offset, $count";
            } else {
                $limit = "LIMIT $count";
            }
        } else {
            $limit = '';
        }

        if (null === $source) {
            return $this->_db->fetch_all('SELECT `log_id`, `source`, `message`, `time`'
                . ' FROM ?f ORDER BY `date` DESC !', $this->_table, $limit);
        } else {
            return $this->_db->fetch_all('SELECT `log_id`, `source`, `message`, `time`'
                . ' FROM ?f WHERE `cource` = ? ORDER BY `date` DESC !'
                , $this->_table, $source, $limit);
        }
    }
}