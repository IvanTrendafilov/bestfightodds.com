<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/utils/class.OddsTools.php');

/**
 * ParseTools
 *
 * Contains tools used when parsing matchups and betting lines
 *
 * Status: Prod version
 */
class ParseTools
{

    private static $aCorrelationTable = array();
    private static $aFeedStorage = array();

    /**
     * Retrieves the contents of a file and returns it as a string
     *
     * TODO: Secure function so that it is not possible to access folders like ../
     *
     * @param string $a_sFile Filename
     * @return string Contents of the file
     */
    public static function retrievePageFromFile($a_sFile)
    {
        $sContents = '';
        $rFile = fopen($a_sFile, 'r');
        if ($rFile)
        {
            while (!feof($rFile))
            {
                $sContents .= fread($rFile, 8192);
            }
            fclose($rFile);
        }
        return $sContents;
    }

    public static function retrievePageFromURL($a_sURL, $a_sCurlOpts = null)
    {
        // Get cURL resource
        $rCurl = curl_init();

        //Get credentials
        $sCred = self::getCredentialsFromURL($a_sURL);
        if ($sCred != null)
        {
            curl_setopt($rCurl, CURLOPT_USERPWD, $sCred);
            curl_setopt($rCurl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }

        // Set some options - we are passing in a useragent too here        
        curl_setopt_array($rCurl, array(
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $a_sURL,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => true
        ));

        //TODO: Hardcoded stuff. Ugly and should be moved out
        if ($a_sURL == 'http://lines.bookmaker.eu')
        {
            curl_setopt($rCurl, CURLOPT_INTERFACE, '89.221.255.123');
        }
        else if ($a_sURL == 'http://lines.betdsi.eu/')
        {
            curl_setopt($rCurl, CURLOPT_INTERFACE, '89.221.253.24');
        }
        else if ($a_sURL == 'http://www.sportsinteraction.com/info/data/feeds/consume/?consumerName=bfodds&pwd=bfodds3145&feedID=5&formatID=4')
        {
            curl_setopt($rCurl, CURLOPT_SSL_CIPHER_LIST, 'ecdhe_ecdsa_aes_128_sha');
        }
        else if (substr($a_sURL, 0, strlen('http://www.thegreek.com')) === 'http://www.thegreek.com')
        {
            curl_setopt($rCurl, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25');
        }

        //Set custom curl options if specified
        if (!empty($a_sCurlOpts))
        {
            curl_setopt_array($rCurl, $a_sCurlOpts);
        }

        // Send the request & save response to $resp
        $sReturn = curl_exec($rCurl);
        // Close request to clear up some resources
        curl_close($rCurl);

        if ($sReturn == '')
        {
            return 'FAILED';
        }


        //Check if gzipped, if so, decode and return that
        if(substr($sReturn, 0, 2) == "\x1F\x8B")
        {
            return gzinflate(substr($sReturn,10,-8));
        }

        return $sReturn;
    }

    public static function retrieveMultiplePagesFromURLs($a_aURLs)
    {
        self::$aFeedStorage = array();
        $aChannels = array();
        $mh = curl_multi_init();

        //Create individual channels for each URL
        foreach ($a_aURLs as $sURL)
        {
            $aChannels[$sURL] = curl_init();

            //Get credentials
            $sCred = self::getCredentialsFromURL($sURL);
            if ($sCred != null)
            {
                curl_setopt($aChannels[$sURL], CURLOPT_USERPWD, $sCred);
                curl_setopt($aChannels[$sURL], CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            }

            curl_setopt_array($aChannels[$sURL], array(
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $sURL,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36',
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => true
            ));            

            if ($sURL == 'http://lines.bookmaker.eu')
            {
              curl_setopt($aChannels[$sURL], CURLOPT_INTERFACE, '89.221.255.123');
            }
            else if ($sURL == 'http://lines.betdsi.eu/')
            {
              curl_setopt($aChannels[$sURL], CURLOPT_INTERFACE, '89.221.253.24');
            }
            else if ($sURL == 'http://www.sportsinteraction.com/info/data/feeds/consume/?consumerName=bfodds&pwd=bfodds3145&feedID=5&formatID=4')
            {
              curl_setopt($aChannels[$sURL], CURLOPT_SSL_CIPHER_LIST, 'ecdhe_ecdsa_aes_128_sha');
            }
            else if (substr($sURL, 0, strlen('http://www.thegreek.com')) === 'http://www.thegreek.com')
            {
                curl_setopt($aChannels[$sURL], CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25');
            }


            curl_multi_add_handle($mh, $aChannels[$sURL]);
        }

        //Execute calls
        /*Old: $running = null;
        do {
            $resp = curl_multi_exec($mh,$running);
        } while ($running > 0);*/

        $running = null;
        do
        {
            $resp = curl_multi_exec($mh, $running);
        }
        while ($resp == CURLM_CALL_MULTI_PERFORM);

        while ($running && $resp == CURLM_OK)
        {
            curl_multi_select($mh);
            do
            {
                $resp = curl_multi_exec($mh, $running);
            }
            while ($resp == CURLM_CALL_MULTI_PERFORM);
        }


        //Fetch data into storage when all is done
        foreach ($aChannels as $sChannelKey => $rChannelVal)
        {
            $sContent = curl_multi_getcontent($rChannelVal);
            if(substr($sContent, 0, 2) == "\x1F\x8B")
            {
                self::$aFeedStorage[$sChannelKey] = gzinflate(substr($sContent,10,-8)); 
            }
            else
            {
                self::$aFeedStorage[$sChannelKey] = $sContent;   
            }
        }

        //Close channels
        foreach ($aChannels as $rChannel)
        {
            curl_multi_remove_handle($mh, $rChannel);        
        }
        curl_multi_close($mh);
        return true;
    }

    private static function getCredentialsFromURL($a_sURL)
    {
        $aMatches = array();
        preg_match('/:\/\/([^:]+:[^@]+)@/', $a_sURL, $aMatches);
        if (isset($aMatches[1]))
        {
            return $aMatches[1];    
        }
        return null;
    }

    /**
     * Converts Odds in EU format (decimal) to US (moneyline)
     *
     * Example: 1.59 converts to -170  or  3.4 converts to +240
     *
     * @deprecated Use OddsTools::convertDecimalToMoneyline instead
     * @param float $a_iOdds Odds in decimal format to convert
     * @return string Odds in moneyline format
     */
    public static function convertOddsEUToUS($a_fOdds)
    {
        return OddsTools::convertDecimalToMoneyline($a_fOdds);
    }

    /**
     * @deprecated Use OddsTools::convertMoneylineToDecimal instead
     */
    public static function convertOddsUSToEU($a_sMoneyLine, $a_bNoRounding = false)
    {
        return OddsTools::convertMoneylineToDecimal($a_sMoneyLine, $a_bNoRounding);
    }

    /**
     * Standardizes a date to the YYYY-MM-DD format
     *
     * @param string $a_sDate Date to convert
     * @return string Date in format YYYY-MM-DD
     * @deprecated Use OddsTools::standardizeDate() instead
     */
    public static function standardizeDate($a_sDate)
    {
        return OddsTools::standardizeDate($a_sDate);
    }

    /**
     * Checks format on odds (ex: +180 / -220) and corrects if not ok
     */
    public static function formatOdds($a_sOdds)
    {
        if ($a_sOdds == 'EV' || $a_sOdds == 'ev' || $a_sOdds == 'Even' || $a_sOdds == 'even' || $a_sOdds == 'EVEN')
        {
            $a_sOdds = '+100';
        }
        //Check if odds is in decimal format, if so change it to moneyline
        else if (preg_match('/[0-9]*\\.[0-9]*/', $a_sOdds))
        {
            $a_sOdds = ParseTools::convertOddsEUToUS($a_sOdds);
        }
        return $a_sOdds;
    }

    /**
     * Formats a fighters name in a post where (hopefully) nicknames and such are stripped
     *
     * @deprecated Use formatName() instead
     */
    public static function formatFighterName($a_sFighterName)
    {
        return ParseTools::formatName($a_sFighterName);
    }

    public static function formatName($a_sName)
    {
        $sNewName = str_replace('&quot;', '"', $a_sName);

        //Replaces all words surrounded by [, ( or " (e.g. nicknames):
        $sNewName = preg_replace('/[\\[("\'“Â][a-zA-Z0-9\\/\\?\'\\.\\,\\s-]*[\\])"\'”Â]/', ' ', $sNewName);

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
        if ($a_bOnlyLast == true)
        {
            //Gets only last part (e.g. Santos from Junior Dos Santos)
            $aParts = explode(' ', $a_sName);
            return $aParts[count($aParts) - 1];    
        }
        else
        {
            //Get all last names (e.g Dos Santos from Junior Dos Santos)
            $aParts = explode(' ', $a_sName, 2);
            if (sizeof($aParts) == 1)
            {
                return $aParts[0];
            }
            return $aParts[1];
        }
    }

    public static function getInitialsFromName($a_sName)
    {
        $aInitials = array();
        foreach (explode(" ", $a_sName) as $sName)
        {
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
        if (preg_match('/^[a-zA-Z0-9\'\\s-]*$/', $a_sTeamName))
        {
            return true;
        }
        return false;
    }

    /**
     * Checks if a betting string should be considered a prop or not
     *
     * Current criterias:
     * - Consists of 4 or more words
     *
     * @param String $a_sName Betting string to test
     * @return boolean If string is a prop or not
     */
    public static function isProp($a_sName)
    {
//Check if either parameter is 4 or more words, then its probably a prop bet
        if (count(explode(" ", ParseTools::formatName($a_sName))) >= 4)
        {
            return true;
        }

        return false;
    }

    public static function matchBlock($a_sContents, $a_sRegExp)
    {
        $aMatches = null;
        preg_match_all($a_sRegExp, $a_sContents, $aMatches, PREG_SET_ORDER);
        return $aMatches;
    }

    /**
     * Stores a correlation for the lifetime of the execution
     * 
     * Correlation can be used to link different parsed objects with eachother
     * e.g. matchups and props
     * 
     * @param String $a_sSource Key
     * @param String $a_sTarget Value
     * @return boolean If correlation was stored or not
     */
    public static function saveCorrelation($a_sSource, $a_sTarget)
    {
        self::$aCorrelationTable[$a_sSource] = $a_sTarget;
        return true;
    }

    /**
     * Retrieves a stored correlation
     * 
     * @param String $a_sSource Key
     * @return String Value for the specified key 
     */
    public static function getCorrelation($a_sSource)
    {
        if (isset(self::$aCorrelationTable[$a_sSource]))
        {
            return self::$aCorrelationTable[$a_sSource];
        }
        return null;
    }

    public static function clearCorrelations()
    {
        self::$aCorrelationTable = array();
        return true;
    }

    public static function getAllCorrelations()
    {
        return self::$aCorrelationTable;
    }

    public static function getStoredContentForURL($a_sURL)
    {
        if (isset(self::$aFeedStorage[$a_sURL]))
        {
            return self::$aFeedStorage[$a_sURL];
        }
        return false;
    }

    /**
     * Replaces all foreign characters with their normal counterpart
     */
    public static function stripForeignChars($a_sText)
    {
        $a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ '; 
        $b = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr '; 
        $a_sText = utf8_decode($a_sText);     
        $a_sText = strtr($a_sText, utf8_decode($a), $b); 
        return utf8_encode($a_sText); 
    }

}

function getArrayVal($a_aArray, $a_sKey)
{
    return $a_aArray[$a_sKey];
}

?>