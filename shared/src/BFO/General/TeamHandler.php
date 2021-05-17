<?php

namespace BFO\General;

use BFO\DB\TeamDB;

/**
 * TeamHandler
 */
class TeamHandler
{
    public static function getAltNamesForTeamByID($team_id)
    {
        if (!$team_id) {
            return null;
        }
        return TeamDB::getAltNamesForTeamByID($team_id);
    }

    /**
     * Gets the latest date when the fighter received an odds update
     */
    public static function getLastChangeDate($team_id)
    {
        return TeamDB::getLastChangeDate($team_id);
    }

    public static function getAllTeamsWithMissingResults()
    {
        return TeamDB::getAllTeamsWithMissingResults();
    }

    public static function addFighterAltName($fighter_id, $new_alt_name)
    {
        return TeamDB::addFighterAltName($fighter_id, $new_alt_name);
    }

    public static function getAllFighters($only_with_odds = false)
    {
        return TeamDB::getAllFighters($only_with_odds);
    }

    public static function searchFighter($name)
    {
        if (strlen($name) < 2) {
            return false;
        }
        return TeamDB::searchFighter($name);
    }

    public static function getFighterByID($id)
    {
        return TeamDB::getFighterByID($id);
    }
}
