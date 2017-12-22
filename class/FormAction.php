<?php
/**
 * @version $Revision: 335 $
 */

require_once(SITE_PATH . '/class/FactoryPattern.php');
require_once(SITE_PATH . '/class/Arrays.php');

/**
 * Abstract form action
 *
 * This is abstract class for implementing concrete realisations of form actions
 *
 * @author Priit Pyld <priit.pold@modera.net>
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 * @abstract
 */
class FormAction
{
    /**
     * Array of invalid fields
     *
     * @var array
     * @access private
     */
    var $_invalidFields;

    /**
     * Form numeric id
     *
     * @var int
     * @access private
     */
    var $_form_id;

    /**
     * Boolean flag identifying whether form is empty or not
     *
     * @var bool
     * @access private
     */
    var $_empty_form;

    /**
     * Database object
     *
     * @var Database
     * @access protected
     */
    var $_db;

    /**
     * Translator instance
     *
     * @var Translator
     * @access protected
     */
    var $_translator;

    /**
     * Action parameters array
     *
     * @var array
     * @access private
     */
    var $_params;

    /**
     * Form title
     *
     * @see _formTitle()
     * @var string
     * @access private
     */
    var $_formTitle;

    /**
     * FormAction constructor
     *
     * @param int $form_id
     * @param Database $db
     * @param Translator $translator
     * @return FormAction
     */
    function FormAction($form_id, &$db, &$translator)
    {
        $this->_invalidFields = array();
        $this->_db = &$db;
        $this->_translator = &$translator;
        $this->_form_id = $form_id;
        $this->_empty_form = false;
        $this->_params = null;
        $this->userid = $GLOBALS["user_data"][0];
        $this->user_name = $GLOBALS["user_data"][1];
        $this->username = $GLOBALS["user_data"][2];
        $this->user_email = $GLOBALS["user_data"][8];
    }

    /**
     * Get form action instance
     *
     * @param string $driver
     * @param int $form_id
     * @param Database $db
     * @param Translator $translator
     * @return FormAction
     * @static
     */
    function &driver($driver, $form_id, &$db, &$translator)
    {
        $obj =& FactoryPattern::factory('FormAction', $driver, dirname(__FILE__)
            , array($form_id, &$db, &$translator));
        return $obj;
    }

    /**
     * Array of available form actions
     *
     * @staticvar array $available cache
     * @return array
     * @static
     */
    function available()
    {
        static $available = null;
        if (is_null($available)) $available = FactoryPattern::available('FormAction'
            , dirname(__FILE__));
        return $available;
    }

    /**
     * Returns array with configuration fields metadata
     *
     * @param string $driver
     * @return array
     * @static
     */
    function getConfigFields($driver)
    {
        $class_name = 'FormAction_' . $driver;
        if (!class_exists($class_name)) {
            $file_name = dirname(__FILE__) . '/FormAction/' . $driver . '.php';
            if (!is_readable($file_name)) {
                trigger_error("Cannot open file $file_name", E_USER_ERROR);
            } else {
                include_once($file_name);
            }
        }

        return call_user_func(array($class_name, 'configFields'));
    }

    /**
     * Configuration fields metadata
     *
     * This method might be overwritten in the child classes, if it will
     * not be overwritten than this version of method will be called
     * returning empty array.
     *
     * @return array
     */
    function configFields()
    {
        return array();
    }

    /**
     * Submit form handler
     *
     * @param array $data
     */
    function submitForm($data)
    {
        // validate form
        if ($this->_validateForm($data)) {
            $this->_processFormData($data);
        }
    }

    /**
     * Process form data
     *
     * @param array $data
     * @access protected
     * @abstract
     */
    function _processFormData($data) {}

    /**
     * Set invalid field name and error description
     *
     * @param string $field_name
     * @param string $error_msg
     * @access protected
     */
    function _setInvalidField($field_name, $error_msg = '')
    {
        $this->_invalidFields[$field_name] = $error_msg;
    }

    /**
     * Get error message for specified form field
     *
     * @param string $field_name
     * @return string|NULL error message, or NULL if field was validated successfully
     */
    function fieldError($field_name)
    {
        return isset($this->_invalidFields[$field_name]) ? $this->_invalidFields[$field_name]
            : null;
    }

