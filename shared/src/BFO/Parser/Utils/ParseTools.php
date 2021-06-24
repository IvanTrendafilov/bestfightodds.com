<?php

namespace BFO\Parser\Utils;

use BFO\DataTypes\FightOdds;

/**
 * Contains useful tools used when parsing matchups and betting lines
 */
class ParseTools
{
    private static $correlation_table = [];
    private static $stored_content = [];

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
        if ($credentials) {
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

        //TODO: Hardcoded URL specific
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

    public static function retrieveMultiplePagesFromURLs(array $urls, array $extra_curl_opts = null): bool
    {
        $curl_channels = [];
        $mh = curl_multi_init();

        //Create individual channels for each URL
        foreach ($urls as $url) {
            $curl_channels[$url] = curl_init();

            //Add credentials if applicable
            $credentials = self::getCredentialsFromURL($url);
            if ($credentials) {
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

            //Add extra curl opts if specified
            if ($extra_curl_opts) {
                if (!empty($extra_curl_options)) {
                    curl_setopt_array($curl_channels[$url], $extra_curl_options);
                }
            }

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

    private static function getCredentialsFromURL(string $url): ?string
    {
        $matches = [];
        preg_match('/:\/\/([^:]+:[^@]+)@/', $url, $matches);
        if (isset($matches[1])) {
            return $matches[1];
        }
        return null;
    }

    public static function formatName(string $team_name): string
    {
        $new_name = str_replace('&quot;', '"', $team_name);
        $new_name = str_replace('?', '', $team_name);

        //Replaces all words surrounded by [, ( or " (e.g. nicknames):
        $new_name = preg_replace('/[\\[("\'“Â][a-zA-Z0-9\\/\\?\'\\.\\,\\s-]*[\\])"\'”Â]/', ' ', $new_name);

        $new_name = str_ireplace('(3 RD', '', $new_name);
        $new_name = str_ireplace('(3RND', '', $new_name);
        $new_name = str_ireplace('S)', '', $new_name);

        //Trims multiple spaces to single space:
        $new_name = preg_replace('/\h{2,}/', ' ', $new_name);

        //Fixes various foreign characters:
        $new_name = self::stripForeignChars($new_name);

        //Capitalize name and remove any trailing chars:
        $new_name = trim(strtoupper($new_name), ' -\t');

        return $new_name;
    }

    public static function getLastnameFromName(string $name, bool $single_last_only = true): string
    {
        if ($single_last_only == true) {
            //Gets only last part (e.g. Santos from Junior Dos Santos)
            $parts = explode(' ', $name);
            return $parts[count($parts) - 1];
        } else {
            //Get all last names (e.g Dos Santos from Junior Dos Santos)
            $parts = explode(' ', $name, 2);
            if (sizeof($parts) == 1) {
                return $parts[0];
            }
            return $parts[1];
        }
    }

    /**
     * Convert name from Lastname, Firstname to Firstname Lastname
     */
    public static function convertCommaNameToFullName(string $name): string
    {
        //Workaround for comma separate prop after actual name
        $pos = strpos($name, 'by KO,');
        if ($pos) {
            $first_part = substr($name, 0, $pos);
            $last_part = substr($name, $pos);
            return trim(self::convertCommaNameToFullName($first_part)) . ' ' . trim($last_part);
        }

        $comma_pos = strpos($name, ',');
        if ($comma_pos) {
            return trim(substr($name, $comma_pos + 1))
                . ' ' . substr($name, 0, $comma_pos);
        }
        return $name;
    }

    public static function getInitialsFromName(string $team_name): array
    {
        $initials = [];
        foreach (explode(" ", $team_name) as $name) {
            $initials[] = strtoupper(substr($name, 0, 1));
        }
        return $initials;
    }

    public static function getArbitrage(string $moneyline1, string $moneyline2): float
    {
        $odds = new FightOdds(-1, -1, $moneyline1, $moneyline2, -1);

        $arbitrage_value = (pow($odds->getFighterOddsAsDecimal(1, true), -1)
            + pow($odds->getFighterOddsAsDecimal(2, true), -1));

        return $arbitrage_value;
    }

    public static function checkTeamName(string $team_name): bool
    {
        if (preg_match('/^[a-zA-Z0-9\'\\s-]*$/', $team_name)) {
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
    public static function isProp(string $prop_name): bool
    {
        //Check if either parameter is 4 or more words, then its probably a prop bet
        if (count(explode(" ", ParseTools::formatName($prop_name))) >= 4) {
            return true;
        }
        //Check if prop is either YES or NO, if that is the case then it is probably a prop
        if (strcasecmp($prop_name, 'yes') == 0 || strcasecmp($prop_name, 'no') == 0) {
            return true;
        }

        return false;
    }

    public static function matchBlock(string $contents, string $regexp): array
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
    public static function stripForeignChars(string $text): string
    {
        $a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ ';
        $b = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr ';
        $text = utf8_decode($text);
        $text = strtr($text, utf8_decode($a), $b);
        return utf8_encode($text);
    }
}
