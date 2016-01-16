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
        if ($a_fOdds < 1)
        {
            return '-' . round((1 / $a_fOdds) * 100);
        }
        else
        {
            return round($a_fOdds * 100);
        }
    }

    public static function convertMoneylineToDecimal($a_sMoneyLine, $a_bNoRounding = false)
    {
        $iOdds = $a_sMoneyLine;
        $fOdds = 0;
        if ($iOdds == 100)
        {
            return 2.0;
        }
        else if ($iOdds > 0)
        {
            if ($a_bNoRounding == true)
            {
                $fOdds = round((($iOdds / 100) + 1) * 100000) / 100000;
            }
            else
            {
                $fOdds = round((($iOdds / 100) + 1) * 100) / 100;
            }
        }
        else
        {
            $iOdds = substr($iOdds, 1);
            if ($a_bNoRounding == true)
            {
                $fOdds = round(((100 / $iOdds) + 1) * 100000) / 100000;
            }
            else
            {
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
        if (count($aPieces) == 1)
        {
            return $a_sFighterName;
        }

        $a_sFighterName = '';
        for ($iX = 0; $iX < sizeof($aPieces); $iX++)
        {
            if ($iX == 0)
            {
                $a_sFighterName .= substr($aPieces[$iX], 0, 1);
            }
            else
            {
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

        if (strtoupper($a_sOdds) == 'EV' || strtoupper($a_sOdds) == 'EVEN')
        {
            return true;
        }

        if (preg_match('/[+-]{0,1}[0-9]{2,5}/', $a_sOdds))
        {
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
        //Shorten name to N Diaz instead of Nick Diaz. Not for fighters with 1 name like Babalu
        if (strpos(trim($a_sName1), ' '))
            $a_sName1 = OddsTools::shortenName($a_sName1);
        if (strpos(trim($a_sName2), ' '))
            $a_sName2 = OddsTools::shortenName($a_sName2);

        $fSim = 0;
        similar_text($a_sName1, $a_sName2, $fSim);
        return $fSim;
    }

}







?>