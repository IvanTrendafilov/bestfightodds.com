<?php

require_once('lib/bfocore/db/class.TeamDB.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');

/**
 * TeamHandler
 *
 * @author Christian
 */
class TeamHandler
{
    /**
     * @deprecated Replaced by getAltNamesForTeamByID that takes ID as input not a string name
     */
    public static function getAllAltNamesForTeam($a_sTeamName)
    {
        return TeamDB::getAllAltNamesForTeam($a_sTeamName);
    }

    public static function getAltNamesForTeamByID($a_iTeamID)
    {
        if (!$a_iTeamID)
        {
            return null;
        }
        return TeamDB::getAltNamesForTeamByID($a_iTeamID);
    }

	/**
     * Gets the latest date when the fighter received an odds update
     */
    public static function getLastChangeDate($a_iFighterID)
    {
     	return TeamDB::getLastChangeDate($a_iFighterID);
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
        if (strlen($name) < 2)
        {
            return false;
        }
        return TeamDB::searchFighter($name);
    }

    public static function getFighterByID($id)
    {
        return TeamDB::getFighterByID($id);
    }

}

?>
