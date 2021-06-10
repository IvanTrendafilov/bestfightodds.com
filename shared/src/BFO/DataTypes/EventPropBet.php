<?php

namespace BFO\DataTypes;

use BFO\DataTypes\PropBet;

/**
 * Event Prop Bet Class - Represents a betting line linked to an event (e.g. Over 8½ matchups go the distance)
 */
class EventPropBet extends PropBet
{
    private $event_id; //Event the prop is linked to

    public function __construct(int $event_id, int $bookie_id, string $prop_name, int $prop_odds, string $negprop_name, int $neg_prop_odds, int $proptype_id, string $date)
    {
        parent::__construct(-1, $bookie_id, $prop_name, $prop_odds, $negprop_name, $neg_prop_odds, $proptype_id, $date, 0);
        $this->setEventID($event_id);
    }

    public function setEventID(int $event_id): void
    {
        $this->event_id = $event_id;
    }

    public function getEventID(): int
    {
        return $this->event_id;
    }

    public function equals(Object $prop_bet): bool
    {
        return ($this->event_id == $prop_bet->getEventID() &&
            $this->getBookieID() == $prop_bet->getBookieID() &&
            $this->getPropOdds() == $prop_bet->getPropOdds() &&
            $this->getNegPropOdds() == $prop_bet->getNegPropOdds() &&
            $this->getPropTypeID() == $prop_bet->getPropTypeID());
    }
}
