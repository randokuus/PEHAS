<?php
require_once(SITE_PATH . "/class/ocsp_config.php");
require_once(SITE_PATH . "/class/IsicEncoding.php");

class id_card {
    var $ocsp_info = false;

    function id_card () {
        global $ocsp_info;
        $this->ocsp_info = $ocsp_info;
    }

    function getClientSDnCn () {
        if (!$_SERVER['SSL_CLIENT_S_DN_CN']) {
            if ($_SERVER['SSL_CLIENT_S_DN']) {
                $t_list = explode("/", $_SERVER['SSL_CLIENT_S_DN']);
                $t_str = str_replace("CN=", "", $t_list[4]);
                $_SERVER['SSL_CLIENT_S_DN_CN'] = $t_str;
            }
        }
        return IsicEncoding::certstr2Utf8($_SERVER['SSL_CLIENT_S_DN_CN']);
    }

    function log_id_action($data) {
        if ($fp_log = fopen(SITE_PATH . "/cache/id/log.txt", "a+")) {
            fwrite($fp_log, "==============================" . date("d.m.Y H:i:s") . "==============================\n");
            fwrite($fp_log, $data . "\n");
            fwrite($fp_log, "==============================" . date("d.m.Y H:i:s") . "==============================\n");
            fclose($fp_log);
        }
    }

    /*
    Params:
    $cert - user certificate in PEM format

    Output:
     0 - OCSP certificate status unknown
     1 - OCSP certificate status valid
     2 - OCSP internal error
     4 - Some error in script
    */

    function id_card_valid () {
        $user_good = 0;
        $issuer_dn = $_SERVER["SSL_CLIENT_I_DN_CN"];

        if ($this->ocsp_info["OCSP_ENABLED"] === false) {
            return false;
        }

        // Saving user certificate file to OCSP temp folder
        if ($tmp_f = fopen($tmp_f_name = tempnam($this->ocsp_info["OCSP_TEMP_DIR"], 'ocsp_check'), 'w')) {
            fwrite($tmp_f, $_SERVER["SSL_CLIENT_CERT"]);
            fclose($tmp_f);
        }

        if ($this->ocsp_info["OCSP_ENABLED"] &&
            isset($this->ocsp_info[$issuer_dn]["CA_CERT_FILE"]) &&
            isset($this->ocsp_info[$issuer_dn]["OCSP_SERVER_CERT_FILE"]) &&
            isset($this->ocsp_info[$issuer_dn]["OCSP_SERVER_URL"])) {

            // Making OCSP request using OpenSSL ocsp command
            $command = $this->ocsp_info["OPEN_SSL_BIN"] . ' ocsp' .
                ' -issuer ' . $this->ocsp_info[$issuer_dn]["CA_CERT_FILE"] .
                ' -cert '   . $tmp_f_name .
                ' -url '    . $this->ocsp_info[$issuer_dn]["OCSP_SERVER_URL"] .
                ' -VAfile ' . $this->ocsp_info[$issuer_dn]["OCSP_SERVER_CERT_FILE"];
            $this->log_id_action($command);
            $descriptorspec = array(
               0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
               1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
               2 => array("pipe", "w")   // stderr is a pipe that the child will write to
            );

            $process = proc_open($command, $descriptorspec, $pipes);

            if (is_resource($process)) {
                fclose($pipes[0]);

                // Getting errors from stderr
                $errorstr = "";
                $answer = '';
                while ($line = fgets($pipes[2])) {
                    $errorstr .= $line;
                }

                if ($errorstr != "Response verify OK\n") {
                    $user_good = 4;
                } else {
                    // Parsing OpenSSL command stdout
                    while ($line = fgets($pipes[1])) {
                        $answer .= $line;
                        if (strstr($line,'good')) {
                            $user_good = 1;
                        } else if (strstr($line,'internalerror (2)')) {
                            $user_good = 2;
                        }
                    }
                    fclose($pipes[1]);
                }
                proc_close($process);

                $this->log_id_action('Errorstr: ' . $errorstr);
                $this->log_id_action('SSL answer: ' . $answer);
            }
        }
        $this->log_id_action('User good: ' . $user_good);

        if (file_exists($tmp_f_name)) {
//            chmod($tmp_f_name, 0666);
            unlink($tmp_f_name);
        }
        return $user_good == 1;
    }
}