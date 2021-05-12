<?php

namespace BFO\DB;

use BFO\Utils\DB\DBTools;
use BFO\Utils\DB\PDOTools;
use BFO\Utils\OddsTools;
use BFO\DataTypes\Event;
use BFO\DataTypes\Fight;
use BFO\DataTypes\FightOdds;

class EventDB
{
    public static function getAllUpcomingEvents()
    {
        $sQuery = 'SELECT id, date, name, display
                    FROM events
                    WHERE LEFT(date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)
                    ORDER BY date ASC, LEFT(name,3) = "UFC" DESC, LEFT(name,8) = "Bellator" DESC, name ASC;';
        $rResult = DBTools::doQuery($sQuery);
        $aEvents = array();
        while ($aEvent = mysqli_fetch_array($rResult)) {
            $aEvents[] = new Event($aEvent['id'], $aEvent['date'], $aEvent['name'], $aEvent['display']);
        }

        return $aEvents;
    }

    public static function getAllEvents()
    {
        $sQuery = 'SELECT id, date, name, display
                    FROM events
                    ORDER BY date ASC, LEFT(name,3) = "UFC" DESC, name ASC';
        $rResult = DBTools::doQuery($sQuery);
        $aEvents = array();
        while ($aEvent = mysqli_fetch_array($rResult)) {
            $aEvents[] = new Event($aEvent['id'], $aEvent['date'], $aEvent['name'], $aEvent['display']);
        }

        return $aEvents;
    }

    public static function getEvent($a_iEventID, $a_bFutureEventsOnly = false)
    {
        $sExtraWhere = '';
        if ($a_bFutureEventsOnly) {
            $sExtraWhere = ' AND LEFT(date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10) ';
        }

        $sQuery = 'SELECT id, date, name, display FROM events WHERE id = ? ' . $sExtraWhere;
        $aParams = array($a_iEventID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);
        $aEvents = array();
        while ($aEvent = mysqli_fetch_array($rResult)) {
            $aEvents[] = new Event($aEvent['id'], $aEvent['date'], $aEvent['name'], $aEvent['display']);
        }
        if (sizeof($aEvents) > 0) {
            return $aEvents[0];
        }
        return null;
    }


    public static function getEventByName($a_sName)
    {
        $sQuery = 'SELECT id, date, name, display FROM events WHERE name = ?';
        $rResult = DBTools::doParamQuery($sQuery, array($a_sName));
        $aEvents = array();
        while ($aEvent = mysqli_fetch_array($rResult)) {
            $aEvents[] = new Event($aEvent['id'], $aEvent['date'], $aEvent['name'], $aEvent['display']);
        }
        if (sizeof($aEvents) > 0) {
            return $aEvents[0];
        }
        return null;
    }

    /**
     *
     * If second parameter is set to true then only fights that have odds on them will be returned
     */
    public static function getAllFightsForEvent($a_iEventID, $a_bOnlyWithOdds = false)
    {
        if ($a_bOnlyWithOdds == true) {
            $sQuery = 'SELECT f.id, f1.name AS fighter1_name, f2.name AS fighter2_name, f.event_id, f1.id AS fighter1_id, f2.id AS fighter2_id, f.is_mainevent as is_mainevent, (SELECT MIN(date) FROM fightodds fo WHERE fo.fight_id = f.id) AS latest_date, m.mvalue as gametime 
                        FROM fights f LEFT JOIN (SELECT matchup_id, AVG(mvalue) as mvalue FROM matchups_metadata mm WHERE mm.mattribute = "gametime" GROUP BY matchup_id) m ON f.id = m.matchup_id
                            LEFT JOIN fighters f1 ON f1.id = f.fighter1_id
                            LEFT JOIN fighters f2 ON f2.id = f.fighter2_id
                        WHERE f.event_id = ?
                        HAVING latest_date IS NOT NULL
                        ORDER BY f.is_mainevent DESC, gametime DESC';
        } else {
            $sQuery = 'SELECT f.id, f1.name AS fighter1_name, f2.name AS fighter2_name, f.event_id, f1.id AS fighter1_id, f2.id AS fighter2_id, f.is_mainevent AS is_mainevent
                        FROM fights f, fighters f1, fighters f2
                        WHERE f.event_id = ? 
                            AND f.fighter1_id = f1.id
                            AND f.fighter2_id = f2.id
                            ORDER BY f.is_mainevent DESC, f.id ASC';
        }

        $aParams = array($a_iEventID);
        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aFights = array();

        while ($aFight = mysqli_fetch_array($rResult)) {
            $oTempFight = new Fight($aFight['id'], $aFight['fighter1_name'], $aFight['fighter2_name'], $aFight['event_id']);
            $oTempFight->setFighterID(1, $aFight['fighter1_id']);
            $oTempFight->setFighterID(2, $aFight['fighter2_id']);
            $oTempFight->setMainEvent($aFight['is_mainevent']);
            if (isset($aFight['gametime'])) {
                $oTempFight->setMetadata('gametime', $aFight['gametime']);
            }
            $aFights[] = $oTempFight;
        }

        return $aFights;
    }

