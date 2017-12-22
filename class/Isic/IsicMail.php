<?php
include_once SITE_PATH . '/class/IsicDB/Users.php';
include_once SITE_PATH . '/class/mail/htmlMimeMail.php';

abstract class IsicMail {

    /**
     * Sends email about card set as distributed
     *
     * @param array $card_data array with card data
     * @return boolean true - sent, false - not sent
    */
    public static function sendCardDistributionNotification($card_data) {
        if ($card_data && $card_data["card_delivery_name"]) {
            $subject = self::getText("module_isic_card", "subject_distribute");
            $message = self::getText("module_isic_card", "message_distribute");
            $before = array('{CARD_TYPE}', '{DELIVERY_ADDRESS}');
            $after = array(
                $card_data['card_type_name'],
                htmlspecialchars($card_data["card_delivery_name"])
            );
            $message = str_replace($before, $after, $message);
            return self::send($card_data["person_email"], $subject, $message);
        }
        return false;
    }

    /**
     * Sends email about card deactivation
     *
     * @param array $cardRecord array with card data
     * @return boolean true - sent, false - not sent
    */
    public static function sendCardDeactivationNotification(array $cardRecord) {
        return;
        $cards = IsicDB::factory('Cards');
        $payments = IsicDB::factory('Payments');
        $subject = self::getText("module_isic_card", "subject_card_deactivated");
        $message = self::getText("module_isic_card", "message_card_deactivated");
        $before = array("{CARD_TYPE}", "{CARD_NO}", "{RETURN_DATE}", "{COLLATERIAL_AMOUNT}");
        $returnInDays = $cardRecord['type_should_return_in'];
        $maxReturnDate = strtotime("+$returnInDays days", strtotime(IsicDate::getAsDate($cardRecord['deactivation_time'])));
        $collateralPayment = $payments->getCollateralPaymentByCard($cardRecord);
        $after = array(
            $cardRecord['type_name'],
            $cardRecord['isic_number'],
            IsicDate::getTimeStampFormatted($maxReturnDate),
            $collateralPayment['payment_sum'] . " " . $collateralPayment['currency'],
        );
        $message = str_replace($before, $after, $message);
        return self::send($cardRecord["person_email"], $subject, $message, self::getGlobalAdminEmail());
    }

    /**
     * Sends email about deactivation for cards with chip
     *
     * @param array $cardRecord
     * @return bool|mixed
     */
    public static function sendCardWithChipDeactivationNotification(array $cardRecord) {
        return;
        $subject = self::getText('module_isic_card', 'subject_chip_card_deactivated');
        $message = self::getText('module_isic_card', 'message_chip_card_deactivated');
        $src = array('{CARD_TYPE}', '{CARD_NO}');
        $tar = array($cardRecord['type_name'], $cardRecord['isic_number']);
        $subject = str_replace($src, $tar, $subject);
        $message = str_replace($src, $tar, $message);
        /** @var IsicDB_Users $dbUser */
        $dbUser = IsicDB::factory('Users');
        $userRecord = $dbUser->getRecordByCode($cardRecord['person_number']);
        if ($userRecord) {
            return self::send($userRecord["email"], $subject, $message, self::getGlobalAdminEmail());
        }
        return false;
    }

    /**
     * Sends email about application being rejected for data correcting
     *
     * @param array $appl_data array with application data
     * @return boolean true - sent, false - not sent
    */
    public static function sendApplicationCorrectionRequiredRejectionNotification($appl_data) {
        return self::sendApplicationRejectionNotification($appl_data, 'correcting');
    }

    /**
     * Sends email about application being rejected for good
     *
     * @param array $appl_data array with application data
     * @return boolean true - sent, false - not sent
    */
    public static function sendApplicationFinalRejectionNotification($appl_data) {
        return self::sendApplicationRejectionNotification($appl_data, 'final');
    }

    private static function sendApplicationRejectionNotification($appl_data, $rejectType) {
        if ($appl_data) {
            $applications = IsicDB::factory('Applications');
            $subject = self::getText("module_isic_card", "subject_reject_" . $rejectType);
            $message = self::getText("module_isic_card", "message_reject_" . $rejectType);
            $before = array(
                "{REQUEST_DATE}",
                "{REASON_TITLE}",
                "{REASON_TEXT}"
            );
            $after = array(
                IsicDate::getDateFormatted($appl_data["user_request_date"]),
                $applications->getRejectReasonTitle($appl_data),
                stripslashes($appl_data["reject_reason_text"])
            );
            $message = str_replace($before, $after, $message);
            return self::send($appl_data["person_email"], $subject, $message);
        }
        return false;
    }

