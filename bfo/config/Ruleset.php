<?php

use BFO\Parser\RulesetInterface;

/**
 * This file contains the site specific ruleset that is used to determine if a matchup or event can be created automatically
 * 
 * When a parser is kicking off it calls OddsProcessor to process the odds. OddsProcessor will in turn call MatchupCreator 
 * when it cannot match odds to an existing matchup to check if the matchup can be created. MatchupCreator is generic and
 * will need to consult this site specific ruleset to check if a matchup can be created.
 */
class Ruleset implements RulesetInterface
{
    public function evaluateMatchup($bookie_obj, $team1, $team2, $event_name, $gametime): bool
    {
        $event_name = strtoupper($event_name);
        $event_pieces = explode(' ', $event_name);

        //Ignore Grappling/K1 events
        $blacklisted_events = ['GLORY', 'BKFC', 'WNO'];
        if (in_array($event_pieces[0], $blacklisted_events)) {
            return false;
        }

        return true; //Default to create any reported matchup
    }

    public function evaluateEvent($bookie_obj, $event_name, $gametime): bool
    {
        $event_name = strtoupper($event_name);
        $event_pieces = explode(' ', $event_name);

        //Ignore Grappling/K1 events
        $blacklisted_events = ['GLORY', 'BKFC', 'WNO'];
        if (in_array($event_pieces[0], $blacklisted_events)) {
            return false;
        }

        return true;
    }
}
