<?php

/**
 * TotalsOdds class
 *
 */
class TotalOdds
{
    private $iMatchupID;
    private $iBookieID;
    private $fTotalPoints;
    private $sOverMoneyline;
    private $sUnderMoneyline;
    private $sDate;

    public function __construct($a_iMatchupID, $a_iBookieID, $a_fTotalPoints, $a_sOverMoneyline, $a_sUnderMoneyline, $a_sDate)
    {
        $this->iMatchupID = $a_iMatchupID;
        $this->iBookieID = $a_iBookieID;
        $this->fTotalPoints = $a_fTotalPoints;

        if (strtoupper($a_sOverMoneyline) == 'EV' || strtoupper($a_sOverMoneyline) == 'EVEN')
        {
            $this->sOverMoneyline = '100';
        }
        else
        {
            $this->sOverMoneyline = str_replace('+', '', $a_sOverMoneyline);
        }

        if (strtoupper($a_sUnderMoneyline) == 'EV' || strtoupper($a_sUnderMoneyline) == 'EVEN')
        {
            $this->sUnderMoneyline = '100';
        }
        else
        {
            $this->sUnderMoneyline = str_replace('+', '', $a_sUnderMoneyline);
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

    public function getOverMoneyline()
    {
        return $this->sOverMoneyline;
    }

    public function getUnderMoneyline()
    {
        return $this->sUnderMoneyline;
    }

    public function getOverMoneylineAsString()
    {
        return $this->getMoneylineAsString($this->sOverMoneyline);
    }

    public function getUnderMoneylineAsString()
    {
        return $this->getMoneylineAsString($this->sUnderMoneyline);
    }

    public function getOverMoneylineAsDecimal()
    {
        return $this->getMoneylineAsDecimal($this->sOverMoneyline);
    }

    public function getUnderMoneylineAsDecimal()
    {
        return $this->getMoneylineAsDecimal($this->sUnderMoneyline);
    }

    public function getOverAsString()
    {
        return 'o' . (fmod($this->fTotalPoints, 1) == 0 ? round($this->fTotalPoints) : $this->fTotalPoints) . ($this->getOverMoneylineAsString() == 'EV' ? ' ' : '') . $this->getOverMoneylineAsString();
    }

    public function getUnderAsString()
    {
        return 'u' . (fmod($this->fTotalPoints, 1) == 0 ? round($this->fTotalPoints) : $this->fTotalPoints) . ($this->getUnderMoneylineAsString() == 'EV' ? ' ' : '') . $this->getUnderMoneylineAsString();
    }


    private function getMoneylineAsString($a_sMoneyline)
    {
        $sOdds = $a_sMoneyline;
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


    /**
     * No rounding is actually not really no rounding but rather up to 5 decimals. Should be enough
     */
    private function getMoneylineAsDecimal($a_sMoneyline, $a_bNoRounding = false)
    {
        $iOdds = $a_sMoneyline;
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

    public function getTotal()
    {
        return $this->fTotalPoints;
    }

    public function equals($a_oTotalOdds)
    {
        return ($this->iMatchupID == $a_oTotalOdds->getMatchupID() &&
            $this->iBookieID == $a_oTotalOdds->getBookieID() &&
            $this->fTotalPoints = $a_oTotalOdds->getTotal(1) &&
            $this->sOverMoneyline == $a_oTotalOdds->getOverMoneyline() &&
            $this->sUnderMoneyline == $a_oTotalOdds->getUnderMoneyline());
    }

    public function convertOddsEUToUS($a_iOdds)
    {
        $a_iOdds--;
        if ($a_iOdds < 1)
        {
            return '-' . round((1 / $a_iOdds) * 100);
        }
        else //(a_iOdds > 1)

        {
            return round($a_iOdds * 100);
        }
    }
}

?>