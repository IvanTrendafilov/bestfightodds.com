<?php

namespace BFO\General;

use BFO\DB\EventDB;
use BFO\DataTypes\FightOdds;
use BFO\DataTypes\Event;
use BFO\DataTypes\Fight;

class EventHandler
{
    public static function getEvent(int $event_id, bool $future_event_only = false): ?Event
    {
        if (!$event_id || $event_id == 0) {
            return null;
        }
        $events = EventDB::getEvents($future_event_only, $event_id);
        return $events[0] ?? null;
    }

    public static function getEvents(bool $future_events_only = null, int $event_id = null, string $event_name = null, string $event_date = null): array
    {
        return EventDB::getEvents($future_events_only, $event_id, $event_name, $event_date);
    }

    public static function getMatchup(int $matchup_id): ?Fight
    {
        if (!$matchup_id || $matchup_id == 0) {
            return null;
        }
        $matchups = EventHandler::getMatchups(matchup_id: $matchup_id);
        return $matchups[0] ?? null;
    }

    public static function getMatchups(bool $future_matchups_only = false, bool $only_with_odds = false, int $event_id = null, int $matchup_id = null, bool $only_without_odds = false, int $team_id = null, int $create_source = null): array
    {
        return EventDB::getMatchups($future_matchups_only, $only_with_odds, $event_id, $matchup_id, $only_without_odds, $team_id, $create_source);
    }

    public static function getMatchingMatchup(string $team1_name, string $team2_name, bool $future_only = false, bool $past_only = false, int $known_fighter_id = null, string $event_date = null, int $event_id = null): ?Fight
    {
        return EventDB::getMatchingMatchup($team1_name, $team2_name, $future_only, $past_only, $known_fighter_id, $event_date, $event_id);
    }

    public static function getMatchingEvent(string $event_name, string $event_date, bool $future_only = true): ?Event
    {
        $event_pieces = explode(' ', strtoupper($event_name));
        $event_search = EventHandler::searchEvent($event_pieces[0], $future_only);
        foreach ($event_search as $event) {
            if ($event->getDate() == $event_date) {
                return $event;
            }
        }
        return null;
    }

    public static function createMatchup(Fight $matchup_obj): ?int
    {
        if ($matchup_obj->getTeam(1) == '' || $matchup_obj->getTeam(2) == '') {
            return null;
        }

        //Check that event is ok
        if (count(EventDB::getEvents(event_id: $matchup_obj->getEventID())) != 1) {
            return null;
        }

        //Check if matchup isn't already added
        if (EventDB::getMatchingMatchup(team1_name: $matchup_obj->getTeam(1), team2_name: $matchup_obj->getTeam(2), event_id: $matchup_obj->getEventID(), future_only: true)) {
            return null;
        }

        //Check that both teams exist, if not, add them
        $team1_id = TeamHandler::getTeamIDByName($matchup_obj->getTeam(1));
        if (!$team1_id) {
            $team1_id = TeamHandler::createTeam($matchup_obj->getTeam(1));
        }
        $team2_id = TeamHandler::getTeamIDByName($matchup_obj->getTeam(2));
        if (!$team2_id) {
            $team2_id = TeamHandler::createTeam($matchup_obj->getTeam(2));
        }

        if (!$team1_id || !$team2_id) {
            return null;
        }

        $id = EventDB::createMatchup($team1_id, $team2_id, $matchup_obj->getEventID());
        if ($id) {
            //Add create audit trace
            EventDB::addCreateAudit($id, $matchup_obj->getCreateSource());
        }
        return $id;
    }

    public static function addCreateAudit(int $matchup_id, int $source): ?bool
    {
        return EventDB::addCreateAudit($matchup_id, $source);
    }

