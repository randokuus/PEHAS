<?php

/**
 * Redirect module
 * last modified 21.02.05 (siim)
 *
 * @package modera_net
 * @version 1.0
 * @access public
 */

class redirect {

/**
 * @var integer database connection identifier
 */
  var $dbc = false;
/**
 * @var boolean modera debug
 */
  var $debug = false;
/**
 * @var string active language
 */
  var $language = false;
/**
 * @var integer active user id
 */
  var $userid = false;

    /**
     * Class constructor
    */

  function redirect () {
    global $db;
    global $language;
    $this->language = $language;
    $this->debug = $GLOBALS["modera_debug"];
    if (!is_object($db)) { $db = new DB; $this->dbc = $db->connect(); }
    else { $this->dbc = $db->con; }

    $this->userid = $GLOBALS["user_data"][0];
  }

    /**
     * Jump to the desired location, based on the module redirect set data in the admin
     */

  function jump($link) {
    $sq = new sql;

    if ($link) {
        $sq->query($this->dbc, "SELECT redirectto FROM module_redirect WHERE language = '".addslashes($this->language) . "' AND linkid = '".addslashes($link)."' AND active = 1");
        if ($sq->numrows > 0) {

            $redirectto = $sq->column("0", "redirectto");

            if (eregi("http://", $redirectto) || eregi("https://", $redirectto) || eregi("ftp://", $redirectto)) {
                redirect($redirectto);
            }
            else {
                if (eregi("\/", substr(SITE_URL, 8))) {
                    $engine_url = substr(SITE_URL, 0, strpos(SITE_URL, "/", 8));
                }
                else {
                    $engine_url = SITE_URL;
                }

                $query = $_SERVER["QUERY_STRING"];
                $skip = array("structure","content","print","search_query","link");
                if (is_array($skip)) {
                    for ($t = 0; $t < sizeof($skip); $t++) {
                        $query = ereg_replace("(&|\?)?".$skip[$t]."=([^&])*", "", $query);
                    }
                }

                redirect($engine_url . $redirectto . "$query");
            }
            exit;
        }
        // link id not found, redirect to NOT FOUND error
        else {
            redirect("error.php?error=404");
        }
    }
    // no link ID, link to first page
    else {
        redirect("/");
    }

  }
}
