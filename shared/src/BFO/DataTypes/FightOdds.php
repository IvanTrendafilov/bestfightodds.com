<?php

namespace BFO\DataTypes;

class FightOdds
{
    private $matchup_id;
    private $bookie_id;
    private $team1_odds;
    private $team2_odds;
    private $date;

    public function __construct(?int $matchup_id, ?int $bookie_id, $team1_odds, $team2_odds, $date)
    {
        $this->matchup_id = $matchup_id;
        $this->bookie_id = $bookie_id;

        if (strtoupper($team1_odds) == 'EV' || strtoupper($team1_odds) == 'EVEN') {
            $this->team1_odds = '100';
        } else {
            $this->team1_odds = str_replace('+', '', $team1_odds);
        }

        if (strtoupper($team2_odds) == 'EV' || strtoupper($team2_odds) == 'EVEN') {
            $this->team2_odds = '100';
        } else {
            $this->team2_odds = str_replace('+', '', $team2_odds);
        }

        $this->date = $date;
    }

    public function getFightID()
    {
        return $this->matchup_id;
    }

    public function getBookieID()
    {
        return $this->bookie_id;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getFighterOddsAsDecimal(int $team_num, bool $no_rounding = false): float // No rounding is actually not really no rounding but rather up to 5 decimals. Should be enough
    {
        $moneyline_odds = $this->getOdds($team_num);
        $decimal_odds = 0;
        if ($moneyline_odds == 100) {
            return 2.0;
        } elseif ($moneyline_odds > 0) {
            if ($no_rounding == true) {
                $decimal_odds = round((($moneyline_odds / 100) + 1) * 100000) / 100000;
            } else {
                $decimal_odds = round((($moneyline_odds / 100) + 1) * 100) / 100;
            }
        } else {
            $moneyline_odds = substr($moneyline_odds, 1);
            if ($no_rounding == true) {
                $decimal_odds = round(((100 / $moneyline_odds) + 1) * 100000) / 100000;
            } else {
                $decimal_odds = round(((100 / $moneyline_odds) + 1) * 100) / 100;
            }
        }
        return $decimal_odds;
    }

    public function getFighterOddsAsString(int $team_num): string
    {
        $odds = $this->getOdds($team_num);
        if ($odds == 0) {
            return 'error';
        } elseif ($odds > 0) {
            return '+' . $odds;
        } else {
            return $odds;
        }
    }

    public function equals($other_obj)
    {
        return ($this->matchup_id == $other_obj->getFightID() &&
            $this->bookie_id == $other_obj->getBookieID() &&
            $this->team1_odds == $other_obj->getOdds(1) &&
            $this->team2_odds == $other_obj->getOdds(2));
    }


    public function getOdds($team_num)
    {
        if ($team_num == 1) {
            return $this->team1_odds;
        }
        if ($team_num == 2) {
            return $this->team2_odds;
        }
        return 0;
    }

    public static function convertOddsEUToUS($decimal_odds)
    {
        $decimal_odds--;
        if ($decimal_odds < 1) {
            return '-' . round((1 / $decimal_odds) * 100);
        } else { //(decimal_odds >= 1)
            return round($decimal_odds * 100);
        }
    }

    public static function moneylineToDecimal($moneyline, bool $no_rounding = false): float
    {
        $decimal_odds = 0;
        if ($moneyline == 100) {
            return 2.0;
        } elseif ($moneyline > 0) {
            if ($no_rounding == true) {
                $decimal_odds = round((($moneyline / 100) + 1) * 100000) / 100000;
            } else {
                $decimal_odds = round((($moneyline / 100) + 1) * 100) / 100;
            }
        } else {
            $moneyline = substr($moneyline, 1);
            if ($no_rounding == true) {
                $decimal_odds = round(((100 / $moneyline) + 1) * 100000) / 100000;
            } else {
                $decimal_odds = round(((100 / $moneyline) + 1) * 100) / 100;
            }
        }
        return $decimal_odds;
    }

    public static function decimalToMoneyline($decimal_val)
    {
        $decimal_val--;
        if ($decimal_val < 1) {
            return '-' . round((1 / $decimal_val) * 100);
        } else { //(decimal_val >= 1)
            return round($decimal_val * 100);
        }
    }
}