    public static function addNewEvent(Event $event): ?Event
    {
        if ($event->getName() == '' || $event->getDate() == '') {
            return null;
        }

        //Check that event doesn't already exists
        if (EventHandler::getEvents(event_name: $event->getName(), event_date: $event->getDate())) {
            return null;
        }

        //Validate date
        $dt = \DateTime::createFromFormat("Y-m-d", $event->getDate());
        if ($dt === false || array_sum($dt::getLastErrors()) > 0) {
            return null;
        }

        $id = EventDB::addNewEvent($event);
        if ($id != false && $id != null) {
            return EventHandler::getEvents(event_id: $id)[0] ?? null;
        }
        return null;
    }

    public static function removeMatchup(int $matchup_id): bool
    {
        return EventDB::removeMatchup($matchup_id);
    }

    public static function removeEvent(int $event_id): bool
    {
        if (!$event_id || $event_id < 1) {
            return false;
        }

        //First remove all matchups for this event
        $matchups = EventHandler::getMatchups(event_id: $event_id);
        foreach ($matchups as $matchup) {
            self::removeMatchup($matchup->getID());
        }
        return EventDB::removeEvent($event_id);
    }

    /**
     * Changes an event. If any field is left blank it will not be updated.
     */
    public static function changeEvent($event_id, $set_event_name = '', $set_event_date = '', $set_is_visible = null)
    {
        $event_obj = EventHandler::getEvents(event_id: $event_id)[0] ?? null;
        if (!$event_obj) {
            return false;
        }
        if ($set_event_name != '') {
            $event_obj->setName($set_event_name);
        }
        if ($set_event_date != '') {
            $event_obj->setDate($set_event_date);
        }
        if ($set_is_visible !== null) {
            $event_obj->setDisplay($set_is_visible);
        }

        return EventDB::updateEvent($event_obj);
    }

    public static function changeFight($matchup_id, $event_id)
    {
        $matchup_obj = EventHandler::getMatchup($matchup_id);

        if ($matchup_obj == null) {
            return false;
        }

        if ($event_id != '') {
            $matchup_obj->setEventID($event_id);
        }

        return EventDB::updateFight($matchup_obj);
    }

    public static function setFightAsMainEvent(int $fight_id, bool $set_as_main_event = true)
    {
        return EventDB::setFightAsMainEvent($fight_id, $set_as_main_event);
    }

    public static function searchEvent(string $event_name, bool $only_future_events = false)
    {
        return EventDB::searchEvent($event_name, $only_future_events);
    }

    public static function getRecentEvents(int $limit = 10, int $offset = 0)
    {
        if (!is_integer($limit) || $limit <= 0) {
            return null;
        }
        if (!is_integer((int) $offset) || (int) $offset < 0) {
            return null;
        }

        return EventDB::getRecentEvents($limit, $offset);
    }

    /**
     * Writes an entry to the log for unmatched entries from parsing
     *
     * Type: 0 = matchup, 1 = prop without matchup, 2 = prop without template
     */
    public static function logUnmatched(string $matchup, int $bookie_id, int $type, array $metadata_col = null): int
    {
        $metadata = serialize($metadata_col);
        return EventDB::logUnmatched($matchup, $bookie_id, $type, $metadata);
    }

    /**
     * Retrieves all stored unmatched entries
     * 
     * Type: 0 = Matchup , 1 = Prop not matched to matchup, 2 = Prop not matched to template
     */
    public static function getUnmatched($limit = 10, $type = -1)
    {
        $unmatches = EventDB::getUnmatched($limit, $type);

        //Before returning, unserialize the metadata field
        foreach ($unmatches as $key => $val) {
            if ($val['metadata'] != '') {
                $unmatches[$key]['metadata'] = unserialize($unmatches[$key]['metadata']);
            }
        }
        return $unmatches;
    }

    /**
     * Clears all unmatched entries
     */
    public static function clearUnmatched($unmatched_item = null, $bookie_id = null)
    {
        return EventDB::clearUnmatched($unmatched_item, $bookie_id);
    }

