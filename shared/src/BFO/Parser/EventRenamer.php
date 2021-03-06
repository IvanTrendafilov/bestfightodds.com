<?php

namespace BFO\Parser;

use BFO\General\EventHandler;

/**
 * Used to check the current stored sportsbook metadata for a matchup and determine the most suitable event name based on input from sportsbooks
 */
class EventRenamer
{
    public function __construct()
    {
    }

    public function evaluteRenamings(): array
    {
        $recommendations = [];

        $events = EventHandler::getEvents(future_events_only: true);
        foreach ($events as $event) {
            $recommendation = ['event' => $event, 'change' => false];
            $names_to_evaluate = [];
            $matchups = EventHandler::getMatchups(event_id: $event->getID(), only_with_odds: true);
            if ($event->getID() != PARSE_FUTURESEVENT_ID && count($matchups) != 0) {
                foreach ($matchups as $matchup) {

                    $event_names = EventHandler::getMetaDataForMatchup($matchup->getID(), 'event_name');
                    foreach ($event_names as $event_name) {
                        //Strip anything past -
                        $formatted_event_name = explode(' - ', $event_name['mvalue'])[0];
                        if (
                            !str_starts_with(strtoupper($formatted_event_name), 'FUTURE') // Ignore events starting with Future
                            && preg_match('/\d{2,4}\-\d{2}\-\d{2}/', $formatted_event_name) == 0
                        ) { // - Ignore events with dates in them (e.g. UFC 2012-12-31)
                            $names_to_evaluate[] = $formatted_event_name;
                        }
                    }
                }

                $common_word = $this->getLongestCommonString($names_to_evaluate);
                
                //If there is consensus, check for any of the available event names that contain a numbered variant.
                $best_choice_numbered = '';
                $best_choice_themed = '';
                foreach ($names_to_evaluate as $names) {

                    $names_stripped = explode(':', $names)[0];
                    $common_stripped = explode(':', $common_word)[0];
                    if (
                        preg_match('/' . $common_word . '[^\d]*\s\d+/', $names_stripped) == 1
                        || preg_match('/[^\d]+\s\d+/', $common_stripped) == 1
                    ) {
                        if (preg_match('/[^\:]+\:.+vs\.?.+/', $names)) {
                            $best_choice_themed = $names;
                        } else {
                            $best_choice_numbered = $names;
                        }
                    }
                }
                //If there is also a named event afterwards (e.g. UFC 266: Jones vs. Silva we honor that)
                $best_choice_themed = str_replace('vs ', 'vs. ', $best_choice_themed);

                if ($best_choice_themed != '' && $best_choice_themed != $event->getName()) {
                    $recommendation['change'] = true;
                    $recommendation['new_name'] = $best_choice_themed;
                } else if ($best_choice_numbered != '' && $best_choice_numbered != $event->getName() && !preg_match('/[^\:]+\:.+vs\.?.+/', $event->getName())) {
                    $recommendation['change'] = true;
                    $recommendation['new_name'] = $best_choice_numbered;
                }
            }
            $recommendations[] = $recommendation;
        }

        return $recommendations;
    }

    public function getLongestCommonString($words)
    {
        $words = array_map('trim', $words);
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
