<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * PDF dictionary type objects
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfFiller {
    /**
     * @access private
     * @var string Head of PDF-file
     */
    var $pdf_head;
    /**
     * @access private
     * @var array map pdf-objects locations
     */
    var $pdf_map;
    /**
     * @access private
     * @var string PDF-file trailer
     */
    var $pdf_trailer;
    /**
     * @access private
     * @var string PDF-template content
     */
    var $pdf_content;

    /**
     * @access private
     * @var aray variables delimiters
     */
    var $delimiters;
    /**
     * @access private
     * @var array variables list
     */
    var $template_variables;
    /**
     * @access private
     * @var array Error list
     */
    var $_errorList = array(
        "parser_01" => "Template file does not exists",
        "parser_02" => "Template file does not readed",
        "parser_03" => "Template format incorrect",
        "parser_04" => "Template variables array is incorect"
        );
    /**
     * @access private
     * @var string Last error id
     */
    var $_lastError = false;

    /**
     * Constructor
     *
     * Set initial value
     *
     * @access public
     */
    function PdfFiller(){
      $this->pdf_content = "";
      $this->delimiters["start"] = "{";
      $this->delimiters["end"] = "}";
    }

    /**
     * Return  PDF map
     *
     * Parse PDF-file content and return object location map
     *
     * @access private
     * @return array PDF-map
     */
    function _getMap(){
        $startxref = "startxref";
        if (empty($this->pdf_content)){
            return false;
        }
        $pos_start = strrpos($this->pdf_content, $startxref);
        if ($pos_start === false){
            return false;
        }
        $tmp_end = trim(substr($this->pdf_content, $pos_start));
        preg_match('/[0-9]+/ms', $tmp_end, $res);
        if (!isset($res[0]) || empty($res[0])) {
            return false;
        }

        $map_position = intval($res[0]);
        if ($map_position<=0){
            return false;
        }
        $xref_pos = strpos($this->pdf_content, "xref", $map_position);
        if ($xref_pos === false){
            return false;
        }
        $tmp = trim(substr($this->pdf_content, $xref_pos+4));
        $traler_pos = strpos($tmp, "trailer");
        if ($traler_pos === false){
            return false;
        }
        $this->pdf_trailer = trim(substr($tmp, $traler_pos, (strrpos($tmp, $startxref)-$traler_pos)));
        $tmp = trim(substr($tmp, 0, $traler_pos));
        $link_array = explode("\n", $tmp);
        $map = array();
        $naxt_object_number = false;
        foreach($link_array as $l){
            $l = trim($l);
            $tmp_param = explode(" ", $l);
            if ($naxt_object_number === false && count($tmp_param) != 2){
                return false;
            }
            if (count($tmp_param) == 2){
                if (intval($tmp_param[0])>= 0){
                    $naxt_object_number = intval($tmp_param[0]);
                }
            }else{
                if (isset($tmp_param[2]) && isset($tmp_param[0]) /*&& $tmp_param[2] == "n"*/){
                    $map[$naxt_object_number] = array("pos"=>$tmp_param[0], "link"=>$tmp_param[1], "type"=>$tmp_param[2]);
                    $naxt_object_number++;
                }
            }
        }
        if (empty($map)){
            return false;
        }
        return $map;
    }

    /**
     * Inserts values of variables
     *
     * Inserts values of variables in pdf-stream content
     *
     * @access private
     * @param strinng $stream
     * @return string filled steram content
     */
    function _variableReplace($stream){
        $cnt = preg_match_all("/\s*\(.*[^\\\\]\)\s*Tj\s*/msU", $stream, $old_text_list);
        if ($cnt > 0){
            $new_text_list = $old_text_list;
            if (is_array($this->template_variables)){
                $keys = array_keys($this->template_variables);
                for ($pos=0; $pos < count($keys); $pos++){
                    $value = $this->template_variables[$keys[$pos]];
                    $search = $this->delimiters["start"].$keys[$pos].$this->delimiters["end"];
                    for($i=0; $i<count($old_text_list); $i++){
                        $new_text_list[$i] = str_replace($search, $value, $new_text_list[$i]);
                    }
                }
            }
            for($i=0; $i<count($old_text_list); $i++){
                $stream = str_replace($old_text_list[$i], $new_text_list[$i], $stream);
            }
        }
        return $stream;
    }

    /**
     * Parse stream object and find strem content
     *
     * Parse stream object and find strem content witch using for function _variableReplace.
     * Creates new stream-object with the filled data
     *
     * @access private
     * @param strinng obj
     * @return string filled steram object
     */
    function _parseStream($obj){
        $pos_st_start = strpos($obj, "obj");
        $pos_st_start +=3;
        if ($pos_st_start === false){
            return false;
        }
        $pos_st_end = strpos($obj, "stream", $pos_st_start);
        if ($pos_st_end === false){
            return false;
        }
        $dictionary = substr($obj, $pos_st_start, $pos_st_end-$pos_st_start);
        $pos = strpos($dictionary, "/Filter");
        if ($pos === false){
            $filter = "decoded";
        }else{
            $pos +=7;
            $pos = strpos($dictionary, "/", $pos);
            if ($pos){
                $tmp = substr($dictionary, $pos);
                $cnt = preg_match("/(\\/.*)[\\W]/msU", $tmp, $s);
                if($cnt == 0){
                    $filter = "decoded";
                }else{
                    if (!isset($s[1])){
                        return false;
                    }else{
                        $filter = trim($s[1]);
                    }
                }
            }
        }
        $pos_st_start = $pos_st_end + 6;
        $pos_st_end = strpos($obj, "endstream", $pos_st_start);
        if ($pos_st_end === false){
            return false;
        }
        $stream = ltrim(substr($obj, $pos_st_start, $pos_st_end-$pos_st_start));
        switch ($filter){
            case "decoded":
                            $stream = $this->_variableReplace($stream);
                            break;
            case "/FlateDecode":
                            if (!function_exists('gzuncompress')) {
                                trigger_error('Zlib required to decompress FlateDecoded streams', E_USER_ERROR);
                            }
                            $stream = gzuncompress($stream);
                            $stream = $this->_variableReplace($stream);
                            if (!function_exists('gzcompress')) {
                                trigger_error('Zlib required to decompress FlateDecoded streams', E_USER_ERROR);
                            }
                            $stream = gzcompress($stream). "\n";
                            break;
            default:
                            return $obj;
        }
        $stream_length = strlen($stream);
        $stream_start = substr($obj, 0, $pos_st_start);
        $len_pos = strpos($stream_start, "/Length");
        if ($len_pos === false ){
            return false;
        }
        $len_pos +=7;
        $tmp_start = substr($stream_start, 0, $len_pos) . " " . $stream_length;
        $tmp_end = ltrim(substr($stream_start, $len_pos));
        if (preg_match("/^(\d+)/", $tmp_end, $s)){
            if (isset($s[1])){
                $tmp_end = substr($tmp_end, strlen($s[1]));
            }
        }
        $stream_start = $tmp_start.$tmp_end;
        $stream_end = substr($obj, $pos_st_end);
        $obj = $stream_start . "\n" . $stream . $stream_end;
        return $obj;
    }

    /**
     * Parse PDF contrnt in accordance with pdf-map
     *
     * Parse PDF contrnt in accordance with pdf-map and created array of a objects
     * width filled variables
     *
     * @access private
     * @param array pdf_map
     * @return array pdf objects
     */
    function _parseObjects($pdf_map){
        $map_keys = array_keys($pdf_map);
        $i = 0;
        $list_objects = array();
        for($i=0; $i < count($map_keys); $i++){
            $st_pos = $pdf_map[$map_keys[$i]]["pos"];
            if ($pdf_map[$map_keys[$i]]['type'] == "n"){
                $pos = strpos($this->pdf_content, "\n", intval($st_pos));
                if ($pos !== false){
                    $obj = substr($this->pdf_content, intval($st_pos), ($pos-intval($st_pos)));
                    $pos1 = strpos($obj, "obj");
                    if ($pos1 === false){
                        return false;
                    }
                    $pos = strpos($this->pdf_content, "endobj", intval($st_pos));
                    if ($pos === false){
                        return false;
                    }
                    $obj = substr($this->pdf_content, intval($st_pos), $pos-intval($st_pos)+6);

                    if (preg_match("/stream(.*)endstream/msU", $obj, $s) != 0){
                        $obj = $this->_parseStream($obj);
                        if ($obj === false){
                            return false;
                        }
                    }
                }

            }else{
                $obj = "";
            }
            $list_objects[$map_keys[$i]] = array ("content"=>$obj, "link"=>$pdf_map[$i]["link"], "type"=>trim($pdf_map[$i]["type"]));

        }
        return $list_objects;
    }

    /**
     * Return PDF-head from PDF-file
     *
     * @access private
     * @return boolean
     */
    function _getPdfHead(){
        if (preg_match("/^(.*)\d+\s+0\s+obj/msU", $this->pdf_content, $s)){
            if (!isset($s[1])){
                return false;
            }
            $this->pdf_head =  $s[1];
            return true;
        }else{
            return false;
        }
    }

    /**
     * Fillen data in PDF-file ($pdf_file_name) on data in array ($template_variables)
     *
     * @access public
     * @param string pdf_file_name pdf file name
     * @param array template_variables
     * @return string filled pdf-content
     */
    function fillPdfFile($pdf_file_name, $template_variables){
        if (!is_array($template_variables)){
            $this->setError("parse_04");
            return false;
        }
        $this->template_variables = $template_variables;
        if (!file_exists($pdf_file_name)){
            $this->setError("parser_01");
            return false;
        }
        if (!$fh = fopen($pdf_file_name, "r")){
             $this->setError("parser_02");
            return false;
        }
        $this->pdf_content = fread($fh, filesize($pdf_file_name));
        fclose($fh);
        if (!$this->_getPdfHead()){
            $this->setError("parser_03");
            return false;
        }
        if (!$pdf_map = $this->_getMap()){
            $this->setError("parser_03");
            return false;
        }
        $list_objects = $this->_parseObjects($pdf_map);
        if ($list_objects === false){
            $this->setError("parser_03");
            return false;
        }else{
            $keys = array_keys($list_objects);
            $index = $keys[0];
            $index_count = 0;
            $first_index = $index;
            $tmp_index = $index;
            $result_map = "";
            $tmp_map = "";
            $result_content = $this->pdf_head;
            for ($i=0; $i < count($keys); $i++){
                $index = $keys[$i];
                if ($tmp_index == $index){
                    if ($list_objects[$index]['type'] == "n"){
                        $position = "" . strlen($result_content);
                        $result_content .= $list_objects[$index]["content"] . "\n";
                        $length = strlen($position);
                        while ($length < 10){
                            $position = "0" . $position;
                            $length++;
                        }
                    }else{
                        $position = "0000000000";
                    }
                    $tmp_map .= $position . " " .$list_objects[$index]['link'] . " " . $list_objects[$index]['type'] . "\n";
                    $index_count++;
                    $tmp_index++;
                }else{
                    $result_map .=$first_index . " " . $index_count . "\n" . $tmp_map;
                }
            }
            $result_map .=$first_index . " " . $index_count . "\n" . $tmp_map . "\n" . $this->pdf_trailer . "\n";
            $result_map = "xref\n" . $result_map ;
            $startxref = "startxref";
            $map_pos = strlen($result_content);
            $result_content .= $result_map . "startxref \n" . $map_pos. "\n%%EOF";
            return $result_content;
        }
    }

    /**
     * Set delimiter of variables
     *
     * @access public
     * @param string l_delimiter left delimiter
     * @param string r_delimiter right delimiter
     * @return boolean
     */
    function setDelimiters($l_delimiter, $r_delimiter){
        $this->delimiters["start"] = $l_delimiter;
        $this->delimiters["end"] = $r_delimiter;
        return true;
    }

     /**
     * Function set errors
     *
     * @access private
     * @param string id error id
     * @return boolean false if failed or error_id if succesfoll
     */
    function setError($id){
        if (array_key_exists($id, $this->_errorList)){
            $this->_lastError = $id;
        }else{
            return false;
        }
    }

    /**
     * Get ID of last error
     *
     * @access public
     * @return string Error code
     */
    function getLastErrorID(){
        return $this->_lastError;
    }

    /**
     * Get description of last error
     *
     * @access public
     * @return string Error description
     */
    function getLastErrorDesc(){
        return $this->_errorList[$this->_lastError];
    }

}
