<?php

namespace cbednarski\FileUtils;

class FileUtils
{
    public static function fileIsHidden($file_path)
    {
        $parts = explode('/', self::softRealpath($file_path));
        foreach ($parts as $part) {
            if (strlen($part) > 0) {
                if ($part[0] === '.') {
                    return true;
                }
            }
        }

        return false;
    }

    public static function dirIsEmpty($path)
    {
        if (!is_dir($path)) {
            return false;
        }

        #  scandir will always return . and .. which we don't care about
        if (count(scandir($path)) > 2) {
            return false;
        }

        return true;
    }

    public static function mkdirIfNotExists($path, $mode = 0755, $recursive = true)
    {
        if (!file_exists($path)) {
            mkdir($path, $mode, $recursive);
        }
    }

    public static function mkdirs($folders, $path = '')
    {
        foreach ($folders as $folder) {
            self::mkdirIfNotExists($path . $folder);
        }
    }

    public static function existsAndIsReadable($path)
    {
        return file_exists($path) && is_readable($path);
    }

    public static function listFilesInDir($path, $show_hidden = false)
    {
        $files = array();

        if (!file_exists($path)) {
            // Even if the folder is missing we'll return an empty array so the API
            // is consistent and we can foreach without checking the type.
            return $files;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $file) {
            if (in_array($file->getBasename(), array('.', '..'))) {
                continue;
            } elseif ($file->isFile()) {
                if (!self::fileIsHidden($file->getPathname())) {
                    $files[] = $file->getPathname();
                }
            }
        }

        sort($files);

        return $files;
    }

    public static function concat($target, $append)
    {
        $new = false;
        if (!is_file($target)) {
            touch($target);
            $new = true;
        }

        $file = fopen($target, 'a');
        $add = fopen($append, 'r');

        if (!$new) {
            fwrite($file, PHP_EOL);
        }
        fwrite($file, fread($add, filesize($append)));

        fclose($file);
        fclose($add);
    }

    /**
     * Recursive delete
     *
     * Thanks to http://stackoverflow.com/a/4490706/317916
     *
     * @param  string $path Delete everything under this path
     * @return int    Number of files deleted
     */
    public static function recursiveDelete($path)
    {
        $counter = 0;

        if (!file_exists($path)) {
            return $counter;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $file) {
            if (in_array($file->getBasename(), array('.', '..'))) {
                continue;
            } elseif ($file->isDir()) {
                // Directories don't hold data so we won't count these
                rmdir($file->getPathname());
            } elseif ($file->isFile() || $file->isLink()) {
                if (unlink($file->getPathname())) {
                    $counter++;
                }
            }
        }

        rmdir($path);

        return $counter;
    }

    public static function pathDiff($outer, $inner, $suppress_leading_slash = false)
    {
        $diff = str_replace($outer, '', $inner);

        if ($suppress_leading_slash) {
            $diff = ltrim($diff, '/\\');
        }
        
        return $diff;
    }

    public static function filterExists($paths)
    {
        $temp = array_filter($paths, function($path) {
            return file_exists($path);
        });

        sort($temp);

        return $temp;
    }

    public static function removeExtension($filename, $extension, $default = 'html')
    {
        if (pathinfo($filename, PATHINFO_EXTENSION) === $extension) {
            $filename = substr($filename, 0, strlen($filename) - (strlen($extension) + 1));

            if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
                $filename = $filename . '.' . $default;
            }
        }

        return $filename;
    }

    /**
     * Check the filename against one or more regexps to see whether it matches
     *
     * @param  string       $filename
     * @param  string|array $matches
     * @return bool
     */
    public static function matchFilename($filename, $matches)
    {
        if (!is_array($matches)) {
            $matches = array($matches);
        }

        foreach ($matches as $regexp) {
            if ($regexp === null) {
                continue;
            }

            if (preg_match("#$regexp#", $filename)) {
                return true;
            }
        }

        return false;
    }

    public static function getFileModifyTimes ($path)
    {
        $files = FileUtils::listFilesInDir($path);
        $fileTimes = array();

        foreach ($files as $file) {
            $fileTimes[$file] = filemtime($file);
        }

        return $fileTimes;
    }

    /**
     * realpath() returns false if the file doesn't exist on the filesystem but
     * sometimes you just want to collapse ../ to get an absolute path.
     */
    public static function softRealpath($path)
    {
        $realpath = realpath($path);

        if ($realpath !== false) {
            return $realpath;
        }

        $path = preg_replace("#([^\"*/:<>?\\\\|]+)/(\\.\\.)/#u", "", $path);
        $path = preg_replace("#([^\"*/:<>?\\\\|]+)/(\\.)?/#u", "$1/", $path);

        return $path;
    }
}
