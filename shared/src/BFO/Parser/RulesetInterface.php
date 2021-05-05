<?php

namespace BFO\Parser;

interface RulesetInterface
{
    public function __construct();
    public function evaluateMatchup($bookie_obj, $team1, $team2, $event_name, $gametime);
    public function evaluateEvent($bookie_obj, $event_name, $gametime);
}