    public static function getAllFightsForEventWithoutOdds($a_iEventID)
    {
        $sQuery = 'SELECT f.id, f1.name AS fighter1_name, f2.name AS fighter2_name, f.event_id, f1.id AS fighter1_id, f2.id AS fighter2_id
                    FROM fights f LEFT JOIN fightodds fo ON f.id = fo.fight_id, fighters f1, fighters f2
                    WHERE fo.fight_id IS NULL
                        AND f.event_id = ?
                        AND f.fighter1_id = f1.id
                        AND f.fighter2_id = f2.id
                        ORDER BY f.is_mainevent DESC, f.id ASC';

        $aParams = array($a_iEventID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aFights = array();

        while ($aFight = mysqli_fetch_array($rResult)) {
            $oTempMatchup = new Fight($aFight['id'], $aFight['fighter1_name'], $aFight['fighter2_name'], $aFight['event_id']);
            $oTempMatchup->setFighterID(1, $aFight['fighter1_id']);
            $oTempMatchup->setFighterID(2, $aFight['fighter2_id']);
            $aFights[] = $oTempMatchup;
        }

        return $aFights;
    }

    /**
     * Get all upcoming fights
     *
     * @param boolean $a_bOnlyWithOdds Get only fights with odds
     * @return Array Collection of fights
     */
    public static function getAllUpcomingMatchups($a_bOnlyWithOdds = false)
    {
        if ($a_bOnlyWithOdds == true) {
            $sQuery = 'SELECT f.id, f1.name AS fighter1_name, f2.name AS fighter2_name, f.event_id, f1.id AS fighter1_id, f2.id AS fighter2_id, f.is_mainevent as is_mainevent, (SELECT date FROM fightodds fo WHERE fo.fight_id = f.id LIMIT 1) AS latest_date
                        FROM fights f, fighters f1, fighters f2, events e
                        WHERE f.fighter1_id = f1.id
                          AND f.fighter2_id = f2.id
                          AND f.event_id = e.id
                          AND LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)
                        HAVING latest_date IS NOT NULL
                        ORDER BY f.is_mainevent DESC, f.id ASC';
        } else {
            $sQuery = 'SELECT f.id, f1.name AS fighter1_name, f2.name AS fighter2_name, f.event_id, f1.id AS fighter1_id, f2.id AS fighter2_id, f.is_mainevent AS is_mainevent
                        FROM fights f, fighters f1, fighters f2, events e
                        WHERE f.fighter1_id = f1.id
                            AND f.fighter2_id = f2.id
                            AND f.event_id = e.id
                            AND LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)
                            ORDER BY f.is_mainevent DESC, f.id ASC';
        }

        $rResult = DBTools::doQuery($sQuery);

        $aFights = array();

        while ($aFight = mysqli_fetch_array($rResult)) {
            $oTempFight = new Fight($aFight['id'], $aFight['fighter1_name'], $aFight['fighter2_name'], $aFight['event_id']);
            $oTempFight->setFighterID(1, $aFight['fighter1_id']);
            $oTempFight->setFighterID(2, $aFight['fighter2_id']);
            $oTempFight->setMainEvent($aFight['is_mainevent']);
            $aFights[] = $oTempFight;
        }

