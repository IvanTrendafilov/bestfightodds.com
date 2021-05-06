<?php

namespace BFO\Parser;

use BFO\Parser\ParsedMoneyline;
use BFO\Parser\Utils\ParseTools;

class ParsedMatchup
{
    private $team1_name;
    private $team2_name;
    private $moneyline;
    private $date;
    private $switched = false; //Indicates if team order has been changed or not
    private $switched_externally = false;
    private $correlation_id = '';
    private $metadata;

    public function __construct($team1, $team2, $team1_odds, $team2_odds, $date = '')
    {
        //Make sure that teams are in lexigraphical order
        if (trim($team1) > trim($team2)) {
            $this->team1_name = ParseTools::formatName($team2);
            $this->team2_name = ParseTools::formatName($team1);
            $this->moneyline = new ParsedMoneyline(trim($team2_odds), trim($team1_odds));
            $this->switched = true;
        } else {
            $this->team1_name = ParseTools::formatName($team1);
            $this->team2_name = ParseTools::formatName($team2);
            $this->moneyline = new ParsedMoneyline(trim($team1_odds), trim($team2_odds));
        }

        $this->switched_externally = false;
        $this->metadata = array();
        $this->date = ($date != '' ? $this->date = ParseTools::standardizeDate(trim($date)) : '');
    }

    public function getTeamName($team_number)
    {
        switch ($team_number) {
            case 1: return $this->team1_name;
                break;
            case 2: return $this->team2_name;
                break;
            default:
                return null;
                break;
        }
    }

    public function getDate()
    {
        return $this->date;
    }

    /**
     * Get moneyline value for a team
     *
     * @param <type> $a_iTeam
     * @return <type>
     *
     * @deprecated Use getMoneyLine() instead
     */
    public function getTeamOdds($team_number)
    {
        return $this->getMoneyLine($team_number);
    }

    /**
     * Get moneyline value for matchup
     *
     * @param int $a_iTeam Team number (1 or 2)
     * @return string Moneyline value
     */
    public function getMoneyline($team_number)
    {
        $moneyline = $this->getMoneyLineObj();

        if ($moneyline == null) {
            return false;
        }

        return $moneyline->getMoneyline($team_number);
    }

    public function switchOdds()
    {
        $this->switched_externally = true;
        //Switch moneyline
        if ($this->moneyline != null && !$this->moneyline->isSwitched()) {
            $this->moneyline->switchOdds();
        }
        return true;
    }

    public function isSwitchedFromOutside()
    {
        return $this->switched_externally;
    }

    public function addMoneyLineObj($moneyline)
    {
        $this->moneyline = $moneyline;
        if ($this->switched == true && !$this->moneyline->isSwitched()) {
            $this->moneyline->switchOdds();
        }
    }

    public function getMoneyLineObj()
    {
        return $this->moneyline;
    }

    public function hasMoneyline()
    {
        return ($this->moneyline->getMoneyline(1) != '' && $this->moneyline->getMoneyline(1) != '');
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
    public function setCorrelationID($correlation_id)
    {
        //Correlation ID is always converted to uppercase to avoid case-insensitivity problems
        $this->correlation_id = strtoupper(trim($correlation_id));
    }

    /**
     * Gets the associated correlation ID (if applicable)
     *
     * @return String Correlation ID
     */
    public function getCorrelationID()
    {
        return $this->correlation_id;
    }

    public function setMetaData($attribute, $value)
    {
        $this->metadata[$attribute] = $value;
    }

    public function getAllMetaData()
    {
        return $this->metadata;
    }
}