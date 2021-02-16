<?php

require_once('lib/bfocore/dao/class.TeamDAO.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');

/**
 * TeamHandler (Replaces FighterHandler eventually)
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
        return TeamDAO::getAllAltNamesForTeam($a_sTeamName);
    }

    public static function getAltNamesForTeamByID($a_iTeamID)
    {
        if (!$a_iTeamID)
        {
            return null;
        }
        return TeamDAO::getAltNamesForTeamByID($a_iTeamID);
    }

	/**
     * Gets the latest date when the fighter received an odds update
     */
    public static function getLastChangeDate($a_iFighterID)
    {
     	return TeamDAO::getLastChangeDate($a_iFighterID);
    }

    public static function getAllTeamsWithMissingResults()
    {
        return TeamDAO::getAllTeamsWithMissingResults();
    }

}

?>
