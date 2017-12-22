<?php
/**
 * Captcha class.
 *
 * @version $Revision: 337 $
 */

/**
 * Main Captcha class.
 *
 * Adds possibility to identify human been.
 * Generate code text, generate image and output it to the page.
 *
 * @author Priit Pold <priit.pold@modera.net>
 */
class Captcha{

    /**
     * Code text.
     *
     * @var string.
     * @access private
     */
    var $_code;

    /**
     * Set of characters to use in code generation.
     *
     * @var array.
     * @access private
     */
    var $_charSet;

    /**
     * String of characters to use in code generation.
     * For optimisation.Not implemented yet.
     *
     * @var string
     * @access private
     */
    var $_chars;

    /**
     * Name of key for captcha to store in SESSION.
     *
     * @var string
     * @access private
     */
    var $_captcha_sid_name;

    /**
     * Last error string.
     * Stores last error.
     *
     * @var string
     * @access private
     */
    var $_lastError;

    /**
     * Captcha options.
     *
     * @var array.
     * @access private
     */
    var $_options = array(
        'chars_num'     => 5,
        'case_sensitive'=> false,
    );

    var $_captcha_name;

    /**
     * Main constructor.
     *
     * @param string $captcha_key - captcha key name.
     *  if you want to use more than one CAPTCHA, then name is needed
     *  to separate others captchas from this one. Used for storing under uniq name.
     * @return Captcha
     */
    function Captcha($_captcha_name = 'code')
    {
        // at the beggining code string is empty.
        $this->_code = '';

        // set chars set to default
        $this->setCharSet('a-h,j-n,p-z,2-9');

        // set char string to ''
        $this->_chars = '';

        // set key name of session to:
        $this->_captcha_sid_name = 'CAPTCHA';

        $this->_captcha_name = trim(htmlspecialchars(strip_tags($_captcha_name)));
        if (!strlen($this->_captcha_name)) $this->_captcha_name = 'code';

        // check session. if needed start it.
        $this->sessionInit();
    }


    /**
     * Initializate SESSION if it's not started.
     */
    function sessionInit()
    {
        if (!isset($_SESSION)){
            session_start();
        }
        if (!isset($_SESSION[$this->_captcha_sid_name])){
            $_SESSION[$this->_captcha_sid_name] = array();
        }
    }

    /**
     * Validates input captcha code text.
     *
     * @param string $text - input text to validate
     * @return bool - if input text equals generated code, return TRUE,
     *  otherwise FALSE.
     */
    function validate($text='')
    {
        if (!$this->_options['case_sensitive']){
            $text = strtoupper($text);
        }

        if ($_SESSION[$this->_captcha_sid_name][$this->_captcha_name] === $text) {
            return true;
        }
        return false;
    }

    /**
     * Generate and set new CAPTCHA code.
     * @param string|null - string to set as code.
     */
    function generateSetNewCode($text = null)
    {
        if (is_null($text) || !strlen($text)){
            $text = $this->generateRandomCode();
        }
        $this->_setCode($text);
        return $text;
    }

    /**
     * Sets code string.
     *
     * @param string $text - code string.
     * @access private
     */
    function _setCode($text)
    {
        $this->_code = $text;
        $_SESSION[$this->_captcha_sid_name][$this->_captcha_name] = $text;
    }

    /**
     * Set character set.
     * Set characters to use in code generation.
     *
     * @param mixed $vCharSet - characters set.
     * usage: array- array('a','b','c','f'...'z')<br />
     *        string-'a,s,v,d,e,q,y,u,j' OR 'a-z,0-1'
     */
    function setCharSet($vCharSet)
    {
        // check for input type
        if (is_array($vCharSet)) {
            $this->_charSet = $vCharSet;
        } else {
            if ($vCharSet != '') {
                // split items on commas
                $aCharSet = explode(',', $vCharSet);

                // initialise array
                $this->_charSet = array();

                // loop through items
                foreach ($aCharSet as $sCurrentItem) {
                    // a range should have 3 characters, otherwise is normal character
                    if (strlen($sCurrentItem) == 3) {
                        // split on range character
                        $aRange = explode('-', $sCurrentItem);

                        // check for valid range
                        if (count($aRange) == 2 && $aRange[0] < $aRange[1]) {
                            // create array of characters from range
                            $aRange = range($aRange[0], $aRange[1]);

                            // add to charset array
                            $this->_charSet = array_merge($this->_charSet, $aRange);
                        }
                    } else {
                        $this->_charSet[] = $sCurrentItem;
                    }
                }
            }
        }
    }

    /**
     * Generates random string.
     * Generates random string using installed characters and options.
     *
     * @return string - randomly generated string.
     */
    function generateRandomCode()
    {
        // reset code
        $count = count($this->_charSet);
        $code = '';
        // loop through and generate the code letter by letter
        for ($i = 0; $i < $this->_options['chars_num']; $i++) {
            if ($count > 0) {
                // select random character and add to code string
                $code .= $this->_charSet[array_rand($this->_charSet)];
            } else {
                // select random character and add to code string
                $code .= chr(rand(65, 90));
            }
        }

        if ($this->_options['case_sensitive']) {
            return $code;
        } else {
            return strtoupper($code);
        }
    }

}