    public static function sendAdminConfirmationPendingNotification(array $userData, array $applData) {
        $applications = IsicDB::factory('Applications');
        $settings = IsicDB::factory('GlobalSettings');
        $subject = self::getText("module_isic_application", "subject_confirmation_pending");
        $message = self::getText("module_isic_application", "message_confirmation_pending");
        $tokens = array(
            '{URI}' => htmlspecialchars($applications->getURI($applData, IsicDB_Users::user_type_admin)),
            '{ADDRESS}' => htmlspecialchars($settings->getRecord('minukool_address')),
            '{PHONE}' => htmlspecialchars($settings->getRecord('minukool_phone'))
        );
        $message = strtr($message, $tokens);
        return self::send($userData['email'], $subject, $message);
    }

    public static function sendUserConfirmationPendingNotification(array $applData) {
        $applications = IsicDB::factory('Applications');
        $schools = IsicDB::factory('Schools');
        $regions = IsicDB::factory('Regions');

        $subject = self::getText("module_isic_application", "subject_confirmation_pending_user");
        $message = self::getText("module_isic_application", "message_confirmation_pending_user");

        $school = $schools->getRecord($applData['school_id']);
        $region = $regions->getRecord($school['region_id']);

        $tokens = array(
            '{PERSON_NAME_FIRST}' => htmlspecialchars($applData['person_name_first']),
            '{PERSON_NAME_LAST}' => htmlspecialchars($applData['person_name_last']),
            '{PERSON_NUMBER}' => htmlspecialchars($applData['person_number']),
            '{PERSON_PHONE}' => htmlspecialchars($applData['person_phone']),
            '{PERSON_EMAIL}' => htmlspecialchars($applData['person_email']),
            '{REGION_NAME}' => htmlspecialchars($region['name']),
            '{SCHOOL_NAME}' => htmlspecialchars($applData['school_name']),
            '{SCHOOL_EMAIL}' => htmlspecialchars($school['email_admin']),
            '{CARD_TYPE}' => htmlspecialchars($applData['type_name']),
            '{CONFIRM_URL}' => htmlspecialchars($applications->getURIUser($applData)),
        );
        $subject = str_replace(array_keys($tokens), array_values($tokens), $subject);
        $message = str_replace(array_keys($tokens), array_values($tokens), $message);
        return self::send($applData['person_email'], $subject, $message);
    }

    public static function sendCardTransferFailedNotification($orderName, array $isicNumbersOfCards) {
        $subject = self::getText("module_isic_card", "subject_transfer_failed");
        $message = self::getText("module_isic_card", "message_transfer_failed");
        $tokens = array(
            '{ORDER}' => $orderName,
            '{CARDS}' => implode(', ', $isicNumbersOfCards)
        );
        $message = strtr($message, $tokens);
        return self::send(self::getGlobalAdminEmail(), $subject, $message);
    }

    public static function sendCardsTransferDelayedNotification(array $transfersList) {
        $subject = self::getText("module_isic_card", "subject_transfer_delayed");
        $messageText = self::getText("module_isic_card", "message_transfer_delayed");
        $isic = IsicCommon::getInstance();
        $message = "";
        foreach ($transfersList as $transferData) {
            $isicNumbersOfCards = $isic->getCardsWithoutChipNumber($transferData['id']);
            $tokens = array(
                '{ORDER}' => $transferData['order_name'],
                '{CARDS}' => implode(', ', $isicNumbersOfCards)
            );
            $message = strtr($messageText, $tokens);
            self::send(self::getGlobalAdminEmail(), $subject, $message);
        }
    }

    public static function sendCardSyncUTFailedNotification($requestUrl, $rawResponse) {
        $settings = IsicDB::factory('GlobalSettings');
        $subject = self::getText("module_isic_card_data_sync", "subject_transfer_failed");
        $message = self::getText("module_isic_card_data_sync", "message_transfer_failed");
        $tokens = array(
            '{REQUEST_URL}' => $requestUrl,
            '{RESPONSE}' => htmlentities($rawResponse)
        );
        $message = strtr($message, $tokens);
        $recipient = $settings->getRecord('card_sync_ut_error_recipient');
        if (!$recipient) {
            $recipient = self::getGlobalAdminEmail();
        }
        return self::sendToList($recipient, $subject, $message);
    }

    public static function sendCompundCardsWithoutChipNotification(array $cardsList, $targetDate) {
        $subject = self::getText("module_isic_card", "subject_compound_cards_without_chip");
        $messageText = self::getText("module_isic_card", "message_compound_cards_without_chip");
        foreach ($cardsList as $cardData) {
            $cardMessage[] = $cardData['isic_number'];
        }
        $message = str_replace(
            array('{CARD_LIST}', '{TARGET_DATE}'),
            array(implode("\n", $cardMessage), $targetDate),
            $messageText
        );
        self::send(self::getGlobalAdminEmail(), $subject, $message);
    }

    public static function sendSwedImportFaultyCardsNotification(array $cardsList, $filename) {
        $subject = self::getText("module_isic_card", "subject_swed_import_faulty_cards");
        $messageText = self::getText("module_isic_card", "message_swed_import_faulty_cards");
        foreach ($cardsList as $cardData) {
            $cardMessage[] = print_r($cardData, true);
        }
        $message = str_replace(
            array('{CARD_LIST}', '{FILENAME}'),
            array(implode("\n", $cardMessage), $filename),
            $messageText
        );
        self::send(self::getGlobalAdminEmail(), $subject, $message);
    }

    public static function sendCardSyncCCDBFailedNotification($requestUrl, $rawResponse) {
        $settings = IsicDB::factory('GlobalSettings');
        $subject = self::getText("module_isic_card_data_sync", "subject_transfer_failed_ccdb");
        $message = self::getText("module_isic_card_data_sync", "message_transfer_failed_ccdb");
        $tokens = array(
            '{REQUEST_URL}' => $requestUrl,
            '{RESPONSE}' => htmlentities($rawResponse)
        );
        $message = strtr($message, $tokens);
        $recipient = $settings->getRecord('card_sync_ccdb_error_recipient');
        if (!$recipient) {
            $recipient = self::getGlobalAdminEmail();
        }
        return self::sendToList($recipient, $subject, $message);
    }

    public static function sendTagImportErrorNotification(array $errors, $filename) {
        $subject = self::getText("module_isic_card", "subject_tag_import_errors");
        $messageText = self::getText("module_isic_card", "message_tag_import_errors");
        $message = str_replace(
            array('{ERRORS}', '{FILENAME}'),
            array(implode(PHP_EOL, $errors), $filename),
            $messageText
        );
        self::send(self::getGlobalAdminEmail(), $subject, $message);
    }

    public static function sendOberthurImportErrorNotification(array $errors, $filename) {
        $subject = self::getText("module_isic_card", "subject_oberthur_import_errors");
        $messageText = self::getText("module_isic_card", "message_oberthur_import_errors");
        $message = str_replace(
            array('{ERRORS}', '{FILENAME}'),
            array(implode(PHP_EOL, $errors), $filename),
            $messageText
        );
        self::send(self::getGlobalAdminEmail(), $subject, $message);
    }

    // SHARED PRIVATE METHODS BELOW //

    private static function getText($module, $token) {
        $txt = new Text($GLOBALS['language'], $module);
        return stripslashes($txt->display($token));
    }

    private static function getSender() {
        $users = IsicDB::factory('Users');
        $currentAdminEmail = $users->isCurrentUserAdmin() ? $GLOBALS["user_data"][8] : '';
        $defaultEmail = $GLOBALS["site_admin"];
        if (validateEmail($currentAdminEmail)) {
            return $currentAdminEmail;
        } else {
            return self::getGlobalAdminEmail();
        }
    }

    private static function getGlobalAdminEmail() {
        $email = $GLOBALS["site_settings"]["admin_email"];
        return validateEmail($email) ? $email : false;
    }

    private static function send($recipient, $subject, $text, $sender = null) {
        if (!validateEmail($recipient) || !$subject || !$text) {
            return false;
        }
        if (strpos(SITE_URL, "dev.") !== false) {
            $subject .= " (dev redirect per $recipient)";
            if (defined('TESTERS_EMAILS')) {
                $recipientsList = explode(",", TESTERS_EMAILS);
            } else {
                return false;
            }
        } else {
            $recipientsList = array($recipient);
        }
        $sender = $sender ? $sender : self::getSender();
        if (!$sender) {
            return false;
        }
        $mail = new htmlMimeMail();
        $mail->setHtml(nl2br($text), returnPlainText($text));
        $mail->setFrom($sender);
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $mail->setSubject($subject);
        return $mail->send($recipientsList, 'mail');
    }

    private static function sendToList($recipientList, $subject, $text) {
        $list = explode(',', $recipientList);
        $result = true;
        foreach ($list as $recipient) {
            $result = $result && self::send($recipient, $subject, $text);
        }
        return $result;
    }
}
