<?php

namespace BFO\Parser;

use BFO\General\BookieHandler;
use BFO\General\ScheduleHandler;
use BFO\General\EventHandler;
use BFO\Utils\OddsTools;

use BFO\DataTypes\Fight;
use BFO\DataTypes\Event;

//Create matchup: If bookie has odds for other matchups on this event (and event name and date matches)
//Create event and matchups: If matching whitelisting criteria (site specific). E.g. BFO, BetOnline and OKTAGON
//Create event if: Bookie matches a specific name like BetOnline. Maybe also check if there are multiple matchups provided?
//Create matchup: If this one is in schedule as Create?
//Create matchup: If multiple bookies has this one provided?
//Do not create: IF another matchup that is already matched has the same event name metadata but matched to different event

class EventRenamer
{
    private $logger = null;
    private $bookie_obj = null;
    private $ruleset = null;
    private $upcoming_matchups = null;
    private $audit_log = null;
    private $manual_actions_create_events = null;
    private $manual_actions_create_matchups = null;
    private $creation_ruleset = null;

    public function __construct(object $logger)
    {
        $this->logger = $logger;

        $this->audit_log = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::INFO, ['filename' => 'changeaudit.log']);

        // //Prefetch manual actions that we will check against later on
        // $this->manual_actions_create_events = ScheduleHandler::getAllManualActions(1);
        // $this->manual_actions_create_matchups = ScheduleHandler::getAllManualActions(5);
        // if ($this->manual_actions_create_events != null) {
        //     foreach ($this->manual_actions_create_events as &$action) {
        //         $action['action_obj'] = json_decode($action['description']);
        //     }
        // }
        // if ($this->manual_actions_create_matchups != null) {
        //     foreach ($this->manual_actions_create_matchups as &$action) {
        //         $action['action_obj'] = json_decode($action['description']);
        //     }
        // }
    }

    public function evaluteRenamings() 
    {
        //Fetch all upcoming matchups and their metadata event_name
        $matchups = EventHandler::getAllUpcomingMatchups(true);
        


        //Determine if there is a common pattern to the matchups for a specific event
        //What is the most common denominator

        //Ruleset: (should this be external or not, ie specific to the site?)
        //Event is named
        //Event is numbered
        //Event is :<x></x> vs  y




    }

}