    public static function getGenericEventForDate($date_str)
    {
        //Check first if future events date, if so, fetch that one
        if ($date_str == '2030-12-31') {
            return self::getEvent(PARSE_FUTURESEVENT_ID);
        }

        $events = EventDB::getEvents(event_name: $date_str);
        $event_obj = $events[0] ?? null;
        //$event_obj = EventDB::getGenericEventForDate($date_str);
        if ($event_obj == null) {
            //No generic event was found, create it
            $event_obj = self::addNewEvent(new Event(0, $date_str, $date_str, true));
        }
        return $event_obj;
    }

    public static function setMetaDataForMatchup(int $matchup_id, string $metadata_attribute, ?string $metadata_value, int $bookie_id)
    {
        if ($metadata_value && trim($metadata_value) != '') {
            if ($metadata_attribute == 'event_name') {
                $a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ ';
                $b = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr ';
                $metadata_value = utf8_decode($metadata_value);
                $metadata_value = strtr($metadata_value, utf8_decode($a), $b);

                //Trims multiple spaces to single space:
                $metadata_value = preg_replace('/\h{2,}/', ' ', $metadata_value);
            }
            return EventDB::setMetaDataForMatchup($matchup_id, $metadata_attribute, trim($metadata_value), $bookie_id);
        }

        return false;
    }

    public static function getMetaDataForMatchup(int $matchup_id, string $metadata_attribute = null, int $bookie_id = null): array
    {
        return EventDB::getMetaDataForMatchup($matchup_id, $metadata_attribute, $bookie_id);
    }

    public static function moveMatchupsToGenericEvents()
    {
        $move_counter = 0;
        $matchup_counter = 0;

        //Checks the date (metadata) of the current matchup and moves the matchup to the appropriate generic event, this is typically only done for sites like PBO where matchups belong to a specific date and not a named event
        $audit_log = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::INFO, ['filename' => 'changeaudit.log']);

