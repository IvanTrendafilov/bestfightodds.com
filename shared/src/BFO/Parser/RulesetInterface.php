<?php

namespace BFO\Parser;

/**
 * This interface is used as a base for site specific rulesets that MatchupCreator uses to determine if an event or matchup can be created
 */
interface RulesetInterface
{
    public function __construct();
    public function evaluateMatchup($bookie_obj, $team1, $team2, $event_name, $gametime): bool;
    public function evaluateEvent($bookie_obj, $event_name, $gametime): bool;
}
