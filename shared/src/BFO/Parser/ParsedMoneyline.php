<?php

namespace BFO\Parser;

/**
 * Description of ParsedMoneyline
 *
 * @author Christian
 */
class ParsedMoneyline
{
    private $iTeam1Moneyline;
    private $iTeam2Moneyline;
    private $bSwitched;

    public function __construct($a_iTeam1Moneyline, $a_iTeam2Moneyline)
    {
        $this->bSwitched = false;

        $this->iTeam1Moneyline = $a_iTeam1Moneyline;
        $this->iTeam2Moneyline = $a_iTeam2Moneyline;

        if (strtoupper($this->iTeam1Moneyline) == 'EV' || strtoupper($this->iTeam1Moneyline) == 'EVEN') {
            $this->iTeam1Moneyline = '100';
        }

        if (strtoupper($this->iTeam2Moneyline) == 'EV' || strtoupper($this->iTeam2Moneyline) == 'EVEN') {
            $this->iTeam2Moneyline = '100';
        }
    }

    public function getMoneyline($a_iTeamNo)
    {
        switch ($a_iTeamNo) {
            case 1: return $this->iTeam1Moneyline;
                break;
            case 2: return $this->iTeam2Moneyline;
                break;
            default:
                return null;
        }
    }

    public function isSwitched()
    {
        return $this->bSwitched;
    }

    public function switchOdds()
    {
        $sTempOdds = $this->iTeam1Moneyline;
        $this->iTeam1Moneyline = $this->iTeam2Moneyline;
        $this->iTeam2Moneyline = $sTempOdds;

        $this->bSwitched = true;
    }
}
