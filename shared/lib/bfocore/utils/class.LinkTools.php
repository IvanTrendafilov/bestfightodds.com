<?php

class LinkTools
{
    public static function slugString($a_sText)
    {
        // replace non letter or digits by -
        $a_sText = preg_replace('~[^\\pL\d]+~u', '-', $a_sText);

        // trim
        $a_sText = trim($a_sText, '-');

        // transliterate
        $a_sText = iconv('utf-8', 'us-ascii//TRANSLIT', $a_sText);

        // lowercase
        //$a_sText = strtolower($a_sText);
      
        // remove unwanted characters
        $a_sText = preg_replace('~[^-\w]+~', '', $a_sText);

        if (empty($a_sText)) {
            return 'n-a';
        }

        return $a_sText;
    }
}
