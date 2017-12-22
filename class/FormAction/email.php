<?php
/**
 * @version $Revision: 690 $
 */

include_once(SITE_PATH . "/class/mail/htmlMimeMail.php");

/**
 * Email action
 *
 * Class for handling sending submitted forms by email
 *
 * @author Priit Pyld <priit.pyld@modera.net>
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class FormAction_email extends FormAction
{
    /**
     * @return array
     */
    function configFields()
    {
        return array(
            'email' => array(
                'type' => 'textinput',
                'max'  => 255,
                'size' => 25,
            ),
        );
    }

    /**
     * @param array $data
     * @access protected
     */
    function _processFormData($data)
    {
        $email = array_pop($this->getActionParams());
        $email = str_replace(',', ';', $email);

        $_emails = (array)explode(';', $email);

        $emails = array();
        foreach ($_emails as $_email) {
            $_email = trim($_email);
            if ($_email && validateEmail($_email)) {
                $emails[] = $_email;
            }
        }

        if (!count($emails)) {
            return;
        }

        $message_body = $this->_constructMailBody($data);
        $subject = $this->_db->fetch_first_value('SELECT `title` FROM `module_form2`'
            . ' WHERE `id` = ?', $this->_form_id);

        $mail = new htmlMimeMail();
        $mail->setHtml($message_body, returnPlainText($message_body));
        if ($GLOBALS["site_admin_name"] && validateEmail($GLOBALS["site_admin"])) {
            $mail->setFrom($GLOBALS["site_admin_name"] . " <" . $GLOBALS["site_admin"] . ">");
            $mail->setReturnPath($GLOBALS['site_admin']);
        } else {
            $mail->setFrom("Modera <info@modera.net>");
            $mail->setReturnPath('info@modera.net');
        }

        $mail->setSubject(htmlspecialchars($subject));
        $mail->send($emails);
    }

    /**
     * Constructs mail message body
     *
     * @param array $data
     * @return string
     * @access protected
     */
    function _constructMailBody($data)
    {
        $mail = '';
        foreach ($data as $field) {
            list($field_name, $field_value) = $field;
            $field_name = htmlspecialchars($field_name);
            $field_value = htmlspecialchars(trim($field_value));
            $mail .= "<b>$field_name</b>:$field_value<br />\n";
        }

        $mail .= "\n<br /><b>Date:</b> ". date("d.m.Y H:i") . "<br />\n";
        $mail .= "<b>Remote addr:</b> " . htmlspecialchars($_SERVER["REMOTE_ADDR"]) . "<br />\n";
        if ($this->userid) {
            $mail .= "<b>User:</b> " . $this->user_name . " (" . $this->username . ", " . $this->user_email . ")<br />\n";
        }

        // add logged in user information
        if (isset($GLOBALS['userdata'][0])) {
            $userid = htmlspecialchars($GLOBAL['userdata'][0]);
            $username = htmlspecialchars($GLOBALS['userdata'][2]);
            $user_fullname = htmlspecialchars($GLOBALS['userdata'][1]);
            $mail .= "<b>Logged in user:</b> - $username (id: $userid) - $user_fillname<br />\n";
        }

        return $mail;
    }
}
