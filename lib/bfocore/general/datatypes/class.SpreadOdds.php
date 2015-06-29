<?php

/**
 * SpreadOdds class
 *
 * The odds for the teams are always sorted lexiographically by name
 * Example: Quinton Jackson (Team 2 odds) vs Chuck Liddell (Team 1 odds)
 */
class SpreadOdds
{
    private $iMatchupID;
    private $iBookieID;
    private $fTeam1Spread;
    private $fTeam2Spread;
    private $sTeam1Moneyline;
    private $sTeam2Moneyline;
    private $sDate;

    public function __construct($a_iMatchupID, $a_iBookieID, $a_fTeam1Spread, $a_fTeam2Spread, $a_sTeam1Moneyline, $a_sTeam2Moneyline, $a_sDate)
    {
        $this->iMatchupID = $a_iMatchupID;
        $this->iBookieID = $a_iBookieID;

        $this->fTeam1Spread = (float) $a_fTeam1Spread;
        $this->fTeam2Spread = (float) $a_fTeam2Spread;

        if (strtoupper($a_sTeam1Moneyline) == 'EV' || strtoupper($a_sTeam1Moneyline) == 'EVEN')
        {
            $this->sTeam1Moneyline = '100';
        }
        else
        {
            $this->sTeam1Moneyline = str_replace('+', '', $a_sTeam1Moneyline);
        }

        if (strtoupper($a_sTeam2Moneyline) == 'EV' || strtoupper($a_sTeam2Moneyline) == 'EVEN')
        {
            $this->sTeam2Moneyline = '100';
        }
        else
        {
            $this->sTeam2Moneyline = str_replace('+', '', $a_sTeam2Moneyline);
        }

        $this->sDate = $a_sDate;
    }

    public function getMatchupID()
    {
        return $this->iMatchupID;
    }

    public function getBookieID()
    {
        return $this->iBookieID;
    }

    public function getDate()
    {
        return $this->sDate;
    }

    public function getMoneyline($a_iTeam)
    {
        switch ($a_iTeam)
        {
            case 1: return $this->sTeam1Moneyline;
                break;
            case 2: return $this->sTeam2Moneyline;
                break;
            default: return 0;
                break;
        }
    }

    /**
     * No rounding is actually not really no rounding but rather up to 5 decimals. Should be enough
     */
    public function getMoneylineAsDecimal($a_iTeam, $a_bNoRounding = false)
    {
        $iOdds = $this->getMoneyline($a_iTeam);
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
     * Returns a string representation of the odds.
     * Should only be used for presentation and not for calculations
     */
    public function getMoneylineAsString($a_iTeam)
    {
        $sOdds = $this->getMoneyline($a_iTeam);
        if ($sOdds == 0)
        {
            return 'error';
        }
        else if ($sOdds == 100)
        {
            return 'EV';
        }
        else if ($sOdds > 0)
        {
            return '+' . $sOdds;
        }
        else
        {
            return $sOdds;
        }
    }

    public function getSpread($a_iTeam)
    {
        switch ($a_iTeam)
        {
            case 1: return $this->fTeam1Spread;
                break;
            case 2: return $this->fTeam2Spread;
                break;
            default: return 0;
                break;
        }
    }

    public function getSpreadAsString($a_iTeam)
    {
        //Replace .5 with ½
        return str_replace('.5', '½', (string) $this->getSpread($a_iTeam)) . ($this->getMoneylineAsString($a_iTeam) == 'EV' ? ' ' : '') . $this->getMoneylineAsString($a_iTeam);
    }

    public function equals($a_oSpreadOdds)
    {
        return ($this->iMatchupID == $a_oSpreadOdds->getMatchupID() &&
            $this->iBookieID == $a_oSpreadOdds->getBookieID() &&
            $this->fTeam1Spread = $a_oSpreadOdds->getSpread(1) &&
            $this->fTeam2Spread = $a_oSpreadOdds->getSpread(2) &&
            $this->sTeam1Moneyline == $a_oSpreadOdds->getMoneyline(1) &&
            $this->sTeam2Moneyline == $a_oSpreadOdds->getMoneyline(2));
    }

    public function convertOddsEUToUS($a_iOdds)
    {
        $a_iOdds--;
        if ($a_iOdds < 1)
        {
            return '-' . round((1 / $a_iOdds) * 100);
        }
        else //(a_iOdds >= 1)

        {
            return round($a_iOdds * 100);
        }
    }
}

?>