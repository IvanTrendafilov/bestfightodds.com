<?php

require_once('lib/bfocore/db/class.ScheduleDB.php');
require_once('lib/bfocore/utils/class.OddsTools.php');

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

    public static function getAllManualActions($type = -1)
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

    /**
     * Checks for all matchup entries that appear in both unmatched (from a bookie) and in the parsed schedule
     */
    public static function getAllUnmatchedAndScheduled()
    {
        $return_actions = [];

        $manual_actions_col = self::getAllManualActions();
        $unmatched_col = EventHandler::getUnmatched(10000);
        foreach ($manual_actions_col as $manual_action) {
            $found = false;
            if ((int) $manual_action['type'] == 1 || (int) $manual_action['type'] == 5) {
                //Create event with matchups (1) or single matchup (5)
                $action = json_decode($manual_action['description']);
                foreach ($action->matchups as $matchup) {
                    foreach ($unmatched_col as $unmatched) {
                        if ($unmatched['type'] == 0 && $found == false) {
                            $unmatched_matchup_parts = explode(' vs ', $unmatched['matchup']);
                            sort($matchup_parts);
                            sort($matchup);
                            if (OddsTools::compareNames($matchup[0], $unmatched_matchup_parts[0]) > 82 &&
                                OddsTools::compareNames($matchup[1], $unmatched_matchup_parts[1]) > 82) {
                                $found = true;
                                $return_actions[] = $manual_action;
                            }
                        }
                    }
                }
            }
        }

        return $return_actions;
    }
}
