<?php

require_once('lib/bfocore/utils/db/class.DBTools.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');

/**
 * TeamDAO (Replaces FighterDAO)
 *
 * @author Christian
 */
class TeamDAO
{

    public static function getAllAltNamesForTeam($a_sTeamName)
    {
        $aNames = array();
        $sQuery = "SELECT fa.* FROM fighters_altnames fa, fighters f WHERE f.name = ? AND fa.fighter_id = f.id";
        $aParams = array($a_sTeamName);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        while ($aTeamName = mysql_fetch_array($rResult))
        {
            $aNames[] = $aTeamName['altname'];
        }

        if (count($aNames) > 0)
        {
            return $aNames;
        }

        return null;
    }

    /**
     * Gets the latest date when the fighter received an odds update
     */
    public static function getLastChangeDate($a_iFighterID)
    {
        $sQuery = 'SELECT Max(fo3.date) 
                        FROM   (SELECT fo1.date 
                                FROM   fighters f1 
                                       INNER JOIN fights fi1 
                                               ON f1.id = fi1.fighter1_id 
                                       INNER JOIN fightodds fo1 
                                               ON fi1.id = fo1.fight_id 
                                WHERE  f1.id = ? 
                                UNION ALL 
                                SELECT fo2.date 
                                FROM   fighters f2 
                                       INNER JOIN fights fi2 
                                               ON f2.id = fi2.fighter2_id 
                                       INNER JOIN fightodds fo2 
                                               ON fi2.id = fo2.fight_id 
                                WHERE  f2.id = ?) fo3; ';

        $aParams = array($a_iFighterID, $a_iFighterID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);
        return DBTools::getSingleValue($rResult);
    }

}

?>
