<?php

/**
 * Generates static pages from dynamic pages. Used as a caching method.
 */
class PageGenerator
{
    public static function generatePage($a_sSourceFile, $a_sTargetFile)
    {
        if ($a_sSourceFile == null || $a_sTargetFile == null || !file_exists($a_sSourceFile))
        {
            return null;
        }

        ob_start();

        include_once($a_sSourceFile);

        $sBuffer = ob_get_clean();
        if (strlen($sBuffer) > 200)
        {
            $rPage = fopen($a_sTargetFile, 'w');
            fwrite($rPage, $sBuffer);
            fclose($rPage);

            return true;
        }

        return false;
    }
}

?>
