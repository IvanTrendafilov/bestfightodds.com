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

    public static function getAllAltNamesForTeam($a_sTeamName)
    {
        return TeamDAO::getAllAltNamesForTeam($a_sTeamName);
    }

	/**
     * Gets the latest date when the fighter received an odds update
     */
    public static function getLastChangeDate($a_iFighterID)
    {
     	return TeamDAO::getLastChangeDate($a_iFighterID);
    }

}

?>