        $events = EventHandler::getEvents(future_events_only: true);
        foreach ($events as $event) {
            $matchups = EventHandler::getMatchups(event_id: $event->getID(), only_with_odds: true);

            foreach ($matchups as $matchup) {
                $matchup_counter++;
                $matchup_metadata_date = new \DateTime();
                $matchup_metadata_date = $matchup_metadata_date->setTimestamp(intval($matchup->getMetadata('min_gametime')));
                if (
                    new \DateTime() < $matchup_metadata_date //Check that new date is not in the past
                    && $matchup_metadata_date->format('Y-m-d') != $event->getDate()
                ) {
                    //Metadata suggests new event, move matchup to the new event
                    $new_event_id = EventHandler::getGenericEventForDate($matchup_metadata_date->format('Y-m-d'))->getID();
                    if (EventHandler::changeFight($matchup->getID(), $new_event_id)) {
                        $audit_log->info("Moved matchup " . $matchup->getTeamAsString(1) . " vs. " . $matchup->getTeamAsString(2) . " (" . $matchup->getID() . ") to " . $matchup_metadata_date->format('Y-m-d') . " based on min gametime metadata");
                        $move_counter++;
                    } else {
                        $audit_log->error("Failed to move matchup " . $matchup->getTeamAsString(1) . " vs. " . $matchup->getTeamAsString(2) . " (" . $matchup->getID() . ") to " . $matchup_metadata_date->format('Y-m-d') . " based on min gametime metadata");
                    }
                }
            }
        }
        return ['checked_matchups' => $matchup_counter, 'moved_matchups' => $move_counter];
    }

    public static function moveMatchupsToNamedEvents(): array
    {
        $move_counter = 0;
        $matchup_counter = 0;

        //Checks the date (metadata) of the current matchup and moves the matchup to the appropriate generic event, this is typically only done for sites like PBO where matchups belong to a specific date and not a named event
        $audit_log = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::INFO, ['filename' => 'changeaudit.log']);

        $events = EventHandler::getEvents(future_events_only: true);
        foreach ($events as $event) {
            $matchups = EventHandler::getMatchups(event_id: $event->getID());
            foreach ($matchups as $matchup) {
                $matchup_counter++;
                $matchup_metadata_date = new \DateTime();
                $matchup_metadata_date = $matchup_metadata_date->setTimestamp(intval($matchup->getMetadata('min_gametime')));
                if (
                    new \DateTime() < $matchup_metadata_date //Check that new date is not in the past
                    && $matchup_metadata_date->format('Y-m-d') != $event->getDate()

                ) {
                    //Ensure that all events have a consensus on the league (e.g. UFC). This is then stored in consens_event_name
                    $event_names = EventHandler::getMetaDataForMatchup($matchup->getID(), 'event_name');
                    if (count($event_names) > 0) {
                        $consensus = true;
                        $consensus_event_name = trim(strtoupper(explode(' ', $event_names[0]['mvalue'])[0]));
                        for ($i = 1; $i < count($event_names); $i++) {
                            $compare1 = trim(strtoupper(explode(' ', $event_names[$i]['mvalue'])[0]));
                            $compare2 = trim(strtoupper(explode(' ', $event_names[$i - 1]['mvalue'])[0]));
                            if ($compare1 != $compare2) {
                                //Ignore the compare if one of the fields is Future, if so we will ensure that the consensus event name is the other
                                if ($compare1 == 'FUTURE') {
                                    $consensus_event_name = $compare2;
                                } elseif ($compare2 == 'FUTURE') {
                                    $consensus_event_name = $compare1;
                                } else {
                                    $consensus = false;
                                }
                            }
                        }

                        if ($consensus && $consensus_event_name) {

                            $found_event = self::getMatchingEvent($consensus_event_name, $matchup_metadata_date->format('Y-m-d'));
                            if ($found_event) {
                                if (EventHandler::changeFight($matchup->getID(), $found_event->getID())) {
                                    $audit_log->info("Moved matchup " . $matchup->getTeamAsString(1) . " vs. " . $matchup->getTeamAsString(2) . " (" . $matchup->getID() . ") to " . $found_event->getName() . "(" . $found_event->getDate() . ") based on min gametime metadata");
                                    $move_counter++;
                                } else {
                                    $audit_log->error("Failed to move matchup " . $matchup->getTeamAsString(1) . " vs. " . $matchup->getTeamAsString(2) . " (" . $matchup->getID() . ") to " . $found_event->getName() . "(" . $found_event->getDate() . ") based on min gametime metadata. May have to create this event");
                                }
                            }
                        }
                    }
                }
            }
        }

        return ['checked_matchups' => $matchup_counter, 'moved_matchups' => $move_counter];
    }

    public static function getAllEventsForDate(string $date): array
    {
        return EventDB::getEvents(event_date: $date);
    }

    public static function deleteAllOldEventsWithoutOdds(): int
    {
        $events = EventDB::getOldEventsWithoutOdds();
        $counter = 0;
        $audit_log = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::INFO, ['filename' => 'changeaudit.log']);
        foreach ($events as $event) {
            $counter++;
            EventHandler::removeEvent($event->getID());
            $audit_log->info('Removed event ' . $event->getName() . '(' . $event->getID() . ') since it is old and does not have any matchups');
        }
        return $counter;
    }

    public static function deleteMatchupsWithoutOdds(): int
    {
        //Note, this excludes matchups that are either created manually or through scheduler
        $matchups = EventHandler::getMatchups(future_matchups_only: true, only_without_odds: true, create_source: 1); //create_source: 1 = Sportsbooks has provided odds for this matchup
        $counter = 0;
        $audit_log = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::INFO, ['filename' => 'changeaudit.log']);
        foreach ($matchups as $matchup) {
            if ($matchup->getCreateSource() == 1) {
                EventHandler::removeMatchup($matchup->getID());
                $audit_log->info('Removed matchup ' . $matchup->getTeam(1) . ' vs. ' . $matchup->getTeam(2) . ' as it was once automatically created and it now has no odds');
                $counter++;
            }
        }
        return $counter;
    }
}
