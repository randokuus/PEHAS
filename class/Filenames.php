<?php
/**
 * @version $Revision: 294 $
 */

/**
 * Helpers for processing filenames
 *
 * NB! This methods might work different way on Windows and *NIX platforms because
 * of DIRECTORY_SEPARATOR constant use. It is safe to process Windows styles
 * paths on Windows platform and *NIX styles paths on *NIX platform.
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 * @static
 */
class Filenames
{
    /**
     * Automatic filename renaming method
     *
     * Appends numeric value to filename, or increases it if $first_run parameter
     * was set to false
     *
     * @param string $path path to file
     * @param bool $first_run
     * @return string
     */
    function rename($path, $first_run = true)
    {
        if ($first_run) return Filenames::appendToName($path, '_1');

        extract(Filenames::pathinfo($path));
        $m = null;
        if (!preg_match('/^(.*)_(\d+)$/', $filename, $m)) {
            return Filenames::rename($path, true);
        } else {
            return Filenames::constructPath($m[1] . '_' . ++$m[2], $extension
                , $dirname);
        }
    }

    /**
     * Append suffix to filename
     *
     * @param string $path
     * @param string $suffix
     * @return string
     */
    function appendToName($path, $suffix)
    {
        extract(Filenames::pathinfo($path));
        return Filenames::constructPath($filename . $suffix, $extension, $dirname);
    }

    /**
     * Returns information about a file path
     *
     * This function is different from php`s pathinfo() in the way how it works
     * with directory names. If input path did not contain a leading dot
     * directory but empty directory, than in $pathinfo['directory'] in output
     * will be empty as well.
     * <br />
     * NB! If directory paths are not supported
     *
     * @param string $path
     * @return array
     * @link http://www.php.net/manual/en/function.pathinfo.php pathinfo()
     */
    function pathinfo($path)
    {
        if (version_compare(PHP_VERSION, '5.2.0', '>=')) {
            // use native php pathinfo() function
            $pathinfo = pathinfo($path);

        } else {
            $pathinfo = array(
                'dirname'   => dirname($path),
                'basename'  => basename($path),
            );

            $pos = strrpos($pathinfo['basename'], '.');
            if ($pos && $pos != strlen($pathinfo['basename']) - 1) {
                $pathinfo['filename'] = substr($pathinfo['basename'], 0, $pos);
                $pathinfo['extension'] = substr($pathinfo['basename'], $pos + 1);
            } else {
                $pathinfo['filename'] = $pathinfo['basename'];
                $pathinfo['extension'] = '';
            }
        }

        if (!strncmp('.', $pathinfo['dirname'], 1) && !strncmp('.' . DIRECTORY_SEPARATOR
            , $path, 2))
        {
            // remove dot dirname if was not initially in the input path
            $pathinfo['dirname'] = '';
        }

        return $pathinfo;
    }

    /**
     * Simple function for appending extension to filename
     *
     * If extension is empty than it will not be appended to name
     *
     * @param string $name
     * @param string $extension
     * @param string $dir
     * @return string
     */
    function constructPath($name, $extension, $dir = '')
    {
        $path = $dir;

        if (strlen($dir) && strncmp(DIRECTORY_SEPARATOR, substr($dir, -1), 1)) {
            $path .= DIRECTORY_SEPARATOR;
        }

        $path .= $name;

        if (strlen($extension)) {
            $path .= '.' . $extension;
        }

        return $path;
    }
}
