<?php

class TotalOddsSet
{
    private $iID;
    private $iMatchupID;
    private $iBookieID;
    private $sDate;
    private $aTotalOdds;

    public function __construct($a_iID, $a_iMatchupID, $a_iBookieID, $a_sDate)
    {
        $this->iID = $a_iID;
        $this->iMatchupID = $a_iMatchupID;
        $this->iBookieID = $a_iBookieID;
        $this->sDate = $a_sDate;
        $this->aTotalOdds = array();
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

    public function setTotalOddsCol($a_aTotalOdds)
    {
        $this->aTotalOdds = $a_aTotalOdds;
        $this->sortTotalOdds();
    }

    public function getTotalOddsCol()
    {
        return $this->aTotalOdds;
    }

    public function addTotalOdds($a_oTotalOdds)
    {
        $this->aTotalOdds[] = $a_oTotalOdds;
        $this->sortTotalOdds();
    }

    private function sortTotalOdds()
    {
        for ($i = count($this->aTotalOdds) - 1; $i >= 0; $i--)
        {
            $swapped = false;
            for ($j = 0; $j < $i; $j++)
            {
                if ($this->aTotalOdds[$j]->getTotal(1) > $this->aTotalOdds[$j + 1]->getTotal(1))
                {
                    $oTmp = $this->aTotalOdds[$j];
                    $this->aTotalOdds[$j] = $this->aTotalOdds[$j + 1];
                    $this->aTotalOdds[$j + 1] = $oTmp;
                    $swapped = true;
                }
            }
            if (!$swapped) return;
        }
    }

    public function equals($a_oTotalOddsSet)
    {
        foreach ($this->aTotalOdds as $oTotalOdds)
        {
            $bFound = false;
            foreach ($a_oTotalOddsSet->getTotalOddsCol() as $oCompareOdds)
            {
                if ($oTotalOdds->equals($oCompareOdds))
                {
                    $bFound = true;
                }
            }
            if (!$bFound)
            {
                return false;
            }
        }

        return ($this->iBookieID == $a_oTotalOddsSet->getBookieID() &&
                $this->iMatchupID == $a_oTotalOddsSet->getMatchupID());
    }

}

?>
