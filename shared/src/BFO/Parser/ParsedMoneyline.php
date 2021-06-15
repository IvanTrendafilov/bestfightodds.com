<?php

namespace BFO\Parser;

/**
 * ParsdeMoneyLine Class - Represents a parsed moneyline from a sportsbook. Typically associated with a ParsedMatchup or ParsedProp
 */
class ParsedMoneyline
{
    private $team1_moneyline;
    private $team2_moneyline;
    private $order_switched;

    public function __construct(string $in_team1_moneyline, string $in_team2_moneyline)
    {
        $this->order_switched = false;

        $this->team1_moneyline = $in_team1_moneyline;
        $this->team2_moneyline = $in_team2_moneyline;

        if (strtoupper($this->team1_moneyline) == 'EV' || strtoupper($this->team1_moneyline) == 'EVEN') {
            $this->team1_moneyline = '100';
        }

        if (strtoupper($this->team2_moneyline) == 'EV' || strtoupper($this->team2_moneyline) == 'EVEN') {
            $this->team2_moneyline = '100';
        }
    }

    public function getMoneyline(int $team_no): string
    {
        if ($team_no == 1) {
            return $this->team1_moneyline;
        }
        if ($team_no == 2) {
            return $this->team2_moneyline;
        }
        return null;
    }

    public function isSwitched(): bool
    {
        return $this->order_switched;
    }

    public function switchOdds(): void
    {
        $temp_moneyline = $this->team1_moneyline;
        $this->team1_moneyline = $this->team2_moneyline;
        $this->team2_moneyline = $temp_moneyline;
        $this->order_switched = true;
    }
}
