<?php

namespace BFO\Utils;

/**
 * OddsTools
 *
 * Contains tools used to manipulate, verify and format odds in various different scenarios. Replaces ParseTools
 * which was initially ment only for Parsing scenarios
 *
 */
class OddsTools
{
    /**
     * Converts Odds in EU format (decimal) to US (moneyline)
     *
     * Example: 1.59 converts to -170  or  3.4 converts to +240
     *
     * @param float $a_iOdds Odds in decimal format to convert
     * @return string Odds in moneyline format
     */
    public static function convertDecimalToMoneyline($decimal_odds)
    {
        $decimal_odds = (float) $decimal_odds - 1.0;
        if ($decimal_odds == 0) {
            return '-25000';
        }
        if ($decimal_odds < 1) {
            return '-' . round((1 / $decimal_odds) * 100);
        } else {
            return round($decimal_odds * 100);
        }
    }

    public static function convertMoneylineToDecimal($moneyline, bool $no_rounding = false): float
    {
        $moneyline_odds = $moneyline;
        $decimal_odds = 0;
        if ($moneyline_odds == 100) {
            return 2.0;
        } elseif ($moneyline_odds > 0) {
            if ($no_rounding == true) {
                $decimal_odds = round((($moneyline_odds / 100) + 1) * 100000) / 100000;
            } else {
                $decimal_odds = round((($moneyline_odds / 100) + 1) * 100) / 100;
            }
        } else {
            $moneyline_odds = substr($moneyline_odds, 1);
            if ($no_rounding == true) {
                $decimal_odds = round(((100 / $moneyline_odds) + 1) * 100000) / 100000;
            } else {
                $decimal_odds = round(((100 / $moneyline_odds) + 1) * 100) / 100;
            }
        }
        return $decimal_odds;
    }

    /**
     * Takes a name in the format of Nick Diaz and changes it to N Diaz
     */
    public static function shortenName(string $name): string
    {
        $pieces = explode(" ", $name);
        //If name only contains one single name, do not shorten
        if (count($pieces) == 1) {
            return $name;
        }

        $name = '';
        for ($iX = 0; $iX < sizeof($pieces); $iX++) {
            if ($iX == 0) {
                $name .= substr($pieces[$iX], 0, 1);
            } else {
                $name .= ' ' . $pieces[$iX];
            }
        }
        return $name;
    }

    /**
     * Checks if moneyline odds is in the right format
     */
    public static function checkCorrectOdds($odds)
    {
        $odds = trim($odds);
        if (strtoupper($odds) == 'EV' || strtoupper($odds) == 'EVEN') {
            return true;
        }
        if (preg_match('/[+-]{0,1}[0-9]{2,5}/', $odds)) {
            return true;
        }
        return false;
    }

    /**
     * Standardizes a date to the YYYY-MM-DD format
     */
    public static function standardizeDate(string $date): string
    {
        return date('Y-m-d', strtotime($date));
    }

    /**
     * Compares two names and returns the fsim value for the check
     */
    public static function compareNames($name1, $name2)
    {
        $name1 = strtoupper($name1);
        $name2 = strtoupper($name2);

        //Find the name with least number of parts, this will be deciding the number of parts for comparison
        $name1_parts = explode(' ', $name1);
        $name2_parts = explode(' ', $name2);
        $parts_count = min(count($name1_parts), count($name2_parts));

        $name1_combinations = self::getNameCombinations($name1_parts, $parts_count);
        $name2_combinations = self::getNameCombinations($name2_parts, $parts_count);

        $top_similarity = 0;
        foreach ($name1_combinations as $aName1) {
            foreach ($name2_combinations as $aName2) {
                $similarity = 0;
                similar_text($aName1, $aName2, $similarity);
                $top_similarity = $similarity > $top_similarity ? $similarity : $top_similarity;
            }
        }
        return $top_similarity;
    }

    private static function getNameCombinations(array $name_parts, int $parts_count): array
    {
        $return_names = [];
        //Add the original name, untouched
        $return_names[] = implode(' ', $name_parts);

        //Get all combinations by putting together the different parts, limited to the parts argument

        self::depth_picker($name_parts, "", $return_names, $parts_count);
        //Add a shortened version of the name using a letter for the first name (e.g. N Diaz for Nathan Diaz)
        if (count($name_parts) > 1) {
            $return_names[] = OddsTools::shortenName(implode(' ', $name_parts));
        }
        return $return_names;
    }

    private static function depth_picker($arr, $temp_string, &$collect, $part_count)
    {
        if ($temp_string != "") {
            if (substr_count(trim($temp_string), " ") == $part_count - 1) {
                $collect[] = trim($temp_string);
            }
        }
        for ($i = 0; $i < sizeof($arr); $i++) {
            $arrcopy = $arr;
            $elem = array_splice($arrcopy, $i, 1); // removes and returns the i'th element
            if (sizeof($arrcopy) > 0) {
                self::depth_picker($arrcopy, $temp_string . " " . $elem[0], $collect, $part_count);
            } else {
                if (substr_count(trim($temp_string . " " . $elem[0]), " ") == $part_count - 1) {
                    $collect[] = trim($temp_string . " " . $elem[0]);
                }
            }
        }
    }
}
