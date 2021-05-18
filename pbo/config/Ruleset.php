<?php

use BFO\Parser\RulesetInterface;

class Ruleset implements RulesetInterface
{
    public function __construct()
    {
    }

    public function evaluateMatchup($bookie_obj, $team1, $team2, $event_name, $gametime)
    {
        $event_name = strtoupper($event_name);
        $event_pieces = explode(' ', $event_name);

        return true; //All bookies can create matchups
    }

    public function evaluateEvent($bookie_obj, $event_name, $gametime)
    {
        $event_name = strtoupper($event_name);
        $event_pieces = explode(' ', $event_name);

        return true; //All bookies can create events (generic date events)
    }
}
