<?php

namespace BFO\Caching;

class CacheControl
{
    public static function cleanGraphCache()
    {
        if (is_dir(IMAGE_CACHE_DIR)) {
            $directory = opendir(IMAGE_CACHE_DIR);
            if ($directory == false) {
                return false;
            }

            while ($filename = readdir($directory)) {
                if ($filename != "." && $filename != ".." && $filename != '.gitignore') {
                    if (!is_dir(IMAGE_CACHE_DIR . "/" . $filename)) {
                        unlink(IMAGE_CACHE_DIR . '/' . $filename);
                    }
                }
            }
            closedir($directory);

            return true;
        }
    }

    public static function isCached($filename)
    {
        if (!CACHE_IMAGE_CACHE_ENABLED) {
            return false;
        }

        clearstatcache();
        return file_exists(IMAGE_CACHE_DIR . '/' . $filename . '.png');
    }

    public static function deleteCachedImage($filename)
    {
        return unlink(IMAGE_CACHE_DIR . '' . $filename . '.png');
    }

    public static function getCachedImage($filename)
    {
        return imagecreatefrompng(IMAGE_CACHE_DIR . '' . $filename . '.png');
    }

    public static function cacheImage($image, $filename)
    {
        return imagepng($image, IMAGE_CACHE_DIR . '' . $filename . '.png');
    }

    public static function cleanPageCache()
    {
        if (is_dir(CACHE_PAGE_DIR)) {
            $directory = opendir(CACHE_PAGE_DIR);
            if ($directory == false) {
                return false;
            }

            while ($filename = readdir($directory)) {
                if ($filename != "." && $filename != "..") {
                    if (!is_dir(CACHE_PAGE_DIR . DIRECTORY_SEPARATOR . $filename)) {
                        unlink(CACHE_PAGE_DIR . DIRECTORY_SEPARATOR . $filename);
                    }
                }
            }
            closedir($directory);

            return true;
        }
    }

    public static function cleanPageCacheWC($filename_pattern)
    {
        foreach (glob(CACHE_PAGE_DIR . DIRECTORY_SEPARATOR . $filename_pattern . ".php") as $filename) {
            unlink($filename);
        }
    }

    public static function isPageCached($page_filename)
    {
        if (!CACHE_PAGE_CACHE_ENABLED) {
            return false;
        }

        clearstatcache();
        return file_exists(CACHE_PAGE_DIR . DIRECTORY_SEPARATOR . $page_filename . '.php');
    }

    public static function getCachedPage($page_filename)
    {
        return file_get_contents(CACHE_PAGE_DIR . DIRECTORY_SEPARATOR . $page_filename . '.php');
    }

    public static function cachePage($content, $page_filename)
    {
        return self::file_put_contents_atomic(CACHE_PAGE_DIR, $page_filename, $content);
    }

    public static function file_put_contents_atomic($cache_dir, $filename, $content)
    {
        $temp = tempnam($cache_dir, 'temp');
        if (!($f = @fopen($temp, 'wb'))) {
            $temp = $cache_dir . DIRECTORY_SEPARATOR . uniqid('temp');
            if (!($f = @fopen($temp, 'wb'))) {
                trigger_error("file_put_contents_atomic() : error writing temporary file '$temp'", E_USER_WARNING);
                return false;
            }
        }
        fwrite($f, $content);
        fclose($f);

        if (!@rename($temp, CACHE_PAGE_DIR . DIRECTORY_SEPARATOR . $filename)) {
            @unlink(CACHE_PAGE_DIR . DIRECTORY_SEPARATOR . $filename);
            @rename($temp, CACHE_PAGE_DIR . DIRECTORY_SEPARATOR . $filename);
        }

        @chmod(CACHE_PAGE_DIR . DIRECTORY_SEPARATOR . $filename, 0777);
        return true;
    }
}
