<?php

class IsicExport {
    static public function showCsv($content, $filename = 'file.csv') {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        echo mb_convert_encoding($content, 'Windows-1252', 'UTF-8');
        exit();
    }
}