<?php
/**
 * @version $Revision: 433 $
 */

/**
 * Event manger
 *
 * Simplified version of observer patters that allows to fire and catch events
 * using global shared event manager component
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class EventManager
{
    /**
     * Array of registered listeners
     *
     * @var array
     * @access protected
     */
    var $_listeners;

    /**
     * Get event manager instance
     *
     * @return EventManager
     * @static
     */
    function &instance()
    {
        static $instance;
        if (!$instance) {
            $instance = new EventManager();
        }
        return $instance;
    }

    /**
     * Constructor
     *
     * @return EventManager
     */
    function EventManager()
    {
        $this->_listeners = array();
    }

    /**
     * Attach listener for specified event
     *
     * @param string $event system wide event name
     * @param mixed $callback listener function name of array with two elements:
     *  class name, method name
     * @return int index inside listeners array that can be used later to remove
     *  listener by this index
     */
    function on($event, $callback)
    {
        if (!isset($this->_listeners[$event]) || !count($this->_listeners[$event])) {
            $this->_listeners[$event] = array();
            $key = 0;
        } else {
            end($this->_listeners[$event]);
            $key = key($this->_listeners[$event]) + 1;
        }

        $this->_listeners[$event][$key] = $callback;
        return $key;
    }

    /**
     * Detach listener from event
     *
     * @param string $event system wide event name
     * @param mixed $listener might be the same value as was passed to ::on() method,
     *  or index number returned by ::on()
     * @return bool TRUE if listener was removed successfully, FALSE otherwise
     */
    function un($event, $listener)
    {
        $unset = false;
        if (isset($this->_listeners[$event])) {
            if (is_numeric($listener)) {
                if (isset($this->_listeners[$event][$listener])) {
                    unset($this->_listeners[$event][$listener]);
                    $unset = true;
                }
            } else {
                $key = array_search($listener, $this->_listeners[$event], true);
                if (false === $key || null === $key) {
                    unset($this->_listeners[$event][$key]);
                    $unset = true;
                }
            }
        }

        return $unset;
    }

    /**
     * Trigger event
     *
     * @param string $event event name
     * @param array $data array with event data passed to listeners
     * @return int number of called listeners
     */
    function fire($event, $data)
    {
        $called = 0;
        if (isset($this->_listeners[$event])) {
            foreach ($this->_listeners[$event] as $callback) {
                call_user_func($callback, $data);
            }
        }

        return $called;
    }
}