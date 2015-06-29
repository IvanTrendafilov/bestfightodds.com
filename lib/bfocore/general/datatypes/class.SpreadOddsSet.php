<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class SpreadOddsSet
{
    private $iID;
    private $iMatchupID;
    private $iBookieID;
    private $sDate;
    private $aSpreadOdds;

    public function __construct($a_iID, $a_iMatchupID, $a_iBookieID, $a_sDate)
    {
        $this->iID = $a_iID;
        $this->iMatchupID = $a_iMatchupID;
        $this->iBookieID = $a_iBookieID;
        $this->sDate = $a_sDate;
        $this->aSpreadOdds = array();
    }

    public function getID()
    {
        return $this->iID;
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

    public function setSpreadOddsCol($a_aSpreadOdds)
    {
        $this->aSpreadOdds = $a_aSpreadOdds;
        $this->sortSpreadOdds();
    }

    public function getSpreadOddsCol()
    {
        return $this->aSpreadOdds;
    }

    public function addSpreadOdds($a_oSpreadOdds)
    {
        $this->aSpreadOdds[] = $a_oSpreadOdds;
        $this->sortSpreadOdds();
    }

    private function sortSpreadOdds()
    {
        for ($i = count($this->aSpreadOdds) - 1; $i >= 0; $i--)
        {
            $swapped = false;
            for ($j = 0; $j < $i; $j++)
            {
                if ($this->aSpreadOdds[$j]->getSpread(1) > $this->aSpreadOdds[$j + 1]->getSpread(1))
                {
                    $oTmp = $this->aSpreadOdds[$j];
                    $this->aSpreadOdds[$j] = $this->aSpreadOdds[$j + 1];
                    $this->aSpreadOdds[$j + 1] = $oTmp;
                    $swapped = true;
                }
            }
            if (!$swapped) return;
        }
    }

    public function equals($a_oSpreadOddsSet)
    {
        foreach ($this->aSpreadOdds as $oSpreadOdds)
        {
            $bFound = false;
            foreach ($a_oSpreadOddsSet->getSpreadOddsCol() as $oCompareOdds)
            {
                if ($oSpreadOdds->equals($oCompareOdds))
                {
                    $bFound = true;
                }
            }
            if (!$bFound)
            {
                return false;
            }
        }

        return ($this->iBookieID == $a_oSpreadOddsSet->getBookieID() &&
                $this->iMatchupID == $a_oSpreadOddsSet->getMatchupID());
    }

}

?>
