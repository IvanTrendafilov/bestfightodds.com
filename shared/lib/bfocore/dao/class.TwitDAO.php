<?php

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/utils/db/class.DBTools.php');
require_once('lib/bfocore/utils/db/class.PDOTools.php');

/**
 * Twitter DAO
 *
 * Handles all calls to the database related to tweets
 *
 */
class TwitDAO
{
    public static function saveFightAsTwittered($a_iFightID)
    {
        $sQuery = 'INSERT INTO fight_twits(fight_id, twitdate)
                        VALUES (?, NOW())';

        $aParams = array($a_iFightID);

        DBTools::doParamQuery($sQuery, $aParams);

        if (DBTools::getAffectedRows() == 1)
        {
            return true;
        }
        return false;
    }

    public static function getUntwitteredFights()
    {
        $sQuery = 'SELECT f.*, f1.name AS fighter1_name, f2.name AS fighter2_name 
                    FROM fights f, events e, fighters f1, fighters f2
                    WHERE NOT EXISTS
                        (SELECT ft.*
                            FROM fight_twits ft
                            WHERE f.id = ft.fight_id)
                        AND f.event_id = e.id AND LEFT(e.date, 10) >= LEFT(NOW(), 10)
                        AND EXISTS
                            (SELECT fo.*
                                FROM fightodds fo
                                WHERE f.id = fo.fight_id)
                        AND f1.id = f.fighter1_id
                        AND f2.id = f.fighter2_id
                        AND e.display = 1';


        $rResult = DBTools::doQuery($sQuery);

        $aFights = array();
        while ($aFight = mysqli_fetch_array($rResult))
        {
            $oTempFight = new Fight($aFight['id'], $aFight['fighter1_name'], $aFight['fighter2_name'], $aFight['event_id']);
            $oTempFight->setFighterID(1, $aFight['fighter1_id']);
            $oTempFight->setFighterID(2, $aFight['fighter2_id']);
            $oTempFight->setMainEvent(($aFight['is_mainevent'] == 1 ? true : false));
            $aFights[] = $oTempFight;
        }

        return $aFights;
    }

    public static function addTwitterHandle($team_id, $handle)
    {
        $query = 'INSERT INTO teams_twitterhandles(team_id, handle)
                        VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE team_id = ?, handle = ?';
        $params = [$team_id, $handle, $team_id, $handle];
        return PDOTools::insert($query, $params);
    }

    public static function getTwitterHandle($team_id)
    {
        $query = 'SELECT * FROM teams_twitterhandles WHERE team_id = ?';
        $params = [$team_id];
        return PDOTools::findOne($query, $params);
    }
}
?>
