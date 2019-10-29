<?php

/**
 * Alert Class - Represents an alert created in the system
 */
class AlertV2
{
    private $id;
    private $email;
    private $oddstype; //1 = Moneyline, 2 = Decimal, 3 = Return on.., 4 = Fraction
    private $criterias; //JSON string containing an array of criterias to fulfill

    public function __construct($id, $email, $oddstype, $criterias)
    {
        $this->id = (int) $id;
        $this->email = (string) $email;
        $this->oddstype = (int) $oddstype;
        $this->criterias = (string) $criterias;        
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

?>