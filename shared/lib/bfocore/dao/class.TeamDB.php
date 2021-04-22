<?php

require_once('lib/bfocore/utils/db/class.DBTools.php');
require_once('lib/bfocore/utils/db/class.PDOTools.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');

/**
 * TeamDB
 *
 * @author Christian
 */
class TeamDB
{
    public static function getAllFighters($a_bOnlyWithFights)
    {
        if ($a_bOnlyWithFights == true)
        {
            $sQuery = 'SELECT DISTINCT fi.id, fi.name
					FROM fighters fi 
						LEFT JOIN fights f ON (fi.id = f.fighter1_id OR fi.id = f.fighter2_id), fighters f1, fighters f2
					WHERE f.fighter1_id = f1.id
						AND f.fighter2_id = f2.id
					ORDER BY fi.name ASC';
        }
        else
        {
            $sQuery = 'SELECT f.id, f.name 
						FROM fighters f 
						ORDER BY f.name ASC';
        }

        $rResult = DBTools::doQuery($sQuery);

        $aFighters = array();

        while ($aFighter = mysqli_fetch_array($rResult))
        {
            $aFighters[] = new Fighter($aFighter['name'], $aFighter['id']);
        }

        return $aFighters;
    }

    public static function searchFighter($a_sFighterName)
    {
        $sQuery = "SELECT f.id, f.name, MATCH(f.name) AGAINST (?) AS score  
					FROM fighters f
					WHERE f.name LIKE ?
                        OR MATCH(f.name) AGAINST (?) 
					ORDER BY score DESC, f.name ASC";

        $aParams = array($a_sFighterName, '%' . $a_sFighterName . '%', $a_sFighterName);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aFighters = array();

        while ($aFighter = mysqli_fetch_array($rResult))
        {
            $aFighters[] = new Fighter($aFighter['name'], $aFighter['id']);
        }

        return $aFighters;
    }

    public static function getFighterByID($a_iID)
    {
        $sQuery = 'SELECT f.name, f.id FROM fighters f WHERE f.id = ?';
        $aParams = array($a_iID);
        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        if ($aFighter = mysqli_fetch_array($rResult))
        {
            return new Fighter($aFighter['name'], $aFighter['id']);
        }
        return null;
    }


    /**
     * @deprecated Replaced by getAltNamesForTeamByID that takes ID as input not a string name
     */
    public static function getAllAltNamesForTeam($a_sTeamName)
    {
        $aNames = array();
        $sQuery = "SELECT fa.* FROM fighters_altnames fa, fighters f WHERE f.name = ? AND fa.fighter_id = f.id";
        $aParams = array($a_sTeamName);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        while ($aTeamName = mysqli_fetch_array($rResult))
        {
            $aNames[] = $aTeamName['altname'];
        }

        if (count($aNames) > 0)
        {
            return $aNames;
        }

        return null;
    }

    public static function getAltNamesForTeamByID($a_iTeamID)
    {
        $aNames = [];
        $sQuery = "SELECT fa.altname FROM fighters_altnames fa WHERE fa.fighter_id = ?";
        $rows = PDOTools::findMany($sQuery, [$a_iTeamID]);
        foreach ($rows as $row)
        {
            $aNames[] = $row['altname'];
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


    public static function getAllTeamsWithMissingResults()
    {
        $sQuery = 'SELECT DISTINCT f.* FROM fighters f 
                        INNER JOIN fights fi ON (fi.fighter1_id = f.id OR fi.fighter2_id = f.id) 
                        INNER JOIN events e ON fi.event_id = e.id 
                    WHERE fi.id NOT IN (SELECT mr.matchup_id FROM matchups_results mr)
                        AND LEFT(e.date, 10) < LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)';
        $rResult = DBTools::doQuery($sQuery);
        $aFighters = [];
        while ($aFighter = mysqli_fetch_array($rResult))
        {
            $aFighters[] = new Fighter($aFighter['name'], $aFighter['id']);
        }
        return $aFighters;
    }

    public static function addFighterAltName($a_iFighterID, $a_sAltName)
    {
        if ($a_iFighterID == "" || $a_sAltName == "") {
            return false;
        }

        $sQuery = 'INSERT INTO fighters_altnames(fighter_id, altname)
                    VALUES (?,?)';

        $aParams = array($a_iFighterID, strtoupper($a_sAltName));
        $bResult = DBTools::doParamQuery($sQuery, $aParams);
        if ($bResult == false) {
            return false;
        }

        return true;
    }

}

?>
