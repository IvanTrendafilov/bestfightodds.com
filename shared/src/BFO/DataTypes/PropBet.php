<?php

namespace BFO\DataTypes;

class PropBet
{
    private int $matchup_id; //Matchup the prop is linked to
    private int $team_num; //Used if prop specifies a certain team in the matchup
    private int $bookie_id;
    private int $proptype_id;

    private string $prop_name;
    private int $prop_odds;
    private string $negprop_name;
    private int $negprop_odds;
    
    private string $date;

    public function __construct(int $matchup_id, int $bookie_id, string $prop_name, int $prop_odds, string $negprop_name, int $negprop_odds, int $proptype_id, string $date, int $team_num = 0)
    {
        $this->matchup_id = $matchup_id;
        $this->bookie_id = $bookie_id;
        $this->prop_name = $prop_name;
        $this->prop_odds = $prop_odds;
        $this->negprop_name = $negprop_name;
        $this->negprop_odds = $negprop_odds;
        $this->proptype_id = $proptype_id;
        $this->team_num = $team_num;
        $this->date = $date;
    }

    public function getMatchupID(): int
    {
        return $this->matchup_id;
    }

    public function setMatchupID($matchup_id): void
    {
        $this->matchup_id = $matchup_id;
    }

    public function getBookieID(): int
    {
        return $this->bookie_id;
    }

    public function setBookieID($bookie_id): void
    {
        $this->bookie_id = $bookie_id;
    }

    /*
    @deprecated Use getOdds(teamNum) instead
    */
    public function getPropOdds(): int
    {
        return $this->prop_odds;
    }

    public function setPropOdds(int $prop_odds): void
    {
        $this->prop_odds = $prop_odds;
    }

    public function getNegPropName(): string
    {
        return $this->negprop_name;
    }

    public function setNegPropName($negprop_name): void
    {
        $this->negprop_name = $negprop_name;
    }

    /*
    @deprecated Use getOdds(teamNum) instead
    */
    public function getNegPropOdds(): int
    {
        return $this->negprop_odds;
    }

    public function setNegPropOdds(int $negprop_odds): void
    {
        $this->negprop_odds = $negprop_odds;
    }

    public function getPropTypeID(): int
    {
        return $this->proptype_id;
    }

    public function setPropTypeID(int $proptype_id): void
    {
        $this->proptype_id = $proptype_id;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate($date): void
    {
        $this->date = $date;
    }

    public function getPropName(): string
    {
        return $this->prop_name;
    }

    public function setPropName(string $prop_name): void
    {
        $this->prop_name = $prop_name;
    }

    private function getOddsAsString(int $odds): string
    {
        if ($odds == 0) {
            return 'error';
        }
        if ($odds > 0) {
            return '+' . $odds;
        }
        return $odds;
    }

    public function getPropOddsAsString(): string
    {
        return $this->getOddsAsString($this->prop_odds);
    }

    public function getNegPropOddsAsString(): string
    {
        return $this->getOddsAsString($this->negprop_odds);
    }

    public function getTeamNumber(): int
    {
        return $this->team_num;
    }

    public function setTeamNumber(int $team_num): void
    {
        $this->team_num = $team_num;
    }

    public function equals(Object $prop_bet): bool
    {
        return ($this->matchup_id == $prop_bet->getMatchupID() &&
            $this->bookie_id == $prop_bet->getBookieID() &&
            $this->prop_odds == $prop_bet->getPropOdds() &&
            $this->negprop_odds == $prop_bet->getNegPropOdds() &&
            $this->proptype_id == $prop_bet->getPropTypeID());
    }

    public function getOdds(int $team_num): ?int
    {
        if ($team_num == '1') {
            return $this->prop_odds;
        } 
        if ($team_num == '2') {
            return $this->negprop_odds;
        }
        return null;
    }

    public static function moneylineToDecimal(int $moneyline, bool $no_rounding = false): float
    {
        if ($moneyline == 100) {
            return 2.0;
        }
        
        if ($moneyline > 0) {
            if ($no_rounding == true) {
                return round((($moneyline / 100) + 1) * 100000) / 100000;
            } else {
                return round((($moneyline / 100) + 1) * 100) / 100;
            }
        } else {
            $moneyline = substr($moneyline, 1);
            if ($no_rounding == true) {
                return round(((100 / $moneyline) + 1) * 100000) / 100000;
            } else {
                return round(((100 / $moneyline) + 1) * 100) / 100;
            }
        }
    }

    public static function decimalToMoneyline($decimal_val)
    {
        $decimal_val--;
        if ($decimal_val < 1) {
            return '-' . round((1 / $decimal_val) * 100);
        }
        //(a_iOdds >= 1)
        return round($decimal_val * 100);
    }
}
