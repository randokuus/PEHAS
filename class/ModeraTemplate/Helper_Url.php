<?php
/**
 * @version $Revision$
 */


/**
 * Helper for retreiving url for specified content id or template
 *
 * @author Stanislav Chychkan <stas.chichkan@modera.net>
 */
class ModeraTemplate_Helper_Url
{

    function execute(&$tmpl, $params)
    {

        if (!isset($params['template']) && !isset($params['content'])) {
            $error = 'ModeraTemplate: wrong params '
                     . 'in ModeraTemplate_Helper_ContentUrl.';
            return $tmpl->reportWarningMessage($error);
        }

        // shortcut
        $db =& $GLOBALS['database'];

        if (!isset($params['content']) && isset($params['template'])) {
            $language = $tmpl->getLanguage();
            $content = ContentStructure::findContentByTemplate($db, $language, $params['template']);
        } else {
            $content = $params['content'];
        }

        $urls = new Urls($db, $GLOBALS['aliases']);

        return $urls->urlToPage($content, null);
    }
}