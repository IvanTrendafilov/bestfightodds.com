<?php

require_once('config/inc.config.php');

class CacheControl
{
    public static function cleanGraphCache()
    {
        if (is_dir(IMAGE_CACHE_DIR))
        {
            $rDir = opendir(IMAGE_CACHE_DIR);
            if ($rDir == false)
            {
                return false;
            }

            while ($sFile = readdir($rDir))
            {
                if ($sFile != "." && $sFile != ".." && $sFile != '.gitignore')
                {
                    if (!is_dir(IMAGE_CACHE_DIR . "/" . $sFile))
                    {
                        unlink(IMAGE_CACHE_DIR . '/' . $sFile);
                    }
                }
            }
            closedir($rDir);

            return true;
        }
    }

    public static function isCached($a_sName)
    {
        if (!CACHE_IMAGE_CACHE_ENABLED)
        {
            return false;
        }

        //TODO: Maybe fix this one to only clear for the file in question:
        clearstatcache();
        $bResult = file_exists(IMAGE_CACHE_DIR . '/' . $a_sName . '.png');
        return $bResult;
    }

    public static function deleteCachedImage($a_sName)
    {
        return unlink(IMAGE_CACHE_DIR . '' . $a_sName . '.png');
    }

    public static function getCachedImage($a_sName)
    {
        return imagecreatefrompng(IMAGE_CACHE_DIR . '' . $a_sName . '.png');
    }

    public static function cacheImage($rImage, $a_sName)
    {
        return imagepng($rImage, IMAGE_CACHE_DIR . '' . $a_sName . '.png');
    }

    public static function cleanPageCache()
    {
        if (is_dir(CACHE_PAGE_DIR))
        {
            $rDir = opendir(CACHE_PAGE_DIR);
            if ($rDir == false)
            {
                return false;
            }

            while ($sFile = readdir($rDir))
            {
                if ($sFile != "." && $sFile != "..")
                {
                    if (!is_dir(CACHE_PAGE_DIR . DIRECTORY_SEPARATOR . $sFile))
                    {
                        unlink(CACHE_PAGE_DIR . DIRECTORY_SEPARATOR . $sFile);
                    }
                }
            }
            closedir($rDir);

            return true;
        }
    }

    public static function cleanPageCacheWC($a_sName)
    {
        foreach (glob(CACHE_PAGE_DIR . DIRECTORY_SEPARATOR . $a_sName . ".php") as $sFilename) {
            unlink($sFilename);
        }
    }

    public static function isPageCached($a_sName)
    {
        if (!CACHE_PAGE_CACHE_ENABLED)
        {
            return false;
        }

        //TODO: Maybe fix this one to only clear for the file in question:
        clearstatcache();
        $bResult = file_exists(CACHE_PAGE_DIR . DIRECTORY_SEPARATOR . $a_sName . '.php');
        return $bResult;
    }

    public static function getCachedPage($a_sName)
    {
        return file_get_contents(CACHE_PAGE_DIR . DIRECTORY_SEPARATOR . $a_sName . '.php');
    }

    public static function cachePage($a_sContent, $a_sName)
    {
        return self::file_put_contents_atomic(CACHE_PAGE_DIR, $a_sName, $a_sContent);
    }


    public static function file_put_contents_atomic($a_sCacheDir, $filename, $content) 
    { 
        $temp = tempnam($a_sCacheDir, 'temp'); 
        if (!($f = @fopen($temp, 'wb'))) { 
            $temp = $a_sCacheDir . DIRECTORY_SEPARATOR . uniqid('temp'); 
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

?>