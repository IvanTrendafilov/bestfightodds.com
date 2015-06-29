<?php

class PropBet
{

    private $iMatchupID; //Matchup the prop is linked to
    private $iTeamNumber; //Used if prop specifies a certain team in the matchup
    private $iBookieID;
    private $sPropName;
    private $sPropOdds;
    private $sNegPropName;
    private $sNegPropOdds;
    private $iPropTypeID;
    private $sDate;

    public function __construct($a_iMatchupID, $a_iBookieID, $a_sPropName, $a_sPropOdds, $a_sNegPropName, $a_sNegPropOdds, $a_iPropTypeID, $a_sDate, $a_iTeamNumber = 0)
    {
        $this->iMatchupID = $a_iMatchupID;
        $this->iBookieID = $a_iBookieID;
        $this->sPropName = $a_sPropName;
        $this->sPropOdds = $a_sPropOdds;
        $this->sNegPropName = $a_sNegPropName;
        $this->sNegPropOdds = $a_sNegPropOdds;
        $this->iPropTypeID = $a_iPropTypeID;
        $this->iTeamNumber = $a_iTeamNumber;
        $this->sDate = $a_sDate;
    }

    public function getMatchupID()
    {
        return $this->iMatchupID;
    }

    public function setMatchupID($a_iMatchupID)
    {
        $this->iMatchupID = $a_iMatchupID;
    }

    public function getBookieID()
    {
        return $this->iBookieID;
    }

    public function setBookieID($a_iBookieID)
    {
        $this->iBookieID = $a_iBookieID;
    }


    /*
    @deprecated Use getOdds(teamNum) instead
    */
    public function getPropOdds()
    {
        return $this->sPropOdds;
    }

    public function setPropOdds($a_sPropOdds)
    {
        $this->sPropOdds = $a_sPropOdds;
    }

    public function getNegPropName()
    {
        return $this->sNegPropName;
    }

    public function setNegPropName($a_sNegPropName)
    {
        $this->sNegPropName = $a_sNegPropName;
    }

    /*
    @deprecated Use getOdds(teamNum) instead
    */
    public function getNegPropOdds()
    {
        return $this->sNegPropOdds;
    }

    public function setNegPropOdds($a_sNegPropOdds)
    {
        $this->sNegPropOdds = $a_sNegPropOdds;
    }

    public function getPropTypeID()
    {
        return $this->iPropTypeID;
    }

    public function setPropTypeID($a_iPropTypeID)
    {
        $this->iPropTypeID = $a_iPropTypeID;
    }

    public function getDate()
    {
        return $this->sDate;
    }

    public function setDate($a_sDate)
    {
        $this->sDate = $a_sDate;
    }

    public function getPropName()
    {
        return $this->sPropName;
    }

    public function setPropName($sPropName)
    {
        $this->sPropName = $sPropName;
    }

    private function getOddsAsString($a_sOdds)
    {
        if ($a_sOdds == 0)
        {
            return 'error';
        }
        else if ($a_sOdds > 0)
        {
            return '+' . $a_sOdds;
        }
        else
        {
            return $a_sOdds;
        }
    }

    public function getPropOddsAsString()
    {
        return $this->getOddsAsString($this->sPropOdds);
    }

    public function getNegPropOddsAsString()
    {
        return $this->getOddsAsString($this->sNegPropOdds);
    }

    public function getTeamNumber()
    {
        return $this->iTeamNumber;
    }

    public function setTeamNumber($a_iTeamNumber)
    {
        $this->iTeamNumber = $a_iTeamNumber;
    }

    public function equals($a_oPropBet)
    {
        $bEquals = ($this->iMatchupID == $a_oPropBet->getMatchupID() &&
                $this->iBookieID == $a_oPropBet->getBookieID() &&
                $this->sPropOdds == $a_oPropBet->getPropOdds() &&
                $this->sNegPropOdds == $a_oPropBet->getNegPropOdds() &&
                $this->iPropTypeID == $a_oPropBet->getPropTypeID()
                );
        return $bEquals;
    }

    public function getOdds($a_iTeamNumber)
    {
        if ($a_iTeamNumber == '1')
        {
            return $this->sPropOdds;
        }
        else if ($a_iTeamNumber == '2')
        {
            return $this->sNegPropOdds;
        }
        return null;
    }


    public static function moneylineToDecimal($a_iMoneyline, $a_bNoRounding = false)
    {
        $fOdds = 0;
        if ($a_iMoneyline == 100)
        {
            return 2.0;
        }
        else if ($a_iMoneyline > 0)
        {
            if ($a_bNoRounding == true)
            {
                $fOdds = round((($a_iMoneyline / 100) + 1) * 100000) / 100000;
            }
            else
            {
                $fOdds = round((($a_iMoneyline / 100) + 1) * 100) / 100;
            }
        }
        else
        {
            $a_iMoneyline = substr($a_iMoneyline, 1);
            if ($a_bNoRounding == true)
            {
                $fOdds = round(((100 / $a_iMoneyline) + 1) * 100000) / 100000;
            }
            else
            {
                $fOdds = round(((100 / $a_iMoneyline) + 1) * 100) / 100;
            }
        }
        return $fOdds;
    }

    public static function decimalToMoneyline($a_fDecimal)
    {
        $a_fDecimal--;
        if ($a_fDecimal < 1)
        {
            return '-' . round((1 / $a_fDecimal) * 100);
        }
        else //(a_iOdds >= 1)
        {
            return round($a_fDecimal * 100);
        }
    }


    


}

?>
