<?php

namespace BFO\General;

use BFO\DB\ScheduleDB;
use BFO\General\EventHandler;
use BFO\Utils\OddsTools;
use BFO\DataTypes\Fight;
use BFO\DataTypes\Event;

class ScheduleHandler
{
    /**
     * Types:
     *
     * 1: Unmatched event to be created
     * 2: Event to be renamed
     * 3: Event to be moved to another date
     * 4: Event to be deleted
     * 5: Matchup to be created
     * 6: Matchup to be moved to different event
     * 7: Matchup to be deleted
     * 8: Matchup to be moved to a different not yet created event
     */
    public static function storeManualAction($message, $type)
    {
        if ($message == '' || !is_integer($type)) {
            return false;
        }
        return ScheduleDB::storeManualAction($message, $type);
    }

    public static function getAllManualActions(int $type = -1): ?array
    {
        return ScheduleDB::getAllManualActions($type);
    }

    public static function clearAllManualActions()
    {
        return ScheduleDB::clearAllManualActions();
    }

    public static function clearManualAction($action_id)
    {
        return ScheduleDB::clearManualAction($action_id);
    }

    public static function acceptAllCreateActions(): int
    {
        $audit_log = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::INFO, ['filename' => 'changeaudit.log']);
        $counter = 0;
        $ma_create_matchups = ScheduleHandler::getAllManualActions(5) ?? []; //1 = Create matchup
        foreach ($ma_create_matchups as $action) {
            $action['action_obj'] = json_decode($action['description'], true);
            $new_matchup = new Fight(
                -1,
                $action['action_obj']['matchups'][0]['team1'],
                $action['action_obj']['matchups'][0]['team2'],
                $action['action_obj']['eventID']
            );
            $new_matchup->setCreateSource(2);
            if (EventHandler::createMatchup($new_matchup)) {
                $audit_log->info("Created new matchup " . $new_matchup->getTeamAsString(1) . ' vs. ' . $new_matchup->getTeamAsString(2) . ' at ' . $new_matchup->getEventID() . ' as proposed by scheduler');
            } else {
                $audit_log->error("Failed to create new matchup " . $new_matchup->getTeamAsString(1) . ' vs. ' . $new_matchup->getTeamAsString(2) . ' at ' . $new_matchup->getEventID() . ' as proposed by scheduler');
            }
            $result = ScheduleHandler::clearManualAction($action['id']);
            $counter++;
        }

        $ma_create_events = ScheduleHandler::getAllManualActions(1) ?? []; //5 = Create event with matchups
        foreach ($ma_create_events as $action) {
            $action['action_obj'] = json_decode($action['description'], true);
            $new_event = new Event(-1, $action['action_obj']['eventDate'], $action['action_obj']['eventTitle'], true);
            $event = EventHandler::addNewEvent($new_event);
            if ($event) {
                $audit_log->info("Created new event " . $event->getName() . ' on ' . $event->getDate() . ' as proposed by scheduler');
                //Event added succesfully. Add matchups
                foreach ($action['action_obj']['matchups'] as $matchup) {
                    $new_matchup = new Fight(
                        -1,
                        $matchup[0],
                        $matchup[1],
                        $event->getID()
                    );
                    $new_matchup->setCreateSource(2);
                    if (EventHandler::createMatchup($new_matchup)) {
                        $audit_log->info("Created new matchup " . $new_matchup->getTeamAsString(1) . ' vs. ' . $new_matchup->getTeamAsString(2) . ' as child to ' . $event->getName() . ' (' . $new_matchup->getEventID() . ') as proposed by scheduler');
                    } else {
                        $audit_log->error("Failed to create new matchup " . $new_matchup->getTeamAsString(1) . ' vs. ' . $new_matchup->getTeamAsString(2) . ' as child to ' . $event->getName() . ' (' . $new_matchup->getEventID() . ') as proposed by scheduler');
                    }
                    $counter++;
                }
                $result = ScheduleHandler::clearManualAction($action['id']);
            } else {
                $audit_log->error("Failed to create new event " . $new_event->getName() . ' on ' . $new_event->getDate() . ' as proposed by scheduler');
            }
        }
        return $counter;
    }
}
