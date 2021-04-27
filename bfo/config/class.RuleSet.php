<?php
class Ruleset
{
    public function __construct()
    {
    }

    public function evaluateMatchup($bookie_obj, $team1, $team2, $event_name, $gametime)
    {
        $event_name = strtoupper($event_name);
        $event_pieces = explode(' ', $event_name);

        //BetOnline checks
        if ($bookie_obj->getName() == 'BetOnline') {
            $whitelisted_events = ['OKTAGON', 'LFA', 'CES', 'PFL', 'UFC', 'BELLATOR'];
            if (in_array($event_pieces[0], $whitelisted_events) || $event_name == 'FUTURE EVENTS') {
                return true;
            }
        }

        return false;
    }
}