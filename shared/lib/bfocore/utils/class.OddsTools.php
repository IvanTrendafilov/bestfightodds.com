<?php

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
    public static function convertDecimalToMoneyline($a_fOdds)
    {
        $a_fOdds = (float) $a_fOdds - 1.0;
        if ($a_fOdds == 0) {
            return '-25000';
        }
        if ($a_fOdds < 1) {
            return '-' . round((1 / $a_fOdds) * 100);
        } else {
            return round($a_fOdds * 100);
        }
    }

    public static function convertMoneylineToDecimal($a_sMoneyLine, $a_bNoRounding = false)
    {
        $iOdds = $a_sMoneyLine;
        $fOdds = 0;
        if ($iOdds == 100) {
            return 2.0;
        } elseif ($iOdds > 0) {
            if ($a_bNoRounding == true) {
                $fOdds = round((($iOdds / 100) + 1) * 100000) / 100000;
            } else {
                $fOdds = round((($iOdds / 100) + 1) * 100) / 100;
            }
        } else {
            $iOdds = substr($iOdds, 1);
            if ($a_bNoRounding == true) {
                $fOdds = round(((100 / $iOdds) + 1) * 100000) / 100000;
            } else {
                $fOdds = round(((100 / $iOdds) + 1) * 100) / 100;
            }
        }
        return $fOdds;
    }

    /**
     * Takes a name in the format of Nick Diaz and changes it to N Diaz
     */
    public static function shortenName($a_sFighterName)
    {
        $aPieces = explode(" ", $a_sFighterName);

        //If name only contains one single name, do not shorten
        if (count($aPieces) == 1) {
            return $a_sFighterName;
        }

        $a_sFighterName = '';
        for ($iX = 0; $iX < sizeof($aPieces); $iX++) {
            if ($iX == 0) {
                $a_sFighterName .= substr($aPieces[$iX], 0, 1);
            } else {
                $a_sFighterName .= ' ' . $aPieces[$iX];
            }
        }
        return $a_sFighterName;
    }

    /**
     * Checks if moneyline odds is in the right format
     */
    public static function checkCorrectOdds($a_sOdds)
    {
        $a_sOdds = trim($a_sOdds);

        if (strtoupper($a_sOdds) == 'EV' || strtoupper($a_sOdds) == 'EVEN') {
            return true;
        }

        if (preg_match('/[+-]{0,1}[0-9]{2,5}/', $a_sOdds)) {
            return true;
        }

        return false;
    }

    /**
     * Standardizes a date to the YYYY-MM-DD format
     *
     * @param string $a_sDate Date to convert
     * @return string Date in format YYYY-MM-DD
     */
    public static function standardizeDate($a_sDate)
    {
        return date('Y-m-d', strtotime($a_sDate));
    }


    /**
     * Compares two names and returns the fsim value for the check
     */
    public static function compareNames($a_sName1, $a_sName2)
    {
        //Find the name with least number of parts, this will be deciding the number of parts for comparison
        $aNameParts1 = explode(' ', $a_sName1);
        $aNameParts2 = explode(' ', $a_sName2);
        $iPartCount = min(count($aNameParts1), count($aNameParts2));

        $aNames1 = self::getNameCombinations($aNameParts1, $iPartCount);
        $aNames2 = self::getNameCombinations($aNameParts2, $iPartCount);

        $fTopSim = 0;
        foreach ($aNames1 as $aName1) {
            foreach ($aNames2 as $aName2) {
                $fSim = 0;
                similar_text($aName1, $aName2, $fSim);
                $fTopSim = $fSim > $fTopSim ? $fSim : $fTopSim;
            }
        }
        return $fTopSim;
    }

    private static function getNameCombinations($a_sNameParts, $a_iParts)
    {
        $aRetNames = [];
        //Add the original name, untouched
        $aRetNames[] = implode(' ', $a_sNameParts);
        
        //Get all combinations by putting together the different parts, limited to the parts argument

        self::depth_picker($a_sNameParts, "", $aRetNames, $a_iParts);
        //Add a shortened version of the name using a letter for the first name (e.g. N Diaz for Nathan Diaz)
        if (count($a_sNameParts) > 1) {
            $aRetNames[] = OddsTools::shortenName(implode(' ', $a_sNameParts));
        }
        return $aRetNames;
    }

    private static function depth_picker($arr, $temp_string, &$collect, $part_count)
    {
        if ($temp_string != "") {
            if (substr_count(trim($temp_string), " ") == $part_count - 1) {
                $collect []= trim($temp_string);
            }
        }
        for ($i=0; $i<sizeof($arr);$i++) {
            $arrcopy = $arr;
            $elem = array_splice($arrcopy, $i, 1); // removes and returns the i'th element
            if (sizeof($arrcopy) > 0) {
                self::depth_picker($arrcopy, $temp_string ." " . $elem[0], $collect, $part_count);
            } else {
                if (substr_count(trim($temp_string. " " . $elem[0]), " ") == $part_count - 1) {
                    $collect[] = trim($temp_string. " " . $elem[0]);
                }
            }
        }
    }
}
