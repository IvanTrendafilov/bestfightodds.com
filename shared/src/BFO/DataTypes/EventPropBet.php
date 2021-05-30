<?php

namespace BFO\DataTypes;

use BFO\DataTypes\PropBet;

class EventPropBet extends PropBet
{
    private $event_id; //Event the prop is linked to

    public function __construct($event_id, $bookie_id, $prop_name, $prop_odds, $negprop_name, $neg_prop_odds, $proptype_id, $date)
    {
        parent::__construct(-1, $bookie_id, $prop_name, $prop_odds, $negprop_name, $neg_prop_odds, $proptype_id, $date, 0);
        $this->setEventID($event_id);
    }

    public function setEventID($event_id)
    {
        $this->event_id = $event_id;
    }

    public function getEventID()
    {
        return $this->event_id;
    }

    public function equals($prop_bet)
    {
        return ($this->event_id == $prop_bet->getEventID() &&
            $this->getBookieID() == $prop_bet->getBookieID() &&
            $this->getPropOdds() == $prop_bet->getPropOdds() &&
            $this->getNegPropOdds() == $prop_bet->getNegPropOdds() &&
            $this->getPropTypeID() == $prop_bet->getPropTypeID());
    }
}
