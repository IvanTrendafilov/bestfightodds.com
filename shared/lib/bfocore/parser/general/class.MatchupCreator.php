<?php

require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('config/class.RuleSet.php');

class MatchupCreator 
{
    private $logger = null;
    private $bookie_obj = null;
    private $ruleset = null;

    public function __construct($logger, $bookie_id)
    {
        $this->logger = $logger;
        $this->bookie_obj = BookieHandler::getBookieByID($bookie_id);
        $this->ruleset = new RuleSet();
    }

    public function evaluateMatchup($team1, $team2, $event_name, $matchup_time) 
    {
        
        echo "Evaluating " . $team1 . " vs " . $team2 . " at " . $event_name . " on " . $matchup_time . "
";
        //Check if the site-specific creation ruleset approves that this match up is created
        $ruleset_blessing = $this->ruleset->evaluateMatchup($this->bookie_obj, $team1, $team2, $event_name, $matchup_time);
        
        if ($ruleset_blessing && isset($event_name, $matchup_time))
        {
            //Find matching event for this league (e.g. UFC, PFL, Bellator)
            $event_pieces = explode(' ', strtoupper($event_name));

            //Match on date for the matched events
            $matched_event = null;
            if (strtoupper(trim($event_name)) == 'FUTURE EVENTS') {
                $matched_event = EventHandler::getEvent(PARSE_FUTURESEVENT_ID);
            }
            else {
                $event_search = EventHandler::searchEvent($event_pieces[0], true);
                $date_obj = new DateTime();
                $date = $date_obj->setTimestamp($matchup_time);
                $date = $date_obj->format('Y-m-d');
                foreach ($event_search as $event) {
                    if ($event->getDate() == $date) {
                        $matched_event = $event;
                    }
                }
            }

            if ($matched_event != null) {
                echo "Will create at event " . $matched_event->getName() . "
";
            }

        }
        
        


        


        //Create matchup: If bookie has odds for other matchups on this event (and event name and date matches)

        //Create event and matchups: If matching whitelisting criteria (site specific). E.g. BFO, BetOnline and OKTAGON

        //Create event if: Bookie matches a specific name like BetOnline. Maybe also check if there are multiple matchups provided?

        //Create matchup: If this one is in schedule as Create?

        //Create matchup: If multiple bookies has this one provided?

        //Do not create: If flagged for deletion in scheduler and flagged by other bookies <-- dangerous since flags are removed..

        //Do not create: IF another matchup that is already matched has the same event name metadata but matched to different event
    }

}