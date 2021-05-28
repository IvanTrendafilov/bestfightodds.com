<?php

namespace BFO\Parser;

use BFO\General\BookieHandler;
use BFO\General\ScheduleHandler;
use BFO\General\EventHandler;
use BFO\Utils\OddsTools;

use BFO\DataTypes\Fight;
use BFO\DataTypes\Event;


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


        $events = EventHandler::getEvents(future_events_only: true);
        foreach ($events as $event) {
            $names_to_evaluate = [];
            $matchups = EventHandler::getMatchups(event_id: $event->getID(), only_with_odds: true);
            foreach ($matchups as $matchup) {

                $event_names = EventHandler::getMetaDataForMatchup($matchup->getID(), 'event_name');
                foreach ($event_names as $event_name) {
                    //Strip anything past -
                    $formatted_event_name = explode(' - ', $event_name['mvalue'])[0];

                    if (
                        !str_starts_with(strtoupper($formatted_event_name), 'FUTURE') // Ignore events starting with Future
                        && preg_match('/\d{2,4}\-\d{2}\-\d{2}/', $formatted_event_name) == 0) { // - Ignore events with dates in them (e.g. UFC 2012-12-31)
                        $names_to_evaluate[] = $formatted_event_name;
                    }
                }
            }

            

            foreach ($names_to_evaluate as $name) {
                echo $name . "
";
            }

            $common_word = $this->getLongestCommonString($names_to_evaluate);
            echo 'Common: ' . $common_word;


            echo "
";


            //If there is consensus, check for any of the available event names that contain a numbered variant.
            foreach ($names_to_evaluate as $names) {
                if (
                    preg_match('/' . $common_word . '[^\d]*\s\d+/', $names) == 1
                    || preg_match('/[^\d]+\s\d+/', $common_word) == 1) {
                    echo " Numbered candidate: " . $names . "
";
                    if (preg_match('/[^\:]+\:.+/', $names)) {
                        echo "  Themed candidate: " . $names . "
";                      
                    }

                    //Maybe match on both themed and number candidate and then decide which one is more relevant?
                    //Eg. UFC Vegas 26 vs UFC Fight Night: Ike vs. Zombie .. in this case prefer maybe the named one?
                    //I.e:
                    //>UFC Fight Night 13: Bla vs. Bla < UFC Fight Night: Ike vs. Zombie < UFC Vegas 26 
                    // Can also check for consus before committing to one of them.. Would not work in the UFC Vegas 26 case though...
                }
            }
            //If there is also a named event afterwards (e.g. UFC 266: Bla vs. Bla we honor that)



        }



        //Determine if there is a common pattern to the matchups for a specific event
        //What is the most common denominator

        //Ruleset: (should this be external or not, ie specific to the site?)
        //Event is named
        //Event is numbered
        //Event is :<x></x> vs  y




    }

    public function getLongestCommonString($words)
    {
        $words =array_map('trim', $words);
        $sort_by_strlen = function ($a, $b) {
            if (strlen($a) == strlen($b)) {
                return strcmp($a, $b);
            }
            return (strlen($a) < strlen($b)) ? -1 : 1;
        };
        usort($words, $sort_by_strlen);
        // We have to assume that each string has something in common with the first
        // string (post sort), we just need to figure out what the longest common
        // string is. If any string DOES NOT have something in common with the first
        // string, return false.
        $longest_common_substring = array();
        $shortest_string = str_split(array_shift($words));
        while (sizeof($shortest_string)) {
            array_unshift($longest_common_substring, '');
            foreach ($shortest_string as $ci => $char) {
                foreach ($words as $wi => $word) {
                    if (!strstr($word, $longest_common_substring[0] . $char)) {
                        // No match
                        break 2;
                    } // if
                } // foreach
                // we found the current char in each word, so add it to the first longest_common_substring element,
                // then start checking again using the next char as well
                $longest_common_substring[0] .= $char;
            } // foreach
            // We've finished looping through the entire shortest_string.
            // Remove the first char and start all over. Do this until there are no more
            // chars to search on.
            array_shift($shortest_string);
        }
        // If we made it here then we've run through everything
        usort($longest_common_substring, $sort_by_strlen);
        return array_pop($longest_common_substring);
    }
}
