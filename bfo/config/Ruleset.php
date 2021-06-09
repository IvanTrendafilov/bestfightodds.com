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
    public function __construct()
    {
    }

    public function evaluateMatchup($bookie_obj, $team1, $team2, $event_name, $gametime): bool
    {
        $event_name = strtoupper($event_name);
        $event_pieces = explode(' ', $event_name);

        if ($bookie_obj->getName() == 'BetOnline') {
            $whitelisted_events = ['OKTAGON', 'LFA', 'CES', 'PFL', 'UFC', 'BELLATOR', 'FAC', 'AMC', 'TITAN', 'FAME', 'INVICTA', 'EFC', 'ACA', 'UWC'];
            if (in_array($event_pieces[0], $whitelisted_events) || $event_name == 'FUTURE EVENTS') {
                return true;
            }
        }

        if ($bookie_obj->getName() == 'BetWay') {
            $whitelisted_events = ['EFC', 'SUPERIOR', 'ACA', 'FEN', 'OKTAGON', 'BRAVE'];
            if (in_array($event_pieces[0], $whitelisted_events)) {
                return true;
            }
        }

        return false;
    }

    public function evaluateEvent($bookie_obj, $event_name, $gametime): bool
    {
        $event_name = strtoupper($event_name);
        $event_pieces = explode(' ', $event_name);

        if ($bookie_obj->getName() == 'BetOnline') {
            $whitelisted_events = ['OKTAGON', 'LFA', 'CES', 'PFL', 'UFC', 'BELLATOR', 'FAC', 'AMC', 'TITAN', 'FAME', 'INVICTA', 'EFC', 'ACA', 'UWC'];
            if (in_array($event_pieces[0], $whitelisted_events)) {
                //Check that event is numbered (= event name contains a number)
                if (preg_match('/\\d/', $event_name) > 0) {
                    return true;
                }
            }
        }

        if ($bookie_obj->getName() == 'BetWay') {
            $whitelisted_events = ['EFC', 'SUPERIOR', 'ACA', 'FEN', 'OKTAGON', 'BRAVE'];
            if (in_array($event_pieces[0], $whitelisted_events)) {
                return true;
            }
        }

        return false;
    }
}