        return $aFights;
    }

    public static function getAllLatestOddsForFight($a_iFightID, $a_iOffset = 0)
    {
        if ($a_iOffset != 0 && $a_iOffset != 1) {
            //TODO: Handle this more gracefully
            return false;
        }

        $aParams = array($a_iFightID, $a_iFightID);
        $sExtraQuery = '';

        if ($a_iOffset == 1) {
            $sExtraQuery = ' AND fo4.date < (SELECT
                MAX(fo5.date) AS date
            FROM
                fightodds fo5
            WHERE
                fo5.fight_id = ? AND fo5.bookie_id = fo4.bookie_id) ';
            $aParams[] = $a_iFightID;
        }

        $sQuery = 'SELECT
            fo2.fight_id, fo2.fighter1_odds, fo2.fighter2_odds, fo2.bookie_id, fo2.date
            FROM
                fightodds AS fo2, bookies bo,
                (SELECT
                    MAX(fo4.date) as date, bookie_id
                FROM
                    fightodds fo4
                WHERE
                    fo4.fight_id = ? ' . $sExtraQuery . ' 
                GROUP BY bookie_id) AS fo3
            WHERE
                fo2.fight_id = ? AND fo2.bookie_id = fo3.bookie_id AND fo2.date
            = fo3.date AND fo2.bookie_id = bo.id GROUP BY fo2.bookie_id ORDER BY bo.position,
            fo2.bookie_id, fo2.fight_id ASC;';

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aFightOddsCol = array();

        while ($aFightOdds = mysqli_fetch_array($rResult)) {
            $aFightOddsCol[] = new FightOdds($aFightOdds['fight_id'], $aFightOdds['bookie_id'], $aFightOdds['fighter1_odds'], $aFightOdds['fighter2_odds'], $aFightOdds['date']);
        }

        return $aFightOddsCol;
    }

    public static function getLatestOddsForFightAndBookie($a_iFightID, $a_iBookieID)
    {
        $sQuery = 'SELECT fight_id, fighter1_odds, fighter2_odds, bookie_id, date
                    FROM fightodds
                    WHERE bookie_id = ? 
                        AND fight_id = ? 
                    ORDER BY date DESC
                    LIMIT 0,1';

        $aParams = array($a_iBookieID, $a_iFightID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aFightOddsCol = array();
        while ($aFightOdds = mysqli_fetch_array($rResult)) {
            $aFightOddsCol[] = new FightOdds($aFightOdds['fight_id'], $aFightOdds['bookie_id'], $aFightOdds['fighter1_odds'], $aFightOdds['fighter2_odds'], $aFightOdds['date']);
        }
        if (sizeof($aFightOddsCol) > 0) {
            return $aFightOddsCol[0];
        }
        return null;
    }

    public static function getAllOddsForFightAndBookie($a_iFightID, $a_iBookieID)
    {
        $sQuery = 'SELECT fight_id, fighter1_odds, fighter2_odds, bookie_id, date
                        FROM fightodds
                        WHERE fight_id = ? 
                            AND bookie_id = ? 
                        ORDER BY date ASC';

        $rResult = DBTools::doParamQuery($sQuery, array($a_iFightID, $a_iBookieID));

        $aFightOddsCol = array();
        while ($aFightOdds = mysqli_fetch_array($rResult)) {
            $aFightOddsCol[] = new FightOdds($aFightOdds['fight_id'], $aFightOdds['bookie_id'], $aFightOdds['fighter1_odds'], $aFightOdds['fighter2_odds'], $aFightOdds['date']);
        }
        if (sizeof($aFightOddsCol) > 0) {
            return $aFightOddsCol;
        }
        return null;
    }

    public static function getAllOddsForMatchup($a_iMatchupID)
    {
        $sQuery = 'SELECT fight_id, fighter1_odds, fighter2_odds, bookie_id, date
                        FROM fightodds
                        WHERE fight_id = ? 
                        ORDER BY date ASC';

        $rResult = DBTools::doParamQuery($sQuery, array($a_iMatchupID));

        $aFightOddsCol = array();
        while ($aFightOdds = mysqli_fetch_array($rResult)) {
            $aFightOddsCol[] = new FightOdds($aFightOdds['fight_id'], $aFightOdds['bookie_id'], $aFightOdds['fighter1_odds'], $aFightOdds['fighter2_odds'], $aFightOdds['date']);
        }
        if (sizeof($aFightOddsCol) > 0) {
            return $aFightOddsCol;
        }
        return null;
    }


    /**
     * Retrieves a future fight
     *
     * Matches the specified matchup using lexographical checks
     *
     * TODO: (Low) Parts of this should be moved to EventHandler
     */
    public static function getFight($a_sFighter1, $a_sFighter2, $a_iEventID = -1)
    {
        $sExtraWhere = '';
        if ($a_iEventID != -1) {
            $sExtraWhere = ' AND event_id = ' . $a_iEventID . ' ';
        }

        $sQuery = 'SELECT 1 AS original, t.id, a.name AS fighter1_name, b.name AS fighter2_name, t.event_id
                      FROM events e, fights t
                          JOIN fighters a ON a.id = t.fighter1_id
                          JOIN fighters b ON b.id = t.fighter2_id WHERE e.id = event_id AND LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)  ' . $sExtraWhere . '
                    UNION SELECT 0 AS original, t.id, a.altname , b.altname, t.event_id
                      FROM events e, fights t
                          JOIN fighters_altnames a ON a.fighter_id = fighter1_id
                          JOIN fighters_altnames b ON b.fighter_id = fighter2_id WHERE e.id = event_id AND LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)  ' . $sExtraWhere . '
                    UNION SELECT 0 AS original, t.id, a.name , b.altname, t.event_id
                      FROM events e, fights t
                          JOIN fighters a ON a.id = t.fighter1_id
                          JOIN fighters_altnames b ON b.fighter_id = fighter2_id WHERE e.id = event_id AND LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)  ' . $sExtraWhere . '
                    UNION SELECT 0 AS original, t.id, a.altname , b.name, t.event_id
                      FROM events e, fights t
                          JOIN fighters b ON b.id = fighter2_id
                          JOIN fighters_altnames a ON a.fighter_id = fighter1_id WHERE e.id = event_id AND LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)  ' . $sExtraWhere . ' ';

        $rResult = DBTools::getCachedQuery($sQuery);
        if ($rResult == null) {
            $rResult = DBTools::doQuery($sQuery);
            DBTools::cacheQueryResults($sQuery, $rResult);
        }

        while ($aFight = mysqli_fetch_array($rResult)) {
            $oTempFight = new Fight($aFight['id'], $aFight['fighter1_name'], $aFight['fighter2_name'], $aFight['event_id']);
            if ($aFight['fighter1_name'] > $aFight['fighter2_name']) {
                $oTempFight->setComment('switched');
            }

            if (OddsTools::compareNames($oTempFight->getFighter(1), $a_sFighter1) > 82) {
                if (OddsTools::compareNames($oTempFight->getFighter(2), $a_sFighter2) > 82) {
                    $aFoundFight = null;
                    if ($aFight['original'] == '0') {
                        $aFoundFight = EventDB::getFightByID($aFight['id']);

                        $bCheckFight = EventDB::isFightOrderedInDatabase($aFight['id']);
                        if ($bCheckFight == true) {
                            if ($oTempFight->getComment() == 'switched') {
                                $aFoundFight->setComment('switched');
                            }
                        } else {
                            if ($oTempFight->getComment() != 'switched') {
                                $aFoundFight->setComment('switched');
                            }
                        }
                    } else {
                        $aFoundFight = new Fight($aFight['id'], $aFight['fighter1_name'], $aFight['fighter2_name'], $aFight['event_id']);
                    }
                    return $aFoundFight;
                }
            }
        }
        //No matching fight found
        return null;
    }

    //New version of getFight above. Improvements are the possibility of finding old matchups
    //Params:
    //team1_name = Required
    //team2_name = Required
    //future_only = Optional
    //past_only = Optional
    //known_fighter_id = Optional
    //event_date = Optional (format: yyyy-mm-dd) Note: day before and after is also included
    //event_id = Optional
    public static function getMatchingFightV2($a_aParams)
    {
        $sExtraWhere = '';
        $aQueryParams = [];
        if (isset($a_aParams['future_only']) && $a_aParams['future_only'] == true) {
            $sExtraWhere .= ' AND LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)';
        }
        if (isset($a_aParams['past_only']) && $a_aParams['past_only'] == true) {
            $sExtraWhere .= ' AND LEFT(e.date, 10) < LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)';
        }
        if (isset($a_aParams['event_id']) && is_numeric($a_aParams['event_id']) && $a_aParams['event_id'] != -1) {
            $sExtraWhere .= ' AND event_id = ' . DBTools::makeParamSafe($a_aParams['event_id']) . '';
        }
        if (isset($a_aParams['known_fighter_id']) && is_numeric($a_aParams['known_fighter_id'])) {
            $sExtraWhere .= ' AND (fighter1_id = ' . DBTools::makeParamSafe($a_aParams['known_fighter_id']) . ' OR fighter2_id = ' . DBTools::makeParamSafe($a_aParams['known_fighter_id']) . ')';
        }
        if (isset($a_aParams['event_date'])) {
            $sExtraWhere .= ' AND LEFT(e.date, 10) <= DATE_ADD(\'' . DBTools::makeParamSafe($a_aParams['event_date']) . '\', INTERVAL +1 DAY) AND LEFT(e.date, 10) >= DATE_ADD(\'' . DBTools::makeParamSafe($a_aParams['event_date']) . '\', INTERVAL -1 DAY)';
        }

        $sQuery = 'SELECT 1 AS original, t.id, a.name AS fighter1_name, a.id as fighter1_id, b.name AS fighter2_name, b.id as fighter2_id, t.event_id
                      FROM events e, fights t
                          JOIN fighters a ON a.id = t.fighter1_id
                          JOIN fighters b ON b.id = t.fighter2_id WHERE e.id = event_id ' . $sExtraWhere . '
                    UNION SELECT 0 AS original, t.id, a.altname, a.fighter_id, b.altname, b.fighter_id, t.event_id
                      FROM events e, fights t
                          JOIN fighters_altnames a ON a.fighter_id = fighter1_id
                          JOIN fighters_altnames b ON b.fighter_id = fighter2_id WHERE e.id = event_id ' . $sExtraWhere . '
                    UNION SELECT 0 AS original, t.id, a.name, a.id, b.altname, b.fighter_id, t.event_id
                      FROM events e, fights t
                          JOIN fighters a ON a.id = t.fighter1_id
                          JOIN fighters_altnames b ON b.fighter_id = fighter2_id WHERE e.id = event_id ' . $sExtraWhere . '
                    UNION SELECT 0 AS original, t.id, a.altname, a.fighter_id, b.name, b.id, t.event_id
                      FROM events e, fights t
                          JOIN fighters b ON b.id = fighter2_id
                          JOIN fighters_altnames a ON a.fighter_id = fighter1_id WHERE e.id = event_id ' . $sExtraWhere . ' ';

        $rResult = DBTools::getCachedQuery($sQuery);
        if ($rResult == null) {
            $rResult = DBTools::doQuery($sQuery);
            DBTools::cacheQueryResults($sQuery, $rResult);
        }

        while ($aFight = mysqli_fetch_array($rResult)) {
            $oTempFight = new Fight($aFight['id'], $aFight['fighter1_name'], $aFight['fighter2_name'], $aFight['event_id']);
            if ($aFight['fighter1_name'] > $aFight['fighter2_name']) {
                $oTempFight->setComment('switched');
            }

            if (OddsTools::compareNames($oTempFight->getFighter(($a_aParams['team1_name'] >= $a_aParams['team2_name'] ? 2 : 1)), $a_aParams['team1_name']) > 82) {
                if (OddsTools::compareNames($oTempFight->getFighter(($a_aParams['team1_name'] >= $a_aParams['team2_name'] ? 1 : 2)), $a_aParams['team2_name']) > 82) {
                    $aFoundFight = null;
                    if ($aFight['original'] == '0') {
                        $aFoundFight = EventDB::getFightByID($aFight['id']);

                        $bCheckFight = EventDB::isFightOrderedInDatabase($aFight['id']);
                        if ($bCheckFight == true) {
                            if ($oTempFight->getComment() == 'switched') {
                                $aFoundFight->setComment('switched');
                            }
                        } else {
                            if ($oTempFight->getComment() != 'switched') {
                                $aFoundFight->setComment('switched');
                            }
                        }
                    } else {
                        $aFoundFight = new Fight($aFight['id'], $aFight['fighter1_name'], $aFight['fighter2_name'], $aFight['event_id']);
                        $aFoundFight->setFighterID(1, $aFight['fighter1_id']);
                        $aFoundFight->setFighterID(2, $aFight['fighter2_id']);
                    }
                    return $aFoundFight;
                }
            }
        }
        //No matching fight found
        return null;
    }

    public static function getFightByID($a_iFightID)
    {
        $sQuery = 'SELECT f.id, f1.name AS fighter1_name, f2.name AS fighter2_name, f.event_id, f.fighter1_id, f.fighter2_id 
                    FROM fights f, fighters f1, fighters f2
                    WHERE f1.id = f.fighter1_id
                        AND f2.id = f.fighter2_id
                        AND f.id = ?
                    LIMIT 0,1';

        $aParams = array($a_iFightID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aFight = mysqli_fetch_array($rResult);
        if ($aFight != false && sizeof($aFight) > 0) {
            $oTempFight = new Fight($aFight['id'], $aFight['fighter1_name'], $aFight['fighter2_name'], $aFight['event_id']);
            $oTempFight->setFighterID(1, $aFight['fighter1_id']);
            $oTempFight->setFighterID(2, $aFight['fighter2_id']);
            return $oTempFight;
        }
        return null;
    }

    /**
     * Ugly little function that is needed to check if a fight is stored not lexiographically order in the database.
     * For example: RAMEAU SOKOUDJU, LYOTO MACHIDA gives false, BJ PENN, JOE STEVENSON gives true
     */
    public static function isFightOrderedInDatabase($fight_id)
    {
        $query = 'SELECT f.id, f1.name AS fighter1_name, f2.name AS fighter2_name, f.event_id
                    FROM fights f, fighters f1, fighters f2
                    WHERE f1.id = f.fighter1_id
                        AND f2.id = f.fighter2_id
                        AND f.id = ? 
                    LIMIT 0,1';

        $params = [$fight_id];

        $rResult = DBTools::doParamQuery($query, $params);

        $aFight = mysqli_fetch_array($rResult);
        if (sizeof($aFight) > 0) {
            if ($aFight['fighter1_name'] > $aFight['fighter2_name']) {
                return false;
            } else {
                return true;
            }
        }
        return null;
    }

    public static function addNewFightOdds($fightodds_obj)
    {
        //TODO: This query should be updated to check for valid value from fights and bookie table
        $query = 'INSERT INTO fightodds(fight_id, fighter1_odds, fighter2_odds, bookie_id, date)
                        VALUES(?, ?, ?, ?, NOW())';

        $params = [$fightodds_obj->getFightID(), $fightodds_obj->getFighterOdds(1), $fightodds_obj->getFighterOdds(2), $fightodds_obj->getBookieID()];

        try {
            $id = PDOTools::insert($query, $params);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Duplicate entry", 10);
            } else {
                throw new \Exception("Unknown error " . $e->getMessage(), 10);
            }
            return false;
        }
        return true;
    }

    public static function addNewFighter($fighter_name)
    {
        if (EventDB::getFighterIDByName($fighter_name) != null) {
            return false;
        }

        $params = [strtoupper($fighter_name)];
        $query = 'INSERT INTO fighters(name, url)
                        VALUES(?, \'\')';
        try {
            $id = PDOTools::insert($query, $params);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Duplicate entry", 10);
            } else {
                throw new \Exception("Unknown error " . $e->getMessage(), 10);
            }
            return false;
        }
        return $id;
    }

    /**
     * Adds a new event
     *
     * TODO: Add check to see that event doesn't already exist
     */
    public static function addNewEvent($event_obj)
    {
        $query = 'INSERT INTO events(date, name, display)
                        VALUES(?, ?, ?)';

        $params = array($event_obj->getDate(), $event_obj->getName(), ($event_obj->isDisplayed() ? '1' : '0'));

        try {
            $id = PDOTools::insert($query, $params);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Duplicate entry", 10);
            } else {
                throw new \Exception("Unknown error " . $e->getMessage(), 10);
            }
            return false;
        }
        return $id;
    }

    public static function getFighterIDByName($fighter_name)
    {
        $query = 'SELECT fn.id
                    FROM (SELECT f.id as id, f.name as name FROM fighters f
                        UNION
                        SELECT fa.fighter_id as id, fa.altname as name FROM fighters_altnames fa
                        ) AS fn
                    WHERE fn.name = ?';

        $params = [strtoupper($fighter_name)];

        $result = DBTools::doParamQuery($query, $params);

        $fighters = array();
        while ($row = mysqli_fetch_array($result)) {
            $fighters[] = $row['id'];
        }
        if (sizeof($fighters) > 0) {
            return $fighters[0];
        }
        return null;
    }

    public static function addNewFight($a_oFight)
    {
        //Check that event is ok
        if (EventDB::getEvent($a_oFight->getEventID()) != null) {
            //Check if fight isn't already added
            if (EventDB::getFight($a_oFight->getFighter(1), $a_oFight->getFighter(2), $a_oFight->getEventID()) == null) {
                //Check that both fighters exist, if not, add them
                $fighter1_id = EventDB::getFighterIDByName($a_oFight->getFighter(1));
                if ($fighter1_id == null) {
                    $fighter1_id = EventDB::addNewFighter($a_oFight->getFighter(1));
                }
                $fighter2_id = EventDB::getFighterIDByName($a_oFight->getFighter(2));
                if ($fighter2_id == null) {
                    $fighter2_id = EventDB::addNewFighter($a_oFight->getFighter(2));
                }

                if ($fighter1_id == null || $fighter2_id == null) {
                    return false;
                }

                $query = 'INSERT INTO fights(fighter1_id, fighter2_id, event_id)
                                VALUES(?, ?, ?)';

                $params = array($fighter1_id, $fighter2_id, $a_oFight->getEventID());
                DBTools::doParamQuery($query, $params);

                //Invalidate cache whenever we add a matchup in case some running function is caching matchups
                DBTools::invalidateCache();

                return DBTools::getLatestID();
            }
        }
        return false;
    }

    public static function removeEvent($event_id)
    {
        $query = "DELETE FROM events WHERE id = ?";
        $params = array($event_id);
        DBTools::doParamQuery($query, $params);

        //TODO: This needs error check
        return true;
    }

    public static function removeFight($matchup_id)
    {
        //Delete all fightodds
        $query = "DELETE FROM fightodds WHERE fight_id = ?";
        $params = [$matchup_id];
        DBTools::doParamQuery($query, $params);

        //Delete fight itself
        $query = "DELETE FROM fights WHERE id = ?";
        DBTools::doParamQuery($query, $params);

        //Delete alerts for the fight
        $query = "DELETE FROM alerts WHERE fight_id = ?";
        DBTools::doParamQuery($query, $params);

        //Delete props for the fight
        $query = "DELETE FROM lines_props WHERE matchup_id = ?";
        DBTools::doParamQuery($query, $params);

        //Delete tweet status
        $query = "DELETE FROM fight_twits WHERE fight_id = ?";
        DBTools::doParamQuery($query, $params);

        //Delete any flagged lines related to the fight
        $query = "DELETE FROM lines_flagged WHERE matchup_id = ?";
        DBTools::doParamQuery($query, $params);

        //Delete any metadata related to the matchup
        $query = "DELETE FROM matchups_metadata WHERE matchup_id = ?";
        DBTools::doParamQuery($query, $params);

        //TODO: This needs error check
        return true;
    }

    public static function getBestOddsForFight($a_iFightID)
    {
        //TODO: Possibly improve this one by splitting into two main subqueries fetchin the best odds?
        $sQuery = 'SELECT
                        MAX(co1.fighter1_odds) AS fighter1_odds,
                        MAX(co1.fighter2_odds) AS fighter2_odds
                    FROM
                        fightodds AS co1
                    WHERE
                        co1.date = (SELECT
                                MAX(co2.date) as maxdate
                            FROM
                                fightodds AS co2
                            WHERE
                                co2.bookie_id = co1.bookie_id AND co2.fight_id = ?)
                    AND co1.fight_id = ?
                    HAVING fighter1_odds IS NOT NULL AND fighter2_odds IS NOT NULL;';

        $aParams = array($a_iFightID, $a_iFightID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aFightOddsCol = array();

        while ($aFightOdds = mysqli_fetch_array($rResult)) {
            $aFightOddsCol[] = new FightOdds($a_iFightID, -1, $aFightOdds['fighter1_odds'], $aFightOdds['fighter2_odds'], '');
        }
        if (sizeof($aFightOddsCol) > 0) {
            return $aFightOddsCol[0];
        }
        return null;
    }

    /**
     * Get best odds for a fight and fighter
     *
     * TODO: What is the difference between this one and getBestOddsForFight? Can this one be replaced?
     *
     * @param int Fight ID
     * @param int Fighter number (1 or 2)
     * @return null|\FightOdds Odds
     */
    public static function getBestOddsForFightAndFighter($a_iFightID, $a_iFighter)
    {
        if ($a_iFighter < 1 || $a_iFighter > 2) {
            return null;
        }

        $sQuery = 'SELECT co1.fighter1_odds AS fighter1_odds, 
                          co1.fighter2_odds AS fighter2_odds, 
                          co1.bookie_id AS bookie_id
                    FROM fightodds co1, (SELECT bookie_id, max(date) AS date
                                        FROM fightodds 
                                        WHERE fight_id = ?
                                        GROUP BY bookie_id ) AS co2 
                    WHERE co1.bookie_id = co2.bookie_id 
                        AND co1.fight_id = ? 
                        AND co1.date = co2.date 
                    ORDER BY co1.fighter' . $a_iFighter . '_odds DESC LIMIT 1;';

        $aParams = array($a_iFightID, $a_iFightID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aFightOddsCol = array();

        while ($aFightOdds = mysqli_fetch_array($rResult)) {
            $aFightOddsCol[] = new FightOdds($a_iFightID, $aFightOdds['bookie_id'], $aFightOdds['fighter1_odds'], $aFightOdds['fighter2_odds'], '');
        }
        if (sizeof($aFightOddsCol) > 0) {
            return $aFightOddsCol[0];
        }
        return null;
    }

    /**
     * Updates an event in the database. Uses all fields in the Event-object
     */
    public static function updateEvent($a_oEvent)
    {
        $sQuery = 'UPDATE events
                    SET name = ?,
                        display = ?,
                        date = ?
                    WHERE id = ?';

        $aParams = array($a_oEvent->getName(), ($a_oEvent->isDisplayed() ? '1' : '0'), $a_oEvent->getDate(), $a_oEvent->getID());

        $bResult = DBTools::doParamQuery($sQuery, $aParams);

        if ($bResult == false) {
            return false;
        }

        return true;
    }

    public static function updateFight($a_oFight)
    {
        $sQuery = 'UPDATE fights
            SET event_id = ?
            WHERE id = ?';

        $aParams = array($a_oFight->getEventID(), $a_oFight->getID());

        $bResult = DBTools::doParamQuery($sQuery, $aParams);

        if ($bResult == false) {
            return false;
        }

        return true;
    }

    //Returns all fights for a fighter
    public static function getAllFightsForFighter($a_iFighterID)
    {
        $sQuery = 'SELECT e.date AS thedate, f.id, f1.name AS fighter1_name, f2.name AS fighter2_name, f.event_id, f1.id AS fighter1_id, f2.id AS fighter2_id,
                        LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10) AS is_future 
                    FROM fights f, fighters f1, fighters f2, events e
                    WHERE f.fighter1_id = f1.id
                    AND f.fighter2_id = f2.id
                    AND f2.id = ?
                    AND f.event_id = e.id 
                UNION
                    SELECT e.date AS thedate, f.id, f1.name AS fighter1_name, f2.name AS fighter2_name, f.event_id, f1.id AS fighter1_id, f2.id AS fighter2_id,
                        LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10) AS is_future 
                    FROM fights f, fighters f1, fighters f2, events e
                    WHERE f.fighter1_id = f1.id
                    AND f.fighter2_id = f2.id
                    AND f1.id = ?
                    AND f.event_id = e.id 
                    ORDER BY thedate DESC';

        $aParams = array($a_iFighterID, $a_iFighterID);
        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aFights = array();
        while ($aFight = mysqli_fetch_array($rResult)) {
            $oTempFight = new Fight($aFight['id'], $aFight['fighter1_name'], $aFight['fighter2_name'], $aFight['event_id']);
            $oTempFight->setFighterID(1, $aFight['fighter1_id']);
            $oTempFight->setFighterID(2, $aFight['fighter2_id']);
            $oTempFight->setIsFuture($aFight['is_future']);
            $aFights[] = $oTempFight;
        }

        return $aFights;
    }

    public static function setFightAsMainEvent($a_iFightID, $a_bIsMainEvent)
    {
        $sQuery = 'UPDATE fights f
                SET f.is_mainevent = ?
                WHERE f.id = ?';

        $aParams = array($a_bIsMainEvent, $a_iFightID);

        return DBTools::doParamQuery($sQuery, $aParams);
    }

    public static function searchEvent($a_sEvent, $a_bFutureEventsOnly = false)
    {
        $sExtraWhere = '';
        if ($a_bFutureEventsOnly == true) {
            $sExtraWhere = ' AND LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10) ';
        }

        $sQuery = ' SELECT DISTINCT a3.* FROM
                        ((SELECT e.id, e.date, e.name, e.display, 100 AS score
                        FROM events e
                        WHERE e.name LIKE ?
                                 ' . $sExtraWhere . '
                        ORDER BY e.date ASC) 
                    UNION
                        (SELECT e.id, e.date, e.name, e.display, MATCH(e.name) AGAINST (?) AS score  
                        FROM events e
                        WHERE MATCH(e.name) AGAINST (?) 
                                ' . $sExtraWhere . '
                        ORDER BY score DESC, e.date ASC)) a3 
                    GROUP BY a3.id, a3.date, a3.name ORDER BY a3.score DESC';

        $aParams = array('%' . $a_sEvent . '%', $a_sEvent, $a_sEvent);
        $rResult = DBTools::doParamQuery($sQuery, $aParams);
        $aEvents = array();
        while ($aEvent = mysqli_fetch_array($rResult)) {
            $aEvents[] = new Event($aEvent['id'], $aEvent['date'], $aEvent['name'], $aEvent['display']);
        }

        return $aEvents;
    }

    /**
     * Retrieve recent events
     *
     * @param int $a_iLimit Event limit (default 10)
     * @param int $a_iOffset Offset (default 0)
     * @return array List of events
     */
    public static function getRecentEvents($a_iLimit, $a_iOffset = 0)
    {
        $sQuery = 'SELECT id, date, name, display
                    FROM events
                    WHERE LEFT(date, 10) < LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)
                    ORDER BY date DESC, name DESC LIMIT ' . $a_iOffset . ',' . $a_iLimit . '';

        $rResult = DBTools::doQuery($sQuery);
        $aEvents = array();
        while ($aEvent = mysqli_fetch_array($rResult)) {
            $aEvents[] = new Event($aEvent['id'], $aEvent['date'], $aEvent['name'], $aEvent['display']);
        }

        return $aEvents;
    }

    /**
     * Writes an entry to the log for unmatched entries from parsing
     */
    public static function logUnmatched($a_sMatchup, $a_iBookieID, $a_iType, $a_sMetadata = '')
    {
        $sQuery = 'INSERT INTO matchups_unmatched(matchup, bookie_id, type, metadata, log_date) VALUES (?,?,?,?, NOW()) ON DUPLICATE KEY UPDATE log_date = NOW()';
        $aParams = array($a_sMatchup, $a_iBookieID, $a_iType, $a_sMetadata);
        DBTools::doParamQuery($sQuery, $aParams);
        return DBTools::getAffectedRows();
    }

    /**
     * Retrieves all stored unmatched entries
     */
    public static function getUnmatched($limit = 10, $type = -1)
    {
        $params = [];
        $extra_where = '';
        if (in_array($type, [0,1,2])) {
            $extra_where = ' WHERE type = ? ';
            $params[] = $type;
        }
        $query = 'SELECT matchup, bookie_id, log_date, type, metadata
                    FROM matchups_unmatched 
                    ' . $extra_where . ' 
                    ORDER BY bookie_id ASC, log_date DESC
                    LIMIT 0, ' . $limit;

        $result = DBTools::doParamQuery($query, $params);
        $unmatched = array();
        while ($row = mysqli_fetch_array($result)) {
            $unmatched[] = $row;
        }
        return $unmatched;
    }

    /**
     * Clears all unmatched entries
     */
    public static function clearUnmatched($unmatched_item = null, $bookie_id = null)
    {
        $extra_where = '';
        $params = [];
        if ($unmatched_item != null && $bookie_id != null) {
            $extra_where = ' WHERE matchup = ? AND bookie_id = ? ';
            $params = [$unmatched_item, $bookie_id];
        }

        $query = 'DELETE FROM matchups_unmatched ' . $extra_where;
        DBTools::doParamQuery($query, $params);
        return DBTools::getAffectedRows();
    }

    /**
     * Gets the generic event for a specific date. The generic event is a default one that is used to store matchups that cannot be linked to a more specific event
     */
    public static function getGenericEventForDate($date)
    {
        //Genereic events for a date is always named after the date so a lookup is made based on that
        $query = 'SELECT id, date, name, display 
                    FROM events 
                    WHERE name = ?';

        $params = array($date);
        $result = DBTools::doParamQuery($query, $params);

        $events = array();
        while ($event = mysqli_fetch_array($result)) {
            $events[] = new Event($event['id'], $event['date'], $event['name'], $event['display']);
        }
        if (sizeof($events) > 0) {
            return $events[0];
        }
        return null;
    }

    public static function setMetaDataForMatchup($matchup_id, $attribute, $value, $bookie_id)
    {
        $query = 'INSERT INTO matchups_metadata(matchup_id, mattribute, mvalue, source_bookie_id) VALUES (?,?,?,?)
                        ON DUPLICATE KEY UPDATE mvalue = ?';
        $params = array($matchup_id, $attribute, $value, $bookie_id, $value);

        return DBTools::doParamQuery($query, $params);
    }

    public static function getLatestChangeDate($event_id)
    {
        $query = 'SELECT thedate FROM (SELECT fo.date as thedate 
                    FROM fightodds fo 
                        LEFT JOIN fights f ON fo.fight_id = f.id 
                    WHERE f.event_id = ?
                    ORDER BY fo.date DESC LIMIT 0,1) AS fot UNION SELECT * FROM 
                    (SELECT lp.date as thedate 
                    FROM lines_props lp
                        LEFT JOIN fights f ON lp.matchup_id = f.id 
                    WHERE f.event_id = ?
                    ORDER BY lp.date DESC LIMIT 0,1) AS lpt 
                    UNION SELECT * FROM (SELECT lep.date as thedate 
                        FROM lines_eventprops lep
                        WHERE lep.event_id = ?
                        ORDER BY lep.date DESC LIMIT 0,1) AS lept
                    ORDER BY thedate DESC LIMIT 0,1;';

        $params = array($event_id, $event_id, $event_id);

        $result = DBTools::doParamQuery($query, $params);
        return DBTools::getSingleValue($result);
    }

    public static function getAllEventsWithMatchupsWithoutResults()
    {
        $query = 'SELECT DISTINCT e.* FROM events e INNER JOIN fights f ON e.id = f.event_id LEFT JOIN matchups_results mr ON mr.matchup_id = f.id WHERE mr.matchup_id IS NULL
                        AND LEFT(e.date, 10) < LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)';

        $result = DBTools::doQuery($query);
        $events = array();
        while ($row = mysqli_fetch_array($result)) {
            $events[] = new Event($row['id'], $row['date'], $row['name'], $row['display']);
        }
        return $events;
    }

    public static function addMatchupResults($a_aParams)
    {
        if (!isset($a_aParams['matchup_id'], $a_aParams['winner'], $a_aParams['source'])) {
            return false;
        }
        $aQueryParams[] = $a_aParams['matchup_id'];
        $aQueryParams[] = $a_aParams['winner'];
        $aQueryParams[] = isset($a_aParams['method']) ? $a_aParams['method'] : '';
        $aQueryParams[] = isset($a_aParams['endround']) ? $a_aParams['endround'] : -1;
        $aQueryParams[] = isset($a_aParams['endtime']) ? $a_aParams['endtime'] : '';
        $aQueryParams[] = $a_aParams['source'];

        $sQuery = 'REPLACE INTO matchups_results(matchup_id, winner, method, endround, endtime, source) 
                    VALUES (?,?,?,?,?, ?)';

        $bResult = DBTools::doParamQuery($sQuery, $aQueryParams);
        if ($bResult == false) {
            return false;
        }
        return true;
    }

    public static function getResultsForMatchup($matchup_id)
    {
        $query = 'SELECT matchup_id, winner, method, endround, endtime FROM matchups_results WHERE matchup_id = ?';
        $result = DBTools::doParamQuery($query, [$matchup_id]);

        if ($return_arr = mysqli_fetch_array($result)) {
            return $return_arr;
        }
        return null;
    }
}
