<?php
/**
 * Content structure backend script
 *
 * Handles XHR request from AJAXified content structure tree.
 *
 * @version $Revision: 608 $
 */

// NB! This scritp is vulnerable to XSFR attacks as well as rest of the Modera core,
// some day we should fix it.

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

require(dirname(__FILE__) . '/admin_header.php');
require_once(SITE_PATH . '/class/ContentStructure.php');
require_once(SITE_PATH . '/class/aliases_helpers.php');
require_once(SITE_PATH . '/class/cache_helpers.php');
require_once(SITE_PATH . '/class/JsonEncoder.php');
require_once(SITE_PATH . '/class/Versioning.php');
require_once(SITE_PATH . '/class/Arrays.php');
require_once(SITE_PATH . '/class/PageTags.php');
require_once(SITE_PATH . '/class/ContentActions.php');
require_once(SITE_PATH . '/class/ContentWorkflow.php');

// destroy $perm object created in admin_header.php
unset($perm);
$structure = new ContentStructure($database);
$versioning = new Versioning($database);
$pageTags = new PageTags($database);
$contentActions = new ContentActions($database, $pageTags, $versioning, $structure);

/**
 * Send JSON response
 *
 * @param mixed $type
 */
function send_response($type)
{
    $args = func_get_args();

    if (is_bool($type)) {
        // send_response(true)
        $args[1] = $type;
        $type = 'status';
    }

    switch ($type) {
        case 'status':
            // send_response('status', true)
            $response = array(
                'status' => $args[1],
            );
            break;

        case 'showblank':
            // send_response('showblank')
            $response = array(
                'status' => true,
                'action' => $type,
            );
            break;

        case 'shownode':
            // send_response('shownode', 'trash', 10)
            $response = array(
                'status' => true,
                'node_type' => $args[1],
                'node_id' => $args[2],
                'action' => $type,
            );

            break;

        case 'showerror':
            // send_response('showerror', 403)
            $response = array(
                'status' => false,
                'action' => $type,
                'error_code' => $args[1],
            );
            break;

        default:
            trigger_error('Unknown response type', E_USER_ERROR);
            exit();
    }

    echo JsonEncoder::encode($response);
    exit();
}

//
// Handle actions if file was requested by POST
//

