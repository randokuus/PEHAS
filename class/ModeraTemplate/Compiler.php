<?php
/**
 * @version $Revision$
 */


/**
 * Template compiler class
 *
 * @author Stanislav Chichkan <stas@itworks.biz.ua>
 */

class ModeraTemplate_Compiler
{
    /**
     * error handling warning level,
     * TPL_WARN_CRITICAL by default.
     *
     * @var string error handling warning level
     * @access public
     */
  var $warn_level = TPL_WARN_CRITICAL;

  var $pair_tags = array(
      'TPL_SUB',
      '/TPL_SUB',
      'TPL_OBJECT',
      '/TPL_OBJECT'
  );

  /**
   * Constructor
   *
   * @return ModeraTemplateCompiler
   */
  function ModeraTemplate_Compiler(){}


  /**
   * Set warning level
   * Warning level indicates what and if warnings should be shown in final rendered page
   * - TPL_WARN_IGNORE - will show no warnings
   * - TPL_WARN_CRITICAL - will show only critical errors (could not create an object, object returned error, data is incorrect)
   * - TPL_WARN_ALL - will show all warnings including dataset present in template but no corresponding data was supplied
   * @param string warning level
   * @access public
   */
  function setWarnLevel($warn_level)
  {
      $this->warn_level = $warn_level;
  }


  /**
   * Replace template`s vars tags into php
   * executeable constructions
   * for example:
   * <_TPL:VARNAME> to < ? php echo $data["VARNAME"]; ? >
   *
   * @param string data for parsing
   * @return mixed php alias of template on success, FALSE on error
   */
  function compileParams($block, $param = 'TPL'
                      , $replace = '$data["%s"]', $sep = '"]["' )
  {
      $tags = array();
      preg_match_all ("/<" . $param . ":([^>]*)>/is",
                          $block, $tags, PREG_SET_ORDER);
      $tags_size = sizeof($tags);
      for ($i=0; $i < $tags_size; $i++) {
          $key = $tags[$i][1];
          if (strpos($key, '.')) {
              $key = str_replace('.', $sep, $key);
          }
          $block = str_replace($tags[$i][0]
             , '<?php echo '.sprintf($replace, $tags[$i][1]).'; ?>', $block);
      }
      return $block;
  }


  /**
   * Convert template text into php executeable script
   *
   * @param string data for parsing
   * @param bool - TRUE if using noncachable vars, FALSE if not
   * @return mixed php alias of template on success, FALSE on error
   */
  function compile($block , $noncachable)
  {
      // declare tag variable
      $tags = array();
      // process special global template tags and constants
      $block = $this->compileParams( $block, 'TPL_CONSTANT', '%s');
      $block = $this->compileParams( $block, '_TPL', '$this->getGlobals("%s")');
      while (stristr($block, '<TPL_INCLUDE') !== false) {
          preg_match_all ("/<(TPL_INCLUDE[^>]*):([^>]*)>/is",
                                  $block, $tags, PREG_SET_ORDER);
          $tags_size = sizeof($tags);
          for ($i=0; $i < $tags_size; $i++) {
              if (!$replace = $this->readTemplateFile($tags[$i][2])) {
                  $replace = $this->reportErrorMessage('Include file [' . $tags[$i][2] . '] not found!');
              }
              $block = str_replace($tags[$i][0], $replace, $block);
          }
          unset($i, $tags_size, $replace);
      }


      // process unused tags with language class
      preg_match_all("/<[\\_]?TPL:[^>]{0,20}TXT(JS)?_([^.>]*)>/is"
                        , $block, $tags, PREG_SET_ORDER);

      for ($i=0; $i < sizeof($tags); $i++) {
          $tag     = $tags[$i][0];
          $params  = str_replace('"', '\"', strtolower($tags[$i][2]));

          if ($tags[$i][1] == 'JS') {
                $replace = '<?php echo JsonEncoder::encode($this->getTranslate("' . $params . '")); ?>';
          } else {
                $replace = '<?php echo $this->getTranslate("' . $params . '"); ?>';
          }

          $block   = str_replace($tag, $replace, $block);
      }

      // gather all template tags
      preg_match_all ("/<([\/]?TPL[^>]*):([^>]*)>/Uis"
                        , $block, $tags, PREG_SET_ORDER);

      $tags = $this->_checkTags($tags);

      // parse template
      $prefix = '';
      $offset = 0;
      $message = '';
      for ($i=0; $i < sizeof($tags); $i++) {
          $tag = $tags[$i][0];
          $type = strtoupper($tags[$i][1]);
          $title = strtoupper($tags[$i][2]);

          if($tags[$i]['check'] == -1) {
              $block = $this->reportWarningMessage("No pair tag for [$title]")
                           . $block;
              continue;
          }
          if ($type == '/TPL_SUB') {
              // end of the block
              if ($prefix === $title) {
                  $prefix = '';
              } elseif (substr($prefix, (-1 - strlen($title))) === '.' . $title) {
                  $prefix = substr($prefix, 0, (-1 - strlen($title)));
              } else {
                  $block = $this->reportWarningMessage("No ending tag for [$prefix]")
                           . $block;
              }
          }
          if (stristr($type, 'TPL_OBJECT') === false) {
              $pos = strpos($block, $tag, $offset);

              if($type == 'TPL_SUB'){
                  if(!empty($prefix)){
                      $foreach_var = '$_foreach["' . $prefix . '"]["';
                      $foreach_key =  $prefix . '.' . $title;
                  } else {
                      $foreach_var = '$data["';
                      $foreach_key =  $title;
                  }
                  $foreach_var .= $title . '"]';
                  $newtag = '<?php if(isset('.$foreach_var.') && is_array('.
                              $foreach_var.')){ foreach('.$foreach_var.' as ' .
                              '$_foreach["' . $foreach_key . '"]){ ?>'."\n";

              } elseif ($type == '/TPL_SUB') {
                  $newtag = '<?php }} ?>'."\n";

              } elseif ($type == 'TPL' && !empty($prefix)) {
                  $newtag =  '<?php echo $_foreach["'.$prefix.'"]["'.$title.'"]; ?>';

              } elseif ($type == 'TPL_FUNC') {
                    $newtag =  $this->_prepareFunction($tags[$i][2], $prefix);

              } else {
                  $newtag  = "<" . $type . ":" ;
                  if(!empty($prefix))
                      $newtag .= $prefix . ".";
                  $newtag .= $title . ">";
              }
              $block = substr_replace($block, $newtag, $pos, strlen($tag));
              $offset = $pos+1;
          }
          if ($type == 'TPL_SUB') {
             $prefix .= (!empty($prefix)) ? '.' . $title : $title;
          }
      }

      //objects_holder
      $oh = array();

      // process objects in template
      preg_match_all ("/<([\/]?TPL_OBJECT[^>]*):([^>]*)>/i",
                      $block, $tags, PREG_SET_ORDER);
      $object_name = '';
      $object_params = array();
      $object_output = '';
      $object_result = array();
      $object_instance = '';
      $start_tag = '';
      $offset = 0;
      for ($i=0; $i < sizeof($tags); $i++) {
          $tag = $tags[$i][0];
          $type = strtoupper($tags[$i][1]);
          $title = $tags[$i][2];
          switch ($type) {
              case 'TPL_OBJECT':
                  // begin of the new object
                  $object_name = $title;
                  $object_params = array();
                  $object_output = '';
                  $object_instance = '';
                  $start_tag = $tag;
              break;
              case '/TPL_OBJECT':
                  // end of the object. instantiate an object
                  $object_result_var = 'ov_' . md5($object_name . '|' . $object_instance);
                  if (!isset($oh[$object_result_var])) {
                      $oh[$object_result_var] = $this->procObject($object_name,
                                                                  $object_params,
                                                                  $object_output,
                                                                  $object_result_var);
                  }

                  $print_result = '<?php echo $' . $object_result_var . '; ?>';

                  // replace in a template
                  $start_pos = strpos($block, $start_tag, $offset);
                  $end_pos = strpos($block, $tag, $offset) + strlen($tag);
                  $block = substr_replace($block,
                                            $print_result,
                                            $start_pos,
                                            $end_pos-$start_pos);
                  $offset = $start_pos;
                  // replace clone objects with info of this object
                  //NB! do we need insensitive, if yes, switch to preg_replace, but get slower results
                  $block = str_replace("<TPL_OBJECT_CLONE:$object_name>",
                                         $print_result,
                                         $block);
                  $object_name     = '';
                  $object_params   = array();
                  $object_output   = '';
                  $object_result   = array();
                  $object_instance = '';
                  $start_tag       = '';
              break;
              case 'TPL_OBJECT_PARAM':
                  // new object parameter
                  $object_params[] = $title;
                  $object_instance .= '/'.strtoupper($title);
              break;
              case 'TPL_OBJECT_OUTPUT':
                  // object output
                  $object_output = $title;
                  $object_instance .= '/'.strtoupper($title);
              break;
          }
      }

      if (!empty($oh)) {
          $objects = '<?php ';
          foreach($oh as $object) {
              $objects .= $object;
          }
          $objects .= '?>';
          $block = $objects . $block;
          unset($oh, $objects, $object);
      }


      // process function tags
      preg_match_all("/<TPL_FUNC:([a-zA-Z0-9\_]{1,}) ([^>]*)>/is"
                        , $block, $tags, PREG_SET_ORDER);
      for ($i=0; $i < count($tags); $i++) {

          $tag     = $tags[$i][0];
          $fname   = $tags[$i][1];
          $fparam  = $this->_getFunctionParams($tags[$i][2]);

          $block   = str_replace($tag
                 , '<?php echo $this->_functionCall("' . $fname . '", ' . $fparam . '); ?>', $block);
      }


      // process special global template tags
      $block = $this->compileParams($block, '_TPL','$this->getGlobals("%s")', '(', ')' );
      $block = $this->compileParams($block);
      // if use non cachable vars in template
      if ($noncachable) {
          $block = $this->compileParams($block, 'TPL_NOCACHE', '\'<?php echo $data["%s"]; ?>\'');
      } else {
          $block = $this->compileParams($block, 'TPL_NOCACHE','$data["%s"]');
      }
      return $block;
  }

