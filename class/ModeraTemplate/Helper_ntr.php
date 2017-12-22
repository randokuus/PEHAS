<?php
/**
 * @version $Revision$
 */



class ModeraTemplate_Helper_ntr {

    function execute(&$tmpl, $params) {

        if (!isset($params['domain']) || !isset($params['token']) || !isset($params['n'])) {
            $error = 'ModeraTemplate: wrong count of params '
                     . 'in ModeraTemplate_Helper_ntr.';
            return $tmpl->reportWarningMessage($error);
        }

        if (!isset($params['args'])) {
            $params['args'] = null;
        }

        if (!isset($params['format'])) {
            $params['format'] = null;
        }

        $language = $tmpl->getLanguage();
        $tanslator = &ModeraTranslator::instance($language, $params['domain']);

        return $tanslator->ntr($params['token'], $params['n'], $params['args'], $params['format']);
    }
}