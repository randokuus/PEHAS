<?php
/**
 * @version $Revision$
 */

class ModeraTemplate_Helper_tr {

    function execute(&$tmpl, $params) {

        if (!isset($params['domain']) || !isset($params['token'])) {
            $error = 'ModeraTemplate: wrong count of params '
                     . 'in ModeraTemplate_Helper_tr.';
            return $tmpl->reportWarningMessage($error);
        }

        if (!isset($params['format'])) {
            $params['format'] = null;
        }

        $language = $tmpl->getLanguage();
        $tanslator = &ModeraTranslator::instance($language, $params['domain']);

        return $tanslator->tr($params['token'], $params['format']);
    }
}