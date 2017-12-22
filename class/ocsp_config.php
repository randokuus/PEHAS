<?php
// User certificate issuer certificate file location
$ocsp_info = Array();

//define('OCSP_SERVER_URL', 'http://ocsp.sk.ee:80');
define('OCSP_SERVER_URL', 'http://ocsp.sk.ee/_auth');
define('OCSP_CERT_PATH', SITE_PATH . '/id/certs/');
define('OCSP_SERVER_CERT_FILE', OCSP_CERT_PATH . 'authentication_ocsp_responder_2016.pem');

// EID-SK - CA for alternative ID cards until 13.01.2007
$ocsp_info["EID-SK"]["CA_CERT_FILE"] = SITE_PATH . "/id/certs/eid_sk.pem";
// OCSP server adress for this CA
$ocsp_info["EID-SK"]["OCSP_SERVER_URL"] = OCSP_SERVER_URL;
// OCSP responder certificate location for this CA
//$ocsp_info["EID-SK"]["OCSP_SERVER_CERT_FILE"] = SITE_PATH . "/id/certs/eid_sk_ocsp.pem";
$ocsp_info["EID-SK"]["OCSP_SERVER_CERT_FILE"] = OCSP_SERVER_CERT_FILE;

// EID-SK - CA for alternative ID cards since 13.01.2007
$ocsp_info["EID-SK 2007"]["CA_CERT_FILE"] = SITE_PATH . "/id/certs/eid_sk_2007.pem";
// OCSP server adress for this CA
$ocsp_info["EID-SK 2007"]["OCSP_SERVER_URL"] = OCSP_SERVER_URL;
// OCSP responder certificate location for this CA
//$ocsp_info["EID-SK 2007"]["OCSP_SERVER_CERT_FILE"] = SITE_PATH . "/id/certs/eid_sk_2007_ocsp.pem";
$ocsp_info["EID-SK 2007"]["OCSP_SERVER_CERT_FILE"] = OCSP_SERVER_CERT_FILE;

// EID-SK - CA for alternative ID cards since 13.08.2011
$ocsp_info["EID-SK 2011"]["CA_CERT_FILE"] = SITE_PATH . "/id/certs/eid_sk_2011.pem";
// OCSP server adress for this CA
$ocsp_info["EID-SK 2011"]["OCSP_SERVER_URL"] = OCSP_SERVER_URL;
// OCSP responder certificate location for this CA
//$ocsp_info["EID-SK 2011"]["OCSP_SERVER_CERT_FILE"] = SITE_PATH . "/id/certs/sk_ocsp_responder_2011.pem";
$ocsp_info["EID-SK 2011"]["OCSP_SERVER_CERT_FILE"] = OCSP_SERVER_CERT_FILE;


// ESTEID-SK - CA for Estonian national ID-card certificates issued until 13.01.2007
$ocsp_info["ESTEID-SK"]["CA_CERT_FILE"] = SITE_PATH . "/id/certs/esteid_sk.pem";
$ocsp_info["ESTEID-SK"]["OCSP_SERVER_URL"] = OCSP_SERVER_URL;
//$ocsp_info["ESTEID-SK"]["OCSP_SERVER_CERT_FILE"] = SITE_PATH . "/id/certs/esteid_sk_ocsp.pem";
$ocsp_info["ESTEID-SK"]["OCSP_SERVER_CERT_FILE"] = OCSP_SERVER_CERT_FILE;

// ESTEID-SK - CA for Estonian national ID-card certificates issued since 13.01.2007
$ocsp_info["ESTEID-SK 2007"]["CA_CERT_FILE"] = SITE_PATH . "/id/certs/esteid_sk_2007.pem";
$ocsp_info["ESTEID-SK 2007"]["OCSP_SERVER_URL"] = OCSP_SERVER_URL;
//$ocsp_info["ESTEID-SK 2007"]["OCSP_SERVER_CERT_FILE"] = SITE_PATH . "/id/certs/esteid_sk_2007_ocsp_2010.pem";
$ocsp_info["ESTEID-SK 2007"]["OCSP_SERVER_CERT_FILE"] = OCSP_SERVER_CERT_FILE;

// Checking status of certificates issued from "ESTEID-SK 2011" against live OCSP
// ESTEID-SK 2011 - CA for Estonian national ID-card certificates issued since 2011
$ocsp_info["ESTEID-SK 2011"]["CA_CERT_FILE"] = SITE_PATH . "/id/certs/esteid_sk_2011.pem";
$ocsp_info["ESTEID-SK 2011"]["OCSP_SERVER_URL"] = OCSP_SERVER_URL;
//$ocsp_info["ESTEID-SK 2011"]["OCSP_SERVER_CERT_FILE"] = SITE_PATH . "/id/certs/sk_ocsp_responder_2011.pem";
$ocsp_info["ESTEID-SK 2011"]["OCSP_SERVER_CERT_FILE"] = OCSP_SERVER_CERT_FILE;

// KLASS3-SK - CA for company certificates
$ocsp_info["KLASS3-SK"]["CA_CERT_FILE"] = SITE_PATH . "/id/certs/klass_3_sk.pem";
$ocsp_info["KLASS3-SK"]["OCSP_SERVER_URL"] = OCSP_SERVER_URL;
//$ocsp_info["KLASS3-SK"]["OCSP_SERVER_CERT_FILE"] = SITE_PATH . "/id/certs/klass_3_sk_ocsp.pem";
$ocsp_info["KLASS3-SK"]["OCSP_SERVER_CERT_FILE"] = OCSP_SERVER_CERT_FILE;

// ESTEID-SK 2015 - CA for Estonian national ID-card certificates issued since 2015
$ocsp_info["ESTEID-SK 2015"]["CA_CERT_FILE"] = SITE_PATH . "/id/certs/esteid_sk_2015.pem";
$ocsp_info["ESTEID-SK 2015"]["OCSP_SERVER_URL"] = OCSP_SERVER_URL;
$ocsp_info["ESTEID-SK 2015"]["OCSP_SERVER_CERT_FILE"] = OCSP_SERVER_CERT_FILE;

// Openssl binary location
$ocsp_info["OPEN_SSL_BIN"] = "/usr/bin/openssl";

// Temp folder to store certificates
$ocsp_info["OCSP_TEMP_DIR"] = "/var/tmp/";

// When true, then OCSP check will be made
$ocsp_info["OCSP_ENABLED"] = true;