if ('POST' == $_SERVER['REQUEST_METHOD']) {
    switch (@$_POST['action']) {
        case 'move-down':
            // node can be moved down if user has modify permission on it and
            // add/append permission on parent node
            $perm = new Rights($group, $user, "content", false);
            if (!$perm->Access(null, $_POST["node"], "m", null)
                || !$prem->Access(null, $structure->getParent($_POST["node"]), "a", null))
            {
                send_response('showerror', 403);
            } else {
                if ($structure->moveNodeDown($_POST['node'])) {
                    clearXSLPfiles();
                    send_response(true);
                } else {
                    send_response(false);
                }
            }

            break;

        case 'move-up':
            // node can be moved up if user has modify permission on it and
            // add/append permission on parent node
            $perm = new Rights($group, $user, "content", false);
            if (!$perm->Access(null, $_POST["node"], "m", null)
                || !$prem->Access(null, $structure->getParent($_POST["node"]), "a", null))
            {
                send_response('showerror', 403);
            } else {
                if ($structure->moveNodeUp($_POST['node'])) {
                    clearXSLPfiles();
                    send_response(true);
                } else {
                    send_response(false);
                }
            }

            break;

        case 'move-under':
            // one node can be moved under another if user has modify permission
            // on moving node and add/append permision on reference node
            $perm = new Rights($group, $user, "content", false);
            if (!$perm->Access(null, $_POST["node"], "m", null)
                || !$perm->Access(null, $_POST["parentNode"], "a", null))
            {
                send_response('showerror', 403);
            } else {
                if ($response['status'] = $structure->moveNodeUnder($_POST['node']
                    , $_POST['parentNode']))
                {
                    clearXSLPfiles();
                    send_response(true);
                } else {
                    send_response(false);
                }
            }

            break;

        case 'move-above':
            // one node can be moved above another if user has modify permission
            // on moving node and add/append permission on parent of another node
            $perm = new Rights($group, $user, "content", false);
            if (!$perm->Access(null, $_POST["node"], "m", null)
                || !$perm->Access(null, $structure->getParent($_POST["refNode"]), "a", null))
            {
                send_response('showerror', 403);
            } else {
                if ($response['status'] = $structure->moveNodeAbove($_POST['node']
                    , $_POST['refNode']))
                {
                    clearXSLPfiles();
                    send_response(true);
                } else {
                    send_response(false);
                }
            }

            break;

        case 'move-below':
            // one node can be moved below another if user has modify permission
            // on moving node and add/append permission on parent of another node
            $perm = new Rights($group, $user, "content", false);
            if (!$perm->Access(null, $_POST["node"], "m", null)
                || !$perm->Access(null, $structure->getParent($_POST["refNode"]), "a", null))
            {
                send_response('showerror', 403);
            } else {
                if ($response['status'] = $structure->moveNodeBelow($_POST['node']
                    , $_POST['refNode']))
                {
                    clearXSLPfiles();
                    send_response(true);
                } else {
                    send_response(false);
                }
            }

            break;

        case 'move-to-trash':
            $perm = new Rights($group, $user, $_POST["node_type"], false);
            if (!$perm->Access(null, $_POST["node"], "d", null)) {
                send_response('showerror', 403);
            }

            switch ($_POST['node_type']) {
                case 'template':
                    $trash_id = $contentActions->moveTemplateToTrash($_POST['node']);
                    break;

                case 'content':
                    // if user has publish permission or if node is pending
                    // creation it can be moved to trash
                    $pending = $database->fetch_first_value('SELECT `pending` FROM'
                        . ' `content` WHERE `content` = ?', $_POST['node']);

                    if ($perm->canPublish() || MODERA_PENDING_CREATION == $pending) {
                        $trash_id = $contentActions->moveContentToTrash($_POST['node']);
                        if ($trash_id) {
                            clearXSLPfiles();
                        }
                    } else {
                        // just mark node as pending for removal
                        $r = $database->query('UPDATE `content` SET `pending` = ?'
                            . ' WHERE `content` = ?', MODERA_PENDING_REMOVAL
                            , $_POST['node']);
                        if ($r) {
                            send_response('shownode', 'content', $_POST['node']);
                        } else {
                            send_response(false);
                        }
                    }

                    break;

                default:
                    $trash_id = false;
            }

            if ($trash_id) {
                send_response('shownode', 'trash', $trash_id);
            } else {
                send_response(false);
            }

            break;

        case 'remove':
            // atm only trash nodes can be removed complitely, other types of
            // nodes are moved to trash instead of wiping
            if ('trash' != $_POST['node_type']) {
                send_response('showerror', 403);
            }

            $perm = new Rights($group, $user, $_POST["node_type"], false);
            if ($perm->Access(null, $_POST["node"], "d", null)) {
                $removed = $contentActions->removeFromTrash($_POST['node']);
                if ($removed) {
                    send_response('showblank');
                } else {
                    send_response(false);
                }
            } else {
                send_response('showerror', 403);
            }

            break;

        case 'empty-trash':
            $perm = new Rights($group, $user, "trash", false);
            $contentWorkflow = new ContentWorkflow($database, $perm);
            $contentWorkflow->setContentActions($contentActions);

            if ($contentWorkflow->emptyTrash()) {
                send_response(true);
            } else {
                send_response('showerror', 403);
            }

            break;

        case 'restore-template':
            // template node can be restored only if user has 'm' permission on it
            $perm = new Rights($group, $user, 'trash', false);
            if ($perm->Access(null, $_POST['trash_node'], 'm', null)) {
                $template_id = $contentActions->restoreTemplate($_POST['trash_node']);
                if ($template_id) {
                    send_response('shownode', 'template', $template_id);
                } else {
                    send_response(false);
                }
            } else {
                send_response('showerror', 403);
            }

            break;

        case 'restore-content':
        case 'content-from-template':

            // discover parent node for checking 'a' permission on it
            if ('below' == $_POST['point'] || 'above' == $_POST['point']) {
                $parent_id = $structure->getParent($_POST["ref_node"]);
            } else if ('append' == $_POST['point']) {
                $parent_id = $_POST["ref_node"];
            } else {
                // unsupported point value
                send_response(false);
            }

            $content_id = false;
            $perm = new Rights($group, $user, "content", false);
            if ($perm->Access(null, $parent_id, "a", null)) {
                $is_pending = !$perm->canPublish();
                if ('restore-content' == $_POST['action']) {
                    $perm = new Rights($group, $user, 'trash', false);

                    // user can restore node only if he has 'm' permission on it
                    if ($perm->Access(null, $_POST['trash_node'], 'm', null)) {
                        $content_id = $contentActions->restoreContent($_POST['trash_node']
                            , $_POST['ref_node'], $_POST['point'], $is_pending);
                        if (!$content_id) {
                            send_response(false);
                        }
                    }
                } else if ('content-from-template' == $_POST['action']) {
                    $perm = new Rights($group, $user, 'template', false);

                    // user can create new node from template only if he has
                    // 'm' permission on template
                    if ($perm->Access(null, $_POST['template_node'], 'm', null)) {
                        $content_id = $contentActions->contentFromTemplate($_POST['template_node']
                            , $_POST['ref_node'], $_POST['point'], $user, $is_pending);
                        if (!$content_id) {
                            send_response(false);
                        }
                    }
                }
            }

            if ($content_id) {
                clearXSLPfiles();
                send_response('shownode', 'content', $content_id);
            } else {
                send_response('showerror', 403);
            }

            break;

        case 'template-from-content':
            // template can be created only if user has 'm' permission on source node
            $perm = new Rights($group, $user, 'content', false);
            if ($perm->Access(null, $_POST['node'], 'm', null)) {
                $template_id = $contentActions->templateFromContent($_POST['node']
                    , $user);
                if ($template_id) {
                    send_response('shownode', 'template', $template_id);
                } else {
                    send_response(false);
                }
            } else {
                send_response('showerror', 403);
            }

            break;

        default:
            send_response(false);
    }
}

//
// Generate menu data if file was requested by GET
//
$xsl = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output
    method="html"
    encoding="UTF-8"
    media-type="text/javascript"
    indent="no"
    omit-xml-declaration="yes"/>

<xsl:template match="/">
    <xsl:apply-templates select="/menu/item"/>
</xsl:template>

<xsl:template match="item">
    <![CDATA[{]]>
        <![CDATA[text:"]]><xsl:value-of select="translate(name,'&#34;','')"/><![CDATA[",]]>
        <![CDATA[id:"]]><xsl:value-of select="@content"/><![CDATA["]]>
        <xsl:if test="count(item) > 0">
            <![CDATA[,children:[]]>
                <xsl:apply-templates select="item"/>
            <![CDATA[]]]>
        </xsl:if>
    <![CDATA[}]]>
    <xsl:if test="following-sibling::item"><![CDATA[,]]></xsl:if>
</xsl:template>
</xsl:stylesheet>
EOT;

$xslt = Xslt::instance();
$xslt->set_xml($structure->getAsXml($language, true, true));
$xslt->set_xsl($xsl);
$structure_data = $xslt->process();

//
// construct templates and trash nodes JSON
//

foreach (array("templates", "trash") as $content_type) {
    ${$content_type . "_data"} = array();
    $data = &${$content_type . "_data"};

    $result = &$database->query("SELECT `content`, `title` FROM ?f"
        . " WHERE `language` = ?", "content_" . $content_type, $language);
    while ($row = $result->fetch_assoc()) {
        $title = htmlspecialchars(str_replace('"', '\\"', $row['title']));
        $content = $row['content'];
        array_push($data, "{text:\"$title\",id:\"$content\"}");
    }

    $data = implode(',', $data);
}

?>
{
    templates : [
        <?php echo $templates_data ?>
    ],
    content : [
        <?php echo $structure_data ?>
    ],
    trash : [
        <?php echo $trash_data ?>
    ]
}