<?php

namespace BFO\Parser\Utils;

use BFO\Utils\OddsTools;
use BFO\DataTypes\FightOdds;

/**
 * ParseTools
 *
 * Contains tools used when parsing matchups and betting lines
 *
 * Status: Prod version
 */
class ParseTools
{
    private static $correlation_table = [];
    private static $stored_content = [];

    /**
     * Retrieves the contents of a file and returns it as a string
     *
     * TODO: Secure function so that it is not possible to access folders like ../
     *
     * @param string $filename Filename
     * @return string Contents of the file
     */
    public static function retrievePageFromFile(string $filename): string
    {
        $file_contents = '';
        $file = fopen($filename, 'r');
        if ($file) {
            while (!feof($file)) {
                $file_contents .= fread($file, 8192);
            }
            fclose($file);
        }
        return $file_contents;
    }

    public static function retrievePageFromURL(string $url, array $extra_curl_options = null): string
    {
        // Get cURL resource
        $curl_obj = curl_init();

        //Add credentials if applicable
        $credentials = self::getCredentialsFromURL($url);
        if ($credentials != null) {
            curl_setopt($curl_obj, CURLOPT_USERPWD, $credentials);
            curl_setopt($curl_obj, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }

        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl_obj, array(
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => true
        ));

        //TODO: Hardcoded stuff. Ugly and should be moved out
        if (strpos($url, 'gamingsystem.') !== false) {
            curl_setopt($curl_obj, CURLOPT_SSLVERSION, 6); //TLS 1.2
        } elseif (substr($url, 0, strlen('https://api.pinnacle.com')) === 'https://api.pinnacle.com') {
            curl_setopt($curl_obj, CURLOPT_HTTPHEADER, ['Authorization: Basic ZmlnaHRvZGRzOmNuODI2Mg==']);
        } elseif (substr($url, 0, strlen('https://www.pinnacle.com/webapi')) === 'https://www.pinnacle.com/webapi') {
            curl_setopt($curl_obj, CURLOPT_REFERER, "https://www.pinnacle.com/en/odds/match/mixed-martial-arts/ufc/ufc");
        }

        //Set custom curl options if specified
        if (!empty($extra_curl_options)) {
            curl_setopt_array($curl_obj, $extra_curl_options);
        }

        // Send the request & save response to $resp
        $return_content = curl_exec($curl_obj);
        // Close request to clear up some resources
        curl_close($curl_obj);

        //Check if gzipped, if so, decode and return that
        if (substr($return_content, 0, 2) == "\x1F\x8B") {
            return gzinflate(substr($return_content, 10, -8));
        }

        return $return_content;
    }

    public static function retrieveMultiplePagesFromURLs(array $urls): bool
    {
        $curl_channels = [];
        $mh = curl_multi_init();

        //Create individual channels for each URL
        foreach ($urls as $url) {
            $curl_channels[$url] = curl_init();

            //Add credentials if applicable
            $credentials = self::getCredentialsFromURL($url);
            if ($credentials != null) {
                curl_setopt($curl_channels[$url], CURLOPT_USERPWD, $credentials);
                curl_setopt($curl_channels[$url], CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            }

            curl_setopt_array($curl_channels[$url], array(
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36',
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_FOLLOWLOCATION => true
            ));

            if (strpos($url, 'gamingsystem.') !== false) {
                curl_setopt($curl_channels[$url], CURLOPT_SSLVERSION, 6); //TLS 1.2
            } elseif (substr($url, 0, strlen('https://api.pinnacle.com')) === 'https://api.pinnacle.com') {
                curl_setopt($curl_channels[$url], CURLOPT_HTTPHEADER, ['Authorization: Basic ZmlnaHRvZGRzOmNuODI2Mg==']);
            } elseif (substr($url, 0, strlen('https://www.pinnacle.com/webapi')) === 'https://www.pinnacle.com/webapi') {
                curl_setopt($curl_channels[$url], CURLOPT_REFERER, "https://www.pinnacle.com/en/odds/match/mixed-martial-arts/ufc/ufc");
            }

            curl_multi_add_handle($mh, $curl_channels[$url]);
        }

        $running = null;
        do {
            $resp = curl_multi_exec($mh, $running);
        } while ($resp == CURLM_CALL_MULTI_PERFORM);

        while ($running && $resp == CURLM_OK) {
            curl_multi_select($mh);
            do {
                $resp = curl_multi_exec($mh, $running);
            } while ($resp == CURLM_CALL_MULTI_PERFORM);
        }

        //Fetch data into storage when all is done
        foreach ($curl_channels as $sChannelKey => $rChannelVal) {
            $sContent = curl_multi_getcontent($rChannelVal);
            if (substr($sContent, 0, 2) == "\x1F\x8B") {
                self::$stored_content[$sChannelKey] = gzinflate(substr($sContent, 10, -8));
            } else {
                self::$stored_content[$sChannelKey] = $sContent;
            }
        }

        //Close channels
        foreach ($curl_channels as $rChannel) {
            curl_multi_remove_handle($mh, $rChannel);
        }
        curl_multi_close($mh);
        return true;
    }

    private static function getCredentialsFromURL($url)
    {
        $matches = array();
        preg_match('/:\/\/([^:]+:[^@]+)@/', $url, $matches);
        if (isset($matches[1])) {
            return $matches[1];
        }
        return null;
    }

    public static function formatName($a_sName)
    {
        $sNewName = str_replace('&quot;', '"', $a_sName);
        $sNewName = str_replace('?', '', $a_sName);

        //Replaces all words surrounded by [, ( or " (e.g. nicknames):
        $sNewName = preg_replace('/[\\[("\'“Â][a-zA-Z0-9\\/\\?\'\\.\\,\\s-]*[\\])"\'”Â]/', ' ', $sNewName);

        //TODO: Minor Bookmaker/BetDSI custom fix - to be removed or revamped
        $sNewName = str_ireplace('(3 RD', '', $sNewName);
        $sNewName = str_ireplace('(3RND', '', $sNewName);
        $sNewName = str_ireplace('S)', '', $sNewName);

        //Trims multiple spaces to single space:
        $sNewName = preg_replace('/\h{2,}/', ' ', $sNewName);

        //Fixes various foreign characters:
        $sNewName = self::stripForeignChars($sNewName);


        //Capitalize name and remove any trailing chars:
        $sNewName = trim(strtoupper($sNewName), ' -\t');

        return $sNewName;
    }

    /**
     * Retrieves the last name in a name
     *
     * @param String $a_sName Full name
     * @param boolean $a_bOnlyLast Indicates if only last part of the full name should be retrieved. If false then all names after first is retrieved. Eg. true = Santos (Junior Dos Santos), false = Dos Santos (Junior Dos Santos)
     * @return String Last name
     */
    public static function getLastnameFromName($a_sName, $a_bOnlyLast = true)
    {
        if ($a_bOnlyLast == true) {
            //Gets only last part (e.g. Santos from Junior Dos Santos)
            $aParts = explode(' ', $a_sName);
            return $aParts[count($aParts) - 1];
        } else {
            //Get all last names (e.g Dos Santos from Junior Dos Santos)
            $aParts = explode(' ', $a_sName, 2);
            if (sizeof($aParts) == 1) {
                return $aParts[0];
            }
            return $aParts[1];
        }
    }

    public static function getInitialsFromName($a_sName)
    {
        $aInitials = array();
        foreach (explode(" ", $a_sName) as $sName) {
            $aInitials[] = strtoupper(substr($sName, 0, 1));
        }
        return $aInitials;
    }

    /**
     * Checks if moneyline odds is in the right format
     *
     * @deprecated Use OddsTools::checkCorrectOdds() instead
     */
    public static function checkCorrectOdds($a_sOdds)
    {
        return OddsTools::checkCorrectOdds($a_sOdds);
    }

    /**
     * Get arbitrage for odds
     *
     * @param string First money line
     * @param string Second money line
     * @return float Arbitrage value in decimal
     */
    public static function getArbitrage($a_sMoneyline1, $a_sMoneyline2)
    {
        $oTempOdds = new FightOdds(-1, -1, $a_sMoneyline1, $a_sMoneyline2, -1);

        $fArbitValue = (pow($oTempOdds->getFighterOddsAsDecimal(1, true), -1)
            + pow($oTempOdds->getFighterOddsAsDecimal(2, true), -1));

        return $fArbitValue;
    }

    public static function checkTeamName($a_sTeamName)
    {
        if (preg_match('/^[a-zA-Z0-9\'\\s-]*$/', $a_sTeamName)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if a betting string should be considered a prop or not
     *
     * Current criterias:
     * - Consists of 4 or more words
     * - Is either Yes or No
     *
     * @param String $a_sName Betting string to test
     * @return boolean If string is a prop or not
     */
    public static function isProp($a_sName)
    {
        //Check if either parameter is 4 or more words, then its probably a prop bet
        if (count(explode(" ", ParseTools::formatName($a_sName))) >= 4) {
            return true;
        }
        //Check if prop is either YES or NO, if that is the case then it is probably a prop
        if (strcasecmp($a_sName, 'yes') == 0 || strcasecmp($a_sName, 'no') == 0) {
            return true;
        }

        return false;
    }

    public static function matchBlock(string $contents, string $regexp)
    {
        $matches = null;
        preg_match_all($regexp, $contents, $matches, PREG_SET_ORDER);
        return $matches;
    }

    /**
     * Stores a correlation for the lifetime of the execution. Key (string) => Matchup ID (int)
     *
     * Correlation can be used to link different parsed objects with eachother
     * e.g. matchups and props
     */
    public static function saveCorrelation(string $correlation_key, int $target_matchup_id): void
    {
        self::$correlation_table[$correlation_key] = $target_matchup_id;
    }

    /**
     * Retrieves a stored correlation Key (string) => Matchup ID (int)
     */
    public static function getCorrelation(string $correlation_key): ?int
    {
        if (isset(self::$correlation_table[$correlation_key])) {
            return self::$correlation_table[$correlation_key];
        }
        return null;
    }

    public static function getAllCorrelations(): array
    {
        return self::$correlation_table;
    }

    public static function getStoredContentForURL(string $url): ?string
    {
        if (isset(self::$stored_content[$url])) {
            return self::$stored_content[$url];
        }
        return null;
    }

    /**
     * Replaces all foreign characters with their normal counterpart
     */
    public static function stripForeignChars($text)
    {
        $a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ ';
        $b = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr ';
        $text = utf8_decode($text);
        $text = strtr($text, utf8_decode($a), $b);
        return utf8_encode($text);
    }
}
