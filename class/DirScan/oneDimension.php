<?php
/**
 * Directory scanner - one dimensional structure
 * @version $Revision: 297 $
 */

/**
 * Create one dimensional directory structure
 *
 * Is a sub class for DirScan
 *
 * @author Priit Pold <priit.pold@modera.net>
 */
class DirScan_oneDimension extends DirScan
{
    /**
     * Array for storing directories and their indexes.
     * @var array
     */
    var $dirs_index = array();

    /**
     * Get directory structure in one dimensional array.
     *
     * @param string $root_path
     * @param string $path
     * @param integer $depth - depth of current directory from root path.
     * @return array - directory structure in array representation
     */
    function _scanning( $root_path , $path , $depth = 1 )
    {
        $structure = array();
        while ($root_path[strlen($root_path)-1] == DIRECTORY_SEPARATOR) {
        	$root_path = substr($root_path,0,-1);
        }
        $full_path = $root_path.(strlen($path)?DIRECTORY_SEPARATOR.$path:'');
        $dh = opendir( $full_path );
        // scan all files and dirs in selected dir.
        $i = 0;
        while ( $file = readdir($dh) ){
            //===================================================================
            // if max_depth is set and it greater then current depth, then continue
            if( $max_depth = $this->getOption('max_depth') AND $max_depth < $depth )
                return $structure;
            //===================================================================
            // if $file is directory and it exists in forbidden dir list, then skip this dir
            if(
                is_dir($full_path.DIRECTORY_SEPARATOR.$file)
                AND is_array($this->getOption('forbidden_dir'))
                AND in_array($file , $this->getOption('forbidden_dir') )
            )
                continue;
            $index = $this->getIndex($full_path);
            //===================================================================
            // if $file is '.' or '..', then skip it.
            if( $file == "." OR $file == ".." ){
                $structure[$index]['depth']=$depth;
                $structure[$index]['dir']=$path;
                $structure[$index]['full_path']=$full_path;
                continue;
            }
            //===================================================================
            // if $file is a dir ...
            if ( is_dir( $full_path.DIRECTORY_SEPARATOR.$file) )
            {
                if( $this->getOption('with_subfolders')===false )
                    continue;
                $arr = $this->_scanning( $full_path , $file , $depth + 1 );
                $structure = $structure+$arr;
                continue;
            }
            else // if $file is not a dir, then it's a file :-)
            {
                $file_extension = pathinfo($file);
                if (!isset($file_extension['extension'])) $file_extension['extension'] = '';

                $file_extension = strtolower($file_extension['extension']);
                //===================================================================
                // if file with current extension is forbidden then skip it.
                if( is_array($this->getOption('forbidden_ext')) and in_array($file_extension , $this->getOption('forbidden_ext') ) )
                    continue;
                //===================================================================
                // if file with current extension is not in allowed list then skip it.
                if( is_array($this->getOption('allowed_ext'))
                    AND !in_array( strtolower($file_extension) , $this->getOption('allowed_ext'))
                )
                    continue;
                //===================================================================
                // if option 'count_files' is set, then count files in current dir
                if( $this->getOption( 'count_files' ) ){
                    $structure[$index]['files_count'] = isset($structure[$index]['files_count'])?
                        ($structure[$index]['files_count']+1) : 1;
                }
                //===================================================================
                // if option 'with_files' is set, then save file into structure array.
                if( $this->getOption( 'with_files' ) ){
                    $structure[$index]['files'][$file] = $file;
                }
            }
        }
        return $structure;
    }

    /**
     * Get index for directory structure array
     *
     * @param string $full_path - directory path.
     * @return string - index of directory
     */
    function getIndex( $full_path )
    {
        if( isset( $this->dirs_index[ $full_path ] ) )
            return $this->dirs_index[ $full_path ];
        else{
            return $this->dirs_index[ $full_path ] = count( $this->dirs_index );
        }
    }
}