    /**
     * Return array of invalid fields with error messages
     *
     * @return array
     */
    function invalidFields()
    {
        return $this->_invalidFields;
    }

    /**
     * Get form validation status
     *
     * @return bool TRUE if form was validated successfully, FALSE otherwise
     */
    function isValid()
    {
        return ($this->_empty_form || count($this->_invalidFields)) ? false : true;
    }

    /**
     * Standard form validation
     *
     * Validates form data using constraints set by used in admin interface
     *
     * @param array $data
     * @return bool
     * @access private
     */
    function _validateForm(&$data)
    {
        $fields_meta = $this->_getFormFieldsMeta();
        if (!count($fields_meta)) {
            $this->_empty_form = true;
            return false;
        }

        $processed_data = array();
        foreach ($fields_meta as $field_num => $field_meta) {
            $field_name = 'field' . ++$field_num . '_1';

            // trim text input fields data
            if (($field_meta['type'] == 1 || $field_meta['type'] == 2
                || $field_meta['type'] == 3) &&  isset($data[$field_name]))
            {
                $data[$field_name] = trim($data[$field_name]);
            }

            // check if field is required and was not filled
            if ($field_meta['required'] && (!isset($data[$field_name])
                || '' == $data[$field_name]))
            {
                $this->_setInvalidField($field_name,
                    $this->_translator->tr('wrong_or_empty_field',array($field_meta['name'])));
            }

            switch ($field_meta['type']) {
                case 5:
                    // convert values of checkbox fields
                    $processed_data[] = array($field_meta['name']
                        , $this->_translator->tr('checkbox_'. $data[$field_name] ? 1 : 0));
                    break;

                case 6:
                case 7:
                case 8:
                    // convert options values
                    $options = split(';;', $field_meta['options']);
                    if (!is_array($data[$field_name])) {
                        $data[$field_name] = array($data[$field_name]);
                    }
                    foreach ($data[$field_name] as $k => $v) {
                        $data[$field_name][$k] = $options[$v-1];
                    }

                    $processed_data[] = array($field_meta['name']
                        , implode(', ', $data[$field_name]));
                    break;

                default:
                    $processed_data[] = array($field_meta['name'], $data[$field_name]);
            }
        }

        $data = $processed_data;
        return $this->isValid();
    }

    /**
     * Get form metadata
     *
     * Resulting array will look like:
     * <pre>
     * array(
     *   array(
     *     'name' => 'aname',
     *     'type' => 'atype',
     *     'options' => 'anoptions',
     *     'required', 'arequired',
     *   ),
     * )
     * </pre>
     *
     * @return array
     * @access private
     */
    function _getFormFieldsMeta()
    {
        return $this->_db->fetch_all('SELECT `name`, `type`, `options`, `required` FROM '
            . ' `module_form2_fields` WHERE `form` = ? ORDER BY prio ASC', $this->_form_id);
    }

    /**
     * Get form numeric id
     *
     * @return int
     */
    function formId()
    {
        return $this->_form_id;
    }

    /**
     * Checks if worm has no fields configured
     *
     * @return bool
     */
    function isEmptyForm()
    {
        return $this->_empty_form;
    }

    /**
     * Get configuration parameters for current action
     *
     * @return array
     */
    function getActionParams()
    {
        if (is_null($this->_params)) {
        	$result =& $this->_db->query('SELECT `key`, `value` FROM `module_form2_config` '
        	   . ' WHERE `form_id` = ?', $this->_form_id);
        	$this->params = array();
        	while($row = $result->fetch_assoc()) {
        	    $this->params[$row['key']] = $row['value'];
        	}
        }

    	return $this->params;
    }

    /**
     * Return submitted form title
     *
     * @return string
     * @access protected
     */
    function _formTitle()
    {
        if (is_null($this->_formTitle)) {
            $this->_formTitle = $this->_db->fetch_first_value('SELECT `title` '
                . ' FROM `module_form2` WHERE `id` = ?', $this->_form_id);
        }

        return $this->_formTitle;
    }
}
