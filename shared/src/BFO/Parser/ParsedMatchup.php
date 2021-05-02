<?php

namespace BFO\Parser;

use BFO\Parser\ParsedMoneyline;
use BFO\Parser\Utils\ParseTools;

class ParsedMatchup
{
    private $sTeam1Name;
    private $sTeam2Name;
    private $oMoneyline;
    private $sDate;
    private $bSwitched = false; //Indicates if team order has been changed or not
    private $bSwitchedFromOutside = false;
    private $sCorrelationID = '';
    private $aMetaData;

    public function __construct($a_sTeam1, $a_sTeam2, $a_sTeam1Odds, $a_sTeam2Odds, $a_sDate = '')
    {
        //Make sure that teams are in lexigraphical order
        if (trim($a_sTeam1) > trim($a_sTeam2)) {
            $this->sTeam1Name = ParseTools::formatName($a_sTeam2);
            $this->sTeam2Name = ParseTools::formatName($a_sTeam1);
            $this->oMoneyline = new ParsedMoneyline(trim($a_sTeam2Odds), trim($a_sTeam1Odds));
            $this->bSwitched = true;
        } else {
            $this->sTeam1Name = ParseTools::formatName($a_sTeam1);
            $this->sTeam2Name = ParseTools::formatName($a_sTeam2);
            $this->oMoneyline = new ParsedMoneyline(trim($a_sTeam1Odds), trim($a_sTeam2Odds));
        }

        $this->bSwitchedFromOutside = false;
        $this->aMetaData = array();
        $this->sDate = ($a_sDate != '' ? $this->sDate = ParseTools::standardizeDate(trim($a_sDate)) : '');
    }

    public function getTeamName($a_iTeam)
    {
        switch ($a_iTeam) {
            case 1: return $this->sTeam1Name;
                break;
            case 2: return $this->sTeam2Name;
                break;
            default:
                return null;
                break;
        }
    }

    public function getDate()
    {
        return $this->sDate;
    }

    /**
     * Get moneyline value for a team
     *
     * @param <type> $a_iTeam
     * @return <type>
     *
     * @deprecated Use getMoneyLine() instead
     */
    public function getTeamOdds($a_iTeam)
    {
        return $this->getMoneyLine($a_iTeam);
    }

    /**
     * Get moneyline value for matchup
     *
     * @param int $a_iTeam Team number (1 or 2)
     * @return string Moneyline value
     */
    public function getMoneyline($a_iTeam)
    {
        $oML = $this->getMoneyLineObj();

        if ($oML == null) {
            return false;
        }

        return $oML->getMoneyline($a_iTeam);
    }

    public function switchOdds()
    {
        $this->bSwitchedFromOutside = true;
        //Switch moneyline
        if ($this->oMoneyline != null && !$this->oMoneyline->isSwitched()) {
            $this->oMoneyline->switchOdds();
        }
        return true;
    }

    public function isSwitchedFromOutside()
    {
        return $this->bSwitchedFromOutside;
    }

    public function addMoneyLineObj($a_oMoneyline)
    {
        $this->oMoneyline = $a_oMoneyline;
        if ($this->bSwitched == true && !$this->oMoneyline->isSwitched()) {
            $this->oMoneyline->switchOdds();
        }
    }

    public function getMoneyLineObj()
    {
        return $this->oMoneyline;
    }

    public function hasMoneyline()
    {
        return ($this->oMoneyline->getMoneyline(1) != '' && $this->oMoneyline->getMoneyline(1) != '');
    }

    public function toString()
    {
        return $this->getTeamName(1) . ' vs ' . $this->getTeamName(2);
    }

    /**
     * Sets a correlation ID
     *
     * The correlation ID can be used to store an identifier that is used to
     * match the matchup with other parsed content such as props
     *
     * @param String $a_sCorrID Correlation ID
     */
    public function setCorrelationID($a_sCorrID)
    {
        //Correlation ID is always converted to uppercase to avoid case-insensitivity problems
        $this->sCorrelationID = strtoupper(trim($a_sCorrID));
    }

    /**
     * Gets the associated correlation ID (if applicable)
     *
     * @return String Correlation ID
     */
    public function getCorrelationID()
    {
        return $this->sCorrelationID;
    }

    public function setMetaData($a_sAttribute, $a_sValue)
    {
        $this->aMetaData[$a_sAttribute] = $a_sValue;
    }

    public function getAllMetaData()
    {
        return $this->aMetaData;
    }
}
