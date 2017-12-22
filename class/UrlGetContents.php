<?php

class UrlGetContents
{
    public static function getContents($url)
    {
        $urlParts = parse_url($url);
        $hostname = $urlParts['host'];
        $port = $urlParts['scheme'] == 'https' ? '443' : '80';
        $path = $urlParts['path'] . '?' . $urlParts['query'];
        $sslPrefix = $port == '443' ? 'ssl://' : '';
        $fp = @fsockopen($sslPrefix . $hostname, $port, $errno, $errstr);
        if ($fp == false) {
            return 'Error: ' . $errno . ': ' . $errstr . "\n";
        }
        $request = self::getRequest($path, $hostname);
        $response = self::getResponse($request, $fp);
        list($responseHead, $responseBody) = self::getResponseParts($response);
        return $responseBody;
    }

    private static function getRequest($path, $hostname)
    {
        $crln = "\r\n";
        $request =
            'GET ' . $path . ' HTTP/1.1' . $crln .
            'Host: ' . $hostname . $crln .
            'Connection: Close' . $crln . $crln;
        return $request;
    }

    private static function getResponse($request, $fp)
    {
        $response = '';
        fwrite($fp, $request);
        while (!feof($fp)) {
            $response .= fread($fp, 2048);
        }
        fclose($fp);
        return $response;
    }

    private static function getResponseParts($response)
    {
        $responseLines = explode("\n", $response);
        $responseHead = array();
        $responseBody = array();
        $bodyFlag = false;
        foreach ($responseLines as $line) {
            if (!trim($line)) {
                $bodyFlag = true;
                continue;
            }
            if ($bodyFlag) {
                $responseBody[] = $line;
            } else {
                $responseHead[] = $line;
            }
        }
        return array(implode("\n", $responseHead), implode("\n", $responseBody));
    }
}