  /**
   * Check list tags on exists pair tags
   *
   * @param array $tags
   * @return array
   */
  function _checkTags($tags)
  {
      $level_check = 3;
      if (!count($tags)) {
          return $tags;
      }

      $stack = array();
      $count_stack = 0;
      foreach ($tags as $i => $tag){

          //tag not pair
          if (!in_array($tags[$i][1], $this->pair_tags)) {
              $tags[$i]['check'] = 1;
              continue;
          }

          // ending tag
          if (substr($tag[1], 0, 1) == '/' ) {
              if (!$count_stack) {
                   $tags[$i]['check'] = -1;
                   continue;
              }
              //need ending tag
              //find ending tag at level $level_check
              $l = 0;
              $found = false;
              while ($l < $level_check && ($count_stack - $l) > 0) {
                    $tag_check = $tags[$stack[$count_stack - 1 - $l]];
                  if ('/' . $tag_check[1] == $tag[1] && $tag_check[2] == $tag[2]) {
                      $found = true;
                      break;
                  }
                  $l++;
              }
              if ($found) {
                  $tags[$i]['check'] = 1;

                  $i_found = $count_stack - 1 - $l;
                  $tags[$stack[$i_found]]['check'] = 1;
                  for ($j=($count_stack-1);$j>$i_found;$j--) {
                         $tags[$stack[$j]]['check'] = -1;
                  }
                  $stack = array_slice($stack, 0, $i_found);
                  //unset($stack[$i_found]);

                  $count_stack = count($stack);
              } else {
                  $tags[$i]['check'] = -1;
              }

          // start tag
          } else {
                $stack[] = $i;
                $count_stack++;
                continue;
          }
      }
      foreach ($stack as $i) {
            $tags[$i]['check']= -1;
      }

      return $tags;
  }

  /**
   * prepare template`s function tags to converting in
   * executeable constructions
   *
   * @param string title of tag
   * @param string prefix of tag
   * @return string tempompary version of tag
   * @access protected
   */
  function _prepareFunction($title, $prefix)
  {
      if (!empty($prefix)
         && preg_match_all('/ ([a-zA-Z0-9\_]{1,})="([^"]{1,})"/U', $title, $found)) {
           $prepared = '<TPL_FUNC:' . substr($title, 0, strpos($title, ' '));
           foreach ($found[2] as $key=>$val) {
               if (strpos($val, 'TPL:') === 0) {
                   $val = '{{{$_foreach[\'' . $prefix . '\'][\'' . substr($val, 4) . '\']}}}';
               }
               $prepared .= ' ' . $found[1][$key] . '="' . $val . '"';
           }
           $prepared .= '>';
      } else {
          $prepared = '<TPL_FUNC:' . $title . '>';
      }
      return $prepared;
  }



  /**
   * proccess template function-tag`s title and
   * convert it to php array of params
   *
   * @param string title of tag
   * @return string php executable array
   * @access protected
   */
  function _getFunctionParams($title)
  {
      $prepared = array();
      if (preg_match_all('/([a-zA-Z0-9\_]{1,})="([^"]{1,})"/U', $title, $found)) {
           foreach ($found[2] as $key=>$val) {
               if (strpos($val, '{{{') === 0 && substr($val, -3) == '}}}') {
                   $val = substr(substr($val, 0,-3), 3 );
               } elseif (strpos($val, 'TPL:') === 0) {
                   $new_key   = substr($val, 4);
                   $key_array = explode('.', $new_key);
                   $val = '$data';
                   foreach($key_array as $subkey) {
                       $val .= '["' . $subkey . '"]';
                   }
               } elseif (strpos($val, 'TPL_CONSTANT:') === 0) {
                   $const   = substr($val, 13);
                   $val = '((defined("'.$const.'"))?'.$const.':null)';
               } else {
                   $val = '"' . $val . '"';
               }
               $prepared[] = '"' . $found[1][$key] . '"=>' . $val;
           }
      }
      return 'array(' . implode(', ', $prepared) . ')';
  }

