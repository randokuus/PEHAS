<?php
error_reporting(E_ALL);
class Xml2Csv {
    private $separator = ';';
    private $title = array();
    private $data = array();

    public function __construct($separator = ';') {
        $this->separator = $separator;
    }

    private function importXmlFeed($filename) {
        return simplexml_load_file($filename);
    }

    public function getAsCsv($filename) {
        $xml = $this->importXmlFeed($filename);
        $this->parseIntoTitleAndData($xml, array());
        $str = implode($this->separator, $this->title) . "\n";
        foreach ($this->data as $data) {
            $str .= implode($this->separator, $data) . "\n";
        }
        return $str;
    }

    public function convertAndSaveAsCsv($xmlFile, $csvFile) {
        return file_put_contents($csvFile, $this->getAsCsv($xmlFile));
    }

    private function parseIntoTitleAndData($xml, $prefix) {
        $firstTime = count($this->data) == 0;
        $data = $prefix;

        foreach ($xml->attributes() as $attrName => $attrValue) {
            $data[] = (string)$attrValue;
            if ($firstTime) {
                $this->title[] = $xml->getName() . ' [' . $attrName . ']';
            }
        }

        $noChildren = true;
        foreach ($xml as $key => $val) {
            if ($val->children()) {
                $this->parseIntoTitleAndData($val, $data);
                $noChildren = false;
            } else {
                if ($firstTime) {
                    $this->title[] = $key;
                }
                $data[] = (string)$val[0];
            }
        }
        if ($noChildren) {
            $this->data[] = $data;
        }
    }
}

//$x = new Xml2Csv(';');
//$x->getAsCsv(SITE_PATH . '/cron/Report_03.10.2011-07.10.2011.xml');