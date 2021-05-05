<?php

namespace BFO\DataTypes;

/**
 * FightOdds class
 *
 * The odds for the fighters are always sorted lexiographically by name
 * Example: Quinton Jackson (Fighter 2 odds) vs Chuck Liddell (Fighter 1 odds)
 */
class FightOdds
{
    private $iFightID;
    private $iBookieID;
    private $sFighter1Odds;
    private $sFighter2Odds;
    private $sDate;

    public function __construct($a_iFightID, $a_iBookieID, $a_sFighter1Odds, $a_sFighter2Odds, $a_sDate)
    {
        $this->iFightID = $a_iFightID;
        $this->iBookieID = $a_iBookieID;

        if (strtoupper($a_sFighter1Odds) == 'EV' || strtoupper($a_sFighter1Odds) == 'EVEN') {
            $this->sFighter1Odds = '100';
        } else {
            $this->sFighter1Odds = str_replace('+', '', $a_sFighter1Odds);
        }

        if (strtoupper($a_sFighter2Odds) == 'EV' || strtoupper($a_sFighter2Odds) == 'EVEN') {
            $this->sFighter2Odds = '100';
        } else {
            $this->sFighter2Odds = str_replace('+', '', $a_sFighter2Odds);
        }

        $this->sDate = $a_sDate;
    }

    public function getFightID()
    {
        return $this->iFightID;
    }

    public function getBookieID()
    {
        return $this->iBookieID;
    }

    /*
    @deprecated Use getOdds(teamNum) instead
    */
    public function getFighter1Odds()
    {
        return $this->sFighter1Odds;
    }

    /*
    @deprecated Use getOdds(teamNum) instead
    */
    public function getFighter2Odds()
    {
        return $this->sFighter2Odds;
    }

    public function getDate()
    {
        return $this->sDate;
    }

    /*
    @deprecated Use getOdds(teamNum) instead
    */
    public function getFighterOdds($a_iFighter)
    {
        switch ($a_iFighter) {
            case 1: return $this->sFighter1Odds;
                break;
            case 2: return $this->sFighter2Odds;
                break;
            default: return 0;
        }
    }

    /**
     * No rounding is actually not really no rounding but rather up to 5 decimals. Should be enough
     */
    public function getFighterOddsAsDecimal($a_iFighter, $a_bNoRounding = false)
    {
        $iOdds = $this->getFighterOdds($a_iFighter);
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
     * Returns a string representation of the odds.
     * Should only be used for presentation and not for calculations
     */
    public function getFighterOddsAsString($a_iFighter)
    {
        $sOdds = $this->getFighterOdds($a_iFighter);
        if ($sOdds == 0) {
            return 'error';
        } elseif ($sOdds > 0) {
            return '+' . $sOdds;
        } else {
            return $sOdds;
        }
    }

    public function equals($a_oFightOdds)
    {
        $bEquals = ($this->iFightID == $a_oFightOdds->getFightID() &&
                $this->iBookieID == $a_oFightOdds->getBookieID() &&
                $this->sFighter1Odds == $a_oFightOdds->getFighterOdds(1) &&
                $this->sFighter2Odds == $a_oFightOdds->getFighterOdds(2));
        return $bEquals;
    }


    public function getOdds($a_iTeamNumber)
    {
        switch ($a_iTeamNumber) {
            case 1: return $this->sFighter1Odds;
                break;
            case 2: return $this->sFighter2Odds;
                break;
            default: return 0;
        }
    }

    public static function convertOddsEUToUS($a_iOdds)
    {
        $a_iOdds--;
        if ($a_iOdds < 1) {
            return '-' . round((1 / $a_iOdds) * 100);
        } else { //(a_iOdds >= 1)
            return round($a_iOdds * 100);
        }
    }

    /*
    @deprecated Use decimalToMoneyline(decimal) instead
    */
    public static function moneylineToDecimal($a_iMoneyline, $a_bNoRounding = false)
    {
        $fOdds = 0;
        if ($a_iMoneyline == 100) {
            return 2.0;
        } elseif ($a_iMoneyline > 0) {
            if ($a_bNoRounding == true) {
                $fOdds = round((($a_iMoneyline / 100) + 1) * 100000) / 100000;
            } else {
                $fOdds = round((($a_iMoneyline / 100) + 1) * 100) / 100;
            }
        } else {
            $a_iMoneyline = substr($a_iMoneyline, 1);
            if ($a_bNoRounding == true) {
                $fOdds = round(((100 / $a_iMoneyline) + 1) * 100000) / 100000;
            } else {
                $fOdds = round(((100 / $a_iMoneyline) + 1) * 100) / 100;
            }
        }
        return $fOdds;
    }

    public static function decimalToMoneyline($a_fDecimal)
    {
        $a_fDecimal--;
        if ($a_fDecimal < 1) {
            return '-' . round((1 / $a_fDecimal) * 100);
        } else { //(a_iOdds >= 1)
            return round($a_fDecimal * 100);
        }
    }
}
