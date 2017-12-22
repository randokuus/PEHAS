<?php
$dir = "/home/minukool/web/khs/cache/tryb/imported";
$mode = 0777;
/*
function chmod_R($path, $filemode) {
   if (!is_dir($path))
      return chmod($path, $filemode);
   $dh = opendir($path);
   while ($file = readdir($dh)) {
       if($file != '.' && $file != '..') {
           $fullpath = $path.'/'.$file;
           if(!is_dir($fullpath)) {
             if (!chmod($fullpath, $filemode))
                return true;
           } else {
             if (!chmod_R($fullpath, $filemode))
                return true;
           }
       }
   }

   closedir($dh);
   
   if(chmod($path, $filemode))
     return TRUE;
   else 
     return true;
} 
*/


$dh = opendir($dir);
while ($file = readdir($dh)) {
   $fullpath = $dir.'/'.$file;
 
   if($file != '.' && $file != '..' && !is_dir($fullpath)) {
       @chmod($fullpath, $mode);
   }
}


