<?php

class NewScp {
    private $_scp_path = '/usr/bin/scp';
    private $_ssh_path = '/usr/bin/ssh';
    private $_sshpass_path = '/usr/bin/sshpass';
    private $_sftp_path = '/usr/bin/sftp';
    private $_known_hosts = '';
    private $_identity = '';
    private $_errors = array();
    private $_username = '';
    private $_hostname = '';
    private $_password = '';

    public function __construct($known_hosts = '', $identity = '', $hostname = '', $username = '', $password = '') {
        $this->setKnownHosts($known_hosts);
        $this->setIdentity($identity);
        $this->setHostname($hostname);
        $this->setUsername($username);
        $this->setPassword($password);
    }

    public function setScpPath($path = '') {
        if ($path) {
            $this->_scp_path = $path;
        }
    }

    public function setKnownHosts($path = '') {
        $this->_known_hosts = $path;
    }

    public function setIdentity($path = '') {
        $this->_identity = $path;
    }

    public function setUsername($un = '') {
        $this->_username = $un;
    }

    public function setHostname($hn = '') {
        $this->_hostname = $hn;
    }

    public function setPassword($hn = '') {
        $this->_password = $hn;
    }

    private function _setError($error) {
        $this->_errors[] = $error;
    }

    private function _clearErrors() {
        $this->_errors = array();
    }

    public function getErrors($as_text = true) {
        if ($as_text) {
            $str = "";
            for ($i = 0; $i < sizeof($this->_errors); $i++) {
                $str .= $this->_errors[$i] . "\n";
            }
            return $str;
        }
        return $this->_errors;
    }

    public function upload($src = '', $tar = '') {
        $this->_clearErrors();
/*
        if (!is_readable($this->_scp_path)) {
            $this->_setError("Could not access scp: " . $this->_scp_path);
            return false;
        }
*/
        if (!is_readable($this->_known_hosts)) {
            $this->_setError("Could not read known_hosts file: " . $this->_known_hosts);
            return false;
        }

        if (!is_readable($this->_identity)) {
            $this->_setError("Could not read identity file: " . $this->_identity);
            return false;
        }

        if (!is_readable($src)) {
            $this->_setError("Could not read source file: " . $src);
            return false;
        }

        if (!$tar) {
            $this->_setError("Target path is not set!");
            return false;
        }

        if (!$this->_username) {
            $this->_setError("Target username was not set!");
            return false;
        }

        if (!$this->_hostname) {
            $this->_setError("Target hostname was not set!");
            return false;
        }

        $passwordPrefix = $this->getPasswordPrefix();

        $target_path = $this->_username . '@' . $this->_hostname . ':' . $tar;

        // -B works in batch mode, no passwords or pass-phrases are asked
        $command = $passwordPrefix . $this->_scp_path . " -o UserKnownHostsFile=" . $this->_known_hosts .
            " -i " . $this->_identity . " " . $src . " " . $target_path;

        $ret_var = 0;
        $result = system($command, $ret_var);
        if ($ret_var) {
            $this->_setError("Transfer failed with following error-code: " . $ret_var . " (" . $result . ")");
            return false;
        }
        return true;
    }

    public function download($src = '', $tar = '') {
        $this->_clearErrors();

        if (!is_readable($this->_known_hosts)) {
            $this->_setError("Could not read known_hosts file: " . $this->_known_hosts);
            return false;
        }

        if (!is_readable($this->_identity)) {
            $this->_setError("Could not read identity file: " . $this->_identity);
            return false;
        }

        if (!is_writeable($tar)) {
            $this->_setError("Could not write target path: " . $tar);
            return false;
        }

        if (!$src) {
            $this->_setError("Source path is not set!");
            return false;
        }

        if (!$this->_username) {
            $this->_setError("Source username was not set!");
            return false;
        }

        if (!$this->_hostname) {
            $this->_setError("Source hostname was not set!");
            return false;
        }

        $passwordPrefix = $this->getPasswordPrefix();

        $source_path = $this->_username . '@' . $this->_hostname . ':' . $src;

        // -B works in batch mode, no passwords or pass-phrases are asked
        $command = $passwordPrefix . $this->_scp_path . " -p -o UserKnownHostsFile=" . $this->_known_hosts .
            " -i " . $this->_identity . " " . $source_path . " " . $tar;

        echo "$command\n";

        $ret_var = 0;
        $result = system($command, $ret_var);
        if ($ret_var) {
            $this->_setError("Transfer failed with following error-code: " . $ret_var . " (" . $result . ")");
            return false;
        }
        return true;
    }

    public function delete($path = '', $commandType = 'ssh') {
        $this->_clearErrors();

        if (!$path) {
            $this->_setError("Delete path is not set!");
            return false;
        }

        if (!$this->_username) {
            $this->_setError("Ssh username was not set!");
            return false;
        }

        if (!$this->_hostname) {
            $this->_setError("Ssh hostname was not set!");
            return false;
        }

        if ($commandType == 'ssh') {
            $command = $this->_ssh_path . " -o UserKnownHostsFile=" . $this->_known_hosts .
                " -i " . $this->_identity . " -l " . $this->_username . " " .
                $this->_hostname . " 'rm " . $path . "'";
        } else {
            $passwordPrefix = $this->getPasswordPrefix();

            $command = 'echo "rm ' . $path . '" | ' . $passwordPrefix . $this->_sftp_path . ' -oBatchMode=no -b - -oIdentityFile=' .
                $this->_identity . ' -oUserKnownHostsFile=' . $this->_known_hosts . ' ' .
                $this->_username . '@' . $this->_hostname;
        }

        echo "$command\n";

        $ret_var = 0;
        $result = exec($command, $output, $ret_var);
        var_dump($result);
        var_dump($output);
        if ($ret_var) {
            $this->_setError("Delete failed with following error-code: " . $ret_var . " (" . $result . ")");
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public function getPasswordPrefix()
    {
        if ($this->_password) {
            return $this->_sshpass_path . ' -p ' . $this->_password . ' ';
        }
        return '';
    }
}
