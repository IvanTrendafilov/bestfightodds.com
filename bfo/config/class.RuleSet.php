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

        if ($bookie_obj->getName() == 'BetOnline') {
            $whitelisted_events = ['OKTAGON', 'LFA', 'CES', 'PFL', 'UFC', 'BELLATOR'];
            if (in_array($event_pieces[0], $whitelisted_events) || $event_name == 'FUTURE EVENTS') {
                return true;
            }
        }

        if ($bookie_obj->getName() == 'BetWay') {
            $whitelisted_events = ['EFC'];
            if (in_array($event_pieces[0], $whitelisted_events)) {
                return true;
            }
        }

        return false;
    }

    public function evaluateEvent($bookie_obj, $event_name, $gametime)
    {
        $event_name = strtoupper($event_name);
        $event_pieces = explode(' ', $event_name);

        if ($bookie_obj->getName() == 'BetOnline') {
            $whitelisted_events = ['OKTAGON', 'LFA', 'CES', 'PFL', 'UFC', 'BELLATOR'];
            if (in_array($event_pieces[0], $whitelisted_events)) {
                //Check that event is numbered (= event name contains a number)
                if (preg_match('/\\d/', $event_name) > 0) {
                    return true;
                }
            }
        }

        if ($bookie_obj->getName() == 'BetWay') {
            $whitelisted_events = ['EFC'];
            if (in_array($event_pieces[0], $whitelisted_events)) {
                return true;
            }
        }

        return false;
    }
}
