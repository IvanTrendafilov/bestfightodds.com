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

    public static function addTeamAltName($team_id, $new_alt_name)
    {
        return TeamDB::addTeamAltName($team_id, $new_alt_name);
    }

    public static function searchTeam(string $name): ?array
    {
        if (strlen($name) < 2) {
            return [];
        }
        return TeamDB::searchTeam($name);
    }

    public static function getTeams(int $team_id = null): array
    {
        return TeamDB::getTeams(team_id: $team_id);
    }

    public static function getTeamIDByName($team_name)
    {
        return TeamDB::getTeamIDByName($team_name);
    }


    public static function createTeam(string $team_name): ?int
    {
        if (TeamHandler::getTeamIDByName($team_name) != null) {
            return null;
        }
        return TeamDB::createTeam($team_name);
    }

}