  /**
   * compile object.
   *
   * @param string object_name name of the object to instantiate
   * @param array object_params array of methods with parameters to be passed to object
   * @param string object_output name of the method which generates the output
   * @access private
   * @return mixed Instantiates an object, executes its paramaters, returns output., FALSE on error
   */
  function procObject($object_name, $object_params, $object_output, $object_result_var)
  {
      $return = '';
      if (empty($object_output)) {
          // No <TPL_OBJECT_OUTPUT:xxx> parameter specified
          $return = "No <TPL_OBJECT_OUTPUT:xxx> parameter "
                      . " specified for object [$object_name]!";
          return ' ?> ' . $this->reportErrorMessage($return) . ' <?php ';
      }

      // check module aliases
      if (isset($GLOBALS["module_aliases"][$object_name])) {
          $object_name = $GLOBALS["module_aliases"][$object_name];
      }

      // check if class name is defined (preloaded)
      $class_name = 'ModeraModule_' . $object_name;
      if (!class_exists($class_name)) {
          $class_name = $object_name;
          if (!class_exists($class_name)) {
              $msg = "Unable to instantiate object [$object_name]!";
              return ' ?> ' . $this->reportErrorMessage($msg) . ' <?php ';
          }
      }

      $return = "\n" . '$obj = new ' . $class_name . ";\n";
      foreach ($object_params as $param) {
          // check if object method defined in template can be called
          $method = substr($param, 0, strpos($param, '('));
          if (is_callable(array($class_name, $method))) {
              $return .= '$obj->'.$param.';' . "\n";

          } else {
              // method does not exist. Set a warning
              $msg = "Method [$method] does not exist in class [$class_name]!";
              $return .= ' ?> ' . $this->reportErrorMessage($msg) . ' <?php ';
          }
      }

      $return .= '$' . $object_result_var . ' = $obj->'.$object_output.';' . "\n";
      return $return;
  }

  /**
   * Get error message. Checks against set warning level. Used for template
   * parsing-time error reporting.
   *
   * @param string error text
   * @param string which part generated a warning (optional)
   * @access public
   * @return string warning message
   */
  function reportErrorMessage($error, $part='')
  {
      $return = '';
      if ($this->warn_level != TPL_WARN_IGNORE) {
          $return = $this->formatErrorMessage($error, $part);
      }
      return $return;
  }


  /**
   * Format error message. Errors are reported inside HTML comments
   *
   * @param string warning text
   * @param string which part generated an error (optional)
   * @access public
   * @return string warning message
   */
  function formatErrorMessage($error, $part = '')
  {
      if (!empty($part))
          $part = " ($part)";
      return "<!-- TEMPLATE ERROR$part: $error -->\n";
  }


  /**
   * Get warning message. Checks against set warning level. Used for template
   * parsing-time error reporting.
   *
   * @param string error text
   * @param string which part generated a warning (optional)
   * @access public
   * @return string warning message
   */
  function reportWarningMessage($warning, $part = '')
  {
      $return = '';
      if ($this->warn_level != TPL_WARN_IGNORE &&
          $this->warn_level != TPL_WARN_CRITICAL)
          $return = $this->formatWarningMessage($warning, $part);
      return $return;
  }


  /**
   * Format warning message. Errors are reported inside HTML comments
   *
   * @param string warning text
   * @param string which part generated an error (optional)
   * @access public
   * @return string warning message
   */
  function formatWarningMessage($warning, $part = '')
  {
      if (!empty($part))
          $part = " ($part)";
      return "<!-- TEMPLATE WARNING$part: $warning -->\n";
  }

  /**
   * Read template file contents
   *
   * @param string full or relative path to template file
   * @access private
   * @return mixed template contents, FALSE on error
   */
  function readTemplateFile ($template_file='') {
      if (!($tf = @fopen($template_file, "r"))) {
          // error opening template file.
          return false;
      } else {
          // file opened successefuly. load contents
          $return = @fread($tf, filesize($template_file));
          fclose($tf);
          return $return;
      }
  }
}