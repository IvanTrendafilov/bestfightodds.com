<?php

namespace BFO\General\AlerterV2;

/**
 * Alert Class - Represents an alert created in the system
 */
class AlertV2
{
     //1 = Moneyline, 2 = Decimal, 3 = Return on.., 4 = Fraction
    //JSON string containing an array of criterias to fulfill

    public function __construct(
        private int $id, 
        private string $email, 
        private int $oddstype,
        private string $criterias)
    {
    }

    public function getID()
    {
        return $this->id;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getOddsType()
    {
        return $this->oddstype;
    }

    public function getCriterias()
    {
        return json_decode($this->criterias, true);
    }
}
