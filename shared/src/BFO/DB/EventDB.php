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
    public static function getEvents(bool $future_events_only = null, int $event_id = null, string $event_name = null, string $event_date = null): array
    {
        $extra_where = '';
        $params = [];
        if ($future_events_only) {
            $extra_where .= ' AND LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10) ';
        }
        if ($event_id) {
            $extra_where .= ' AND e.id = ? ';
            $params[] = $event_id;
        }
        if ($event_name) {
            $extra_where .= ' AND e.name = ? ';
            $params[] = $event_name;
        }

        if ($event_date) {
            $extra_where .= ' AND e.date = ? ';
            $params[] = $event_date;
        }

        $query = 'SELECT e.id, e.date, e.name, e.display
                    FROM events e
                        WHERE 1=1 ' . $extra_where . ' 
                        AND e.id IS NOT NULL
                    ORDER BY e.date ASC, LEFT(e.name,3) = "UFC" DESC, LEFT(e.name,8) = "Bellator" DESC, e.name ASC;';

        $found_events = [];
        try {
            foreach (PDOTools::findMany($query, $params) as $row) {
                $found_events[] = new Event((int) $row['id'], $row['date'], $row['name'], $row['display']);
            }
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        return $found_events;
    }

    public static function getMatchups($future_matchups_only = false, $only_with_odds = false, $event_id = null, $matchup_id = null, $only_without_odds = false, $team_id = null): array
    {
        $extra_where = '';
        $extra_where_metadata = '';
        $params = [];

        if ($matchup_id) {
            $extra_where .= ' AND f.id = :matchup_id ';
            $extra_where_metadata .= ' AND fm.id = :metadata_matchup_id ';
            $params = [':matchup_id' => $matchup_id, ':metadata_matchup_id' => $matchup_id];
        }

        if ($event_id) {
            $extra_where .= ' AND e.id = :event_id ';
            $extra_where_metadata .= ' AND em.id = :metadata_event_id ';
            $params = [':event_id' => $event_id, ':metadata_event_id' => $event_id];
        }

        if ($team_id) {
            $extra_where .= ' AND (fighter1_id = :team1_id OR fighter2_id = :team2_id) ';
            $extra_where_metadata .= ' AND (fighter1_id = :metadata_team1_id OR fighter2_id = :metadata_team2_id) ';
            $params = [':team1_id' => $team_id, ':team2_id' => $team_id,
                        ':metadata_team1_id' =>  $team_id, ':metadata_team2_id' => $team_id];
        }

        $query = 'SELECT f.id, f1.name AS fighter1_name, f2.name AS fighter2_name, f.event_id, f1.id AS fighter1_id, f2.id AS fighter2_id, f.is_mainevent as is_mainevent, 
                        (SELECT MIN(date) FROM fightodds fo WHERE fo.fight_id = f.id) AS latest_date, m.mvalue as gametime, m.max_value as max_gametime, m.min_value as min_gametime,
                        LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10) AS is_future 
                    FROM 
                        events e
                        LEFT JOIN fights f ON e.id = f.event_id 
                        LEFT JOIN 
                            (SELECT matchup_id, ROUND(AVG(mvalue)) as mvalue, MAX(mvalue) as max_value, MIN(mvalue) as min_value 
                                FROM events em 
                                    LEFT JOIN fights fm ON em.id = fm.event_id 
                                    LEFT JOIN matchups_metadata mm ON fm.id = mm.matchup_id 
                                WHERE mm.mattribute = "gametime" 
                                    ' . $extra_where_metadata . '
                                GROUP BY matchup_id) m ON f.id = m.matchup_id
                        LEFT JOIN fighters f1 ON f1.id = f.fighter1_id
                        LEFT JOIN fighters f2 ON f2.id = f.fighter2_id
                    WHERE 1=1 ' . $extra_where . '
                    ' . ($future_matchups_only ? ' AND LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10) ' : '') . '
                    ' . ($only_with_odds ? ' HAVING latest_date IS NOT NULL ' : '') . '
                    ' . ($only_without_odds ? ' HAVING latest_date IS NULL ' : '') . '
                        AND f.id IS NOT NULL
                    ORDER BY f.is_mainevent DESC, gametime DESC, latest_date ASC';

        $matchups = [];
        try {
            foreach (PDOTools::findMany($query, $params) as $row) {
                $fight_obj = new Fight((int) $row['id'], $row['fighter1_name'], $row['fighter2_name'], (int) $row['event_id']);
                $fight_obj->setFighterID(1, (int) $row['fighter1_id']);
                $fight_obj->setFighterID(2, (int) $row['fighter2_id']);
                $fight_obj->setMainEvent($row['is_mainevent']);
                $fight_obj->setIsFuture($row['is_future']);
                if (isset($row['gametime']) && is_numeric($row['gametime'])) {
                    $fight_obj->setMetadata('gametime', $row['gametime']);
                }
                if (isset($row['max_gametime']) && is_numeric($row['max_gametime'])) {
                    $fight_obj->setMetadata('max_gametime', $row['max_gametime']);
                }
                if (isset($row['min_gametime']) && is_numeric($row['min_gametime'])) {
                    $fight_obj->setMetadata('min_gametime', $row['min_gametime']);
                }
                $matchups[] = $fight_obj;
            }
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        return $matchups;
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

    public static function getLatestOddsForFightAndBookie($matchup_id, $bookie_id)
    {
        $query = 'SELECT fight_id, fighter1_odds, fighter2_odds, bookie_id, date
                    FROM fightodds
                    WHERE bookie_id = ? 
                        AND fight_id = ? 
                    ORDER BY date DESC
                    LIMIT 0,1';

        $params = array($bookie_id, $matchup_id);

        $result = DBTools::doParamQuery($query, $params);

        $odds_col = [];
        while ($row = mysqli_fetch_array($result)) {
            $odds_col[] = new FightOdds($row['fight_id'], $row['bookie_id'], $row['fighter1_odds'], $row['fighter2_odds'], $row['date']);
        }
        if (sizeof($odds_col) > 0) {
            return $odds_col[0];
        }
        return null;
    }

    public static function getAllOdds(int $matchup_id, int $bookie_id = null): ?array
    {
        $extra_where = '';
        $params = [':matchup_id' => $matchup_id];
        if ($bookie_id) {
            $params[':bookie_id'] = $bookie_id;
            $extra_where = ' AND bookie_id = :bookie_id ';
        }

        $query = 'SELECT fight_id, fighter1_odds, fighter2_odds, bookie_id, date
                    FROM fightodds
                    WHERE fight_id = :matchup_id 
                    ' . $extra_where . '
                    ORDER BY date ASC';

        $odds_col = [];
        try {
            foreach (PDOTools::findMany($query, $params) as $row) {
                $odds_col[] = new FightOdds($row['fight_id'], $row['bookie_id'], $row['fighter1_odds'], $row['fighter2_odds'], $row['date']);
            }
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }
        return $odds_col;
    }


    public static function getMatchingFight(string $team1_name, string $team2_name, bool $future_only = false, bool $past_only = false, int $known_fighter_id = null, string $event_date = null, int $event_id = null): ?Fight
    {
        $extra_where = '';
        $aQueryParams = [];
        if ($future_only) {
            $extra_where .= ' AND LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)';
        }
        if ($past_only) {
            $extra_where .= ' AND LEFT(e.date, 10) < LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)';
        }
        if ($event_id) {
            $extra_where .= ' AND event_id = ' . DBTools::makeParamSafe($event_id) . '';
        }
        if ($known_fighter_id) {
            $extra_where .= ' AND (fighter1_id = ' . DBTools::makeParamSafe($known_fighter_id) . ' OR fighter2_id = ' . DBTools::makeParamSafe($known_fighter_id) . ')';
        }
        if ($event_date) {
            $extra_where .= ' AND LEFT(e.date, 10) <= DATE_ADD(\'' . DBTools::makeParamSafe($event_date) . '\', INTERVAL +1 DAY) AND LEFT(e.date, 10) >= DATE_ADD(\'' . DBTools::makeParamSafe($event_date) . '\', INTERVAL -1 DAY)';
        }

        $query = 'SELECT 1 AS original, t.id, a.name AS fighter1_name, a.id as fighter1_id, b.name AS fighter2_name, b.id as fighter2_id, t.event_id
                      FROM events e, fights t
                          JOIN fighters a ON a.id = t.fighter1_id
                          JOIN fighters b ON b.id = t.fighter2_id WHERE e.id = event_id ' . $extra_where . '
                    UNION SELECT 0 AS original, t.id, a.altname, a.fighter_id, b.altname, b.fighter_id, t.event_id
                      FROM events e, fights t
                          JOIN fighters_altnames a ON a.fighter_id = fighter1_id
                          JOIN fighters_altnames b ON b.fighter_id = fighter2_id WHERE e.id = event_id ' . $extra_where . '
                    UNION SELECT 0 AS original, t.id, a.name, a.id, b.altname, b.fighter_id, t.event_id
                      FROM events e, fights t
                          JOIN fighters a ON a.id = t.fighter1_id
                          JOIN fighters_altnames b ON b.fighter_id = fighter2_id WHERE e.id = event_id ' . $extra_where . '
                    UNION SELECT 0 AS original, t.id, a.altname, a.fighter_id, b.name, b.id, t.event_id
                      FROM events e, fights t
                          JOIN fighters b ON b.id = fighter2_id
                          JOIN fighters_altnames a ON a.fighter_id = fighter1_id WHERE e.id = event_id ' . $extra_where . ' ';

        $result = DBTools::getCachedQuery($query);
        if ($result == null) {
            $result = DBTools::doQuery($query);
            DBTools::cacheQueryResults($query, $result);
        }

        while ($row = mysqli_fetch_array($result)) {
            $fight_obj = new Fight($row['id'], $row['fighter1_name'], $row['fighter2_name'], $row['event_id']);
            if ($row['fighter1_name'] > $row['fighter2_name']) {
                $fight_obj->setComment('switched');
            }

            if (OddsTools::compareNames($fight_obj->getTeam(($team1_name >= $team2_name ? 2 : 1)), $team1_name) > 82) {
                if (OddsTools::compareNames($fight_obj->getTeam(($team1_name >= $team2_name ? 1 : 2)), $team2_name) > 82) {
                    $found_matchup = null;
                    if ($row['original'] == '0') {
                        $matchup = EventDB::getMatchups(matchup_id: $row['id']);
                        $found_matchup = $matchup[0] ?? null;

                        $is_ordered_in_db = EventDB::isFightOrderedInDatabase($row['id']);
                        if ($is_ordered_in_db == true) {
                            if ($fight_obj->getComment() == 'switched') {
                                $found_matchup->setComment('switched');
                            }
                        } else {
                            if ($fight_obj->getComment() != 'switched') {
                                $found_matchup->setComment('switched');
                            }
                        }
                    } else {
                        $found_matchup = new Fight($row['id'], $row['fighter1_name'], $row['fighter2_name'], $row['event_id']);
                        $found_matchup->setFighterID(1, $row['fighter1_id']);
                        $found_matchup->setFighterID(2, $row['fighter2_id']);
                    }
                    return $found_matchup;
                }
            }
        }
        return null;
    }

    /**
     * Check if a fight is stored not lexiographically order in the database.
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

        $result = DBTools::doParamQuery($query, $params);

        $row = mysqli_fetch_array($result);
        if (sizeof($row) > 0) {
            if ($row['fighter1_name'] > $row['fighter2_name']) {
                return false;
            } else {
                return true;
            }
        }
        return null;
    }

    public static function addNewFightOdds(FightOdds $fightodds_obj) : ?int
    {
        //TODO: This query should be updated to check for valid value from fights and bookie table
        /*$query = 'INSERT INTO fightodds(fight_id, fighter1_odds, fighter2_odds, bookie_id, date)
                        VALUES(?, ?, ?, ?, NOW())';*/

        $query = 'INSERT INTO fightodds(fight_id, fighter1_odds, fighter2_odds, bookie_id, date)
                    SELECT f.id, ?, ?, b.id, NOW()
                        FROM fights f, bookies b
                        WHERE f.id = ? AND b.id = ?';

        $params = [$fightodds_obj->getFighterOdds(1), $fightodds_obj->getFighterOdds(2), $fightodds_obj->getFightID(), $fightodds_obj->getBookieID()];

        try {
            $id = PDOTools::executeQuery($query, $params);
            return $id->rowCount();

        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Duplicate entry", 10);
            } else {
                throw new \Exception("Unknown error " . $e->getMessage(), 10);
            }
        }
        return null;
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



    /*public static function addNewFight($fight_obj)
    {
        //Check that event is ok
        if (EventDB::getEvents(false, $fight_obj->getEventID()) != null) {
            //Check if fight isn't already added
            if (!EventDB::getMatchingFight(team1_name: $fight_obj->getFighter(1), team2_name: $fight_obj->getFighter(2), event_id: $fight_obj->getEventID(), future_only: true)) {
                //Check that both fighters exist, if not, add them
                $fighter1_id = EventDB::getFighterIDByName($fight_obj->getFighter(1));
                if ($fighter1_id == null) {
                    $fighter1_id = EventDB::addNewFighter($fight_obj->getFighter(1));
                }
                $fighter2_id = EventDB::getFighterIDByName($fight_obj->getFighter(2));
                if ($fighter2_id == null) {
                    $fighter2_id = EventDB::addNewFighter($fight_obj->getFighter(2));
                }

                if ($fighter1_id == null || $fighter2_id == null) {
                    return false;
                }

                $query = 'INSERT INTO fights(fighter1_id, fighter2_id, event_id)
                                VALUES(?, ?, ?)';

                $params = array($fighter1_id, $fighter2_id, $fight_obj->getEventID());
                DBTools::doParamQuery($query, $params);

                //Invalidate cache whenever we add a matchup in case some running function is caching matchups
                DBTools::invalidateCache();

                return DBTools::getLatestID();
            }
        }
        return false;
    }*/

    public static function createMatchup(int $team1_id, int $team2_id, int $event_id): ?int
    {
        $query = 'INSERT INTO fights(fighter1_id, fighter2_id, event_id)
                    SELECT f1.id, f2.id, e.id
                        FROM fighters f1, fighters f2, events e
                        WHERE f1.id = ? AND f2.id = ? AND e.id = ?';

        $params = [$team1_id, $team2_id, $event_id];
        try {
            $id = PDOTools::insert($query, $params);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Duplicate entry", 10);
            } else {
                throw new \Exception("Unknown error " . $e->getMessage(), 10);
            }
            return null;
        }
        //Invalidate cache whenever we add a matchup in case some running function is caching matchups
        DBTools::invalidateCache();
        return $id;
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
        try {
            $result = PDOTools::delete($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        //Delete fight itself
        $query = "DELETE FROM fights WHERE id = ?";
        try {
            $result = PDOTools::delete($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        //Delete alerts for the fight
        $query = "DELETE FROM alerts WHERE fight_id = ?";
        try {
            $result = PDOTools::delete($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        //Delete props for the fight
        $query = "DELETE FROM lines_props WHERE matchup_id = ?";
        try {
            $result = PDOTools::delete($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        //Delete tweet status
        $query = "DELETE FROM fight_twits WHERE fight_id = ?";
        try {
            $result = PDOTools::delete($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        //Delete any flagged lines related to the fight
        $query = "DELETE FROM lines_flagged WHERE matchup_id = ?";
        try {
            $result = PDOTools::delete($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        //Delete any metadata related to the matchup
        $query = "DELETE FROM matchups_metadata WHERE matchup_id = ?";
        try {
            $result = PDOTools::delete($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        //TODO: This needs error check
        return true;
    }

    public static function getBestOddsForFight($matchup_id)
    {
        //TODO: Possibly improve this one by splitting into two main subqueries fetchin the best odds?
        $query = 'SELECT
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

        $params = array($matchup_id, $matchup_id);

        $rResult = DBTools::doParamQuery($query, $params);

        $odds_col = array();

        while ($row = mysqli_fetch_array($rResult)) {
            $odds_col[] = new FightOdds($matchup_id, -1, $row['fighter1_odds'], $row['fighter2_odds'], '');
        }
        if (sizeof($odds_col) > 0) {
            return $odds_col[0];
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
    public static function getBestOddsForFightAndFighter($matchup_id, $team_num)
    {
        if ($team_num < 1 || $team_num > 2) {
            return null;
        }

        $query = 'SELECT co1.fighter1_odds AS fighter1_odds, 
                          co1.fighter2_odds AS fighter2_odds, 
                          co1.bookie_id AS bookie_id
                    FROM fightodds co1, (SELECT bookie_id, max(date) AS date
                                        FROM fightodds 
                                        WHERE fight_id = ?
                                        GROUP BY bookie_id ) AS co2 
                    WHERE co1.bookie_id = co2.bookie_id 
                        AND co1.fight_id = ? 
                        AND co1.date = co2.date 
                    ORDER BY co1.fighter' . $team_num . '_odds DESC LIMIT 1;';

        $params = array($matchup_id, $matchup_id);

        $rResult = DBTools::doParamQuery($query, $params);

        $odds_col = array();

        while ($row = mysqli_fetch_array($rResult)) {
            $odds_col[] = new FightOdds($matchup_id, $row['bookie_id'], $row['fighter1_odds'], $row['fighter2_odds'], '');
        }
        if (sizeof($odds_col) > 0) {
            return $odds_col[0];
        }
        return null;
    }

    /**
     * Updates an event in the database. Uses all fields in the Event-object
     */
    public static function updateEvent($event_obj)
    {
        $query = 'UPDATE events
                    SET name = ?,
                        display = ?,
                        date = ?
                    WHERE id = ?';

        $params = array($event_obj->getName(), ($event_obj->isDisplayed() ? '1' : '0'), $event_obj->getDate(), $event_obj->getID());

        $result = DBTools::doParamQuery($query, $params);

        if ($result == false) {
            return false;
        }

        return true;
    }

    public static function updateFight($fight_obj)
    {
        $query = 'UPDATE fights
            SET event_id = ?
            WHERE id = ?';

        $params = array($fight_obj->getEventID(), $fight_obj->getID());

        $result = DBTools::doParamQuery($query, $params);
        if ($result == false) {
            return false;
        }
        return true;
    }

    public static function setFightAsMainEvent($matchup_id, $is_main_event)
    {
        $query = 'UPDATE fights f
                SET f.is_mainevent = ?
                WHERE f.id = ?';

        $params = [$is_main_event, $matchup_id];

        return DBTools::doParamQuery($query, $params);
    }

    public static function searchEvent($event_name, $future_events_only = false)
    {
        $extra_where = '';
        if ($future_events_only == true) {
            $extra_where .= ' AND LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10) ';
        }

        $query = ' SELECT DISTINCT a3.* FROM
                        ((SELECT e.id, e.date, e.name, e.display, 100 AS score
                        FROM events e
                        WHERE e.name LIKE ?
                                 ' . $extra_where . '
                        ORDER BY e.date ASC) 
                    UNION
                        (SELECT e.id, e.date, e.name, e.display, MATCH(e.name) AGAINST (?) AS score  
                        FROM events e
                        WHERE MATCH(e.name) AGAINST (?) 
                                ' . $extra_where . '
                        ORDER BY score DESC, e.date ASC)) a3 
                    GROUP BY a3.id, a3.date, a3.name ORDER BY a3.score DESC';

        $params = array('%' . $event_name . '%', $event_name, $event_name);
        $result = DBTools::doParamQuery($query, $params);
        $events = array();
        while ($row = mysqli_fetch_array($result)) {
            $events[] = new Event((int) $row['id'], $row['date'], $row['name'], $row['display']);
        }

        return $events;
    }

    /**
     * Retrieve recent events
     *
     * @param int $a_iLimit Event limit (default 10)
     * @param int $a_iOffset Offset (default 0)
     * @return array List of events
     */
    public static function getRecentEvents(int $limit, int $offset = 0): array
    {
        $query = 'SELECT id, date, name, display
                    FROM events
                    WHERE LEFT(date, 10) < LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)
                    ORDER BY date DESC, name DESC LIMIT ' . $offset . ',' . $limit . '';

        try {
            foreach (PDOTools::findMany($query) as $row) {
                $events[] = new Event((int) $row['id'], $row['date'], $row['name'], $row['display']);
            }
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        return $events;
    }

    /**
     * Writes an entry to the log for unmatched entries from parsing
     */
    public static function logUnmatched($matchup_string, $bookie_id, $type, $metadata = '')
    {
        $query = 'INSERT INTO matchups_unmatched(matchup, bookie_id, type, metadata, log_date) VALUES (?,?,?,?, NOW()) ON DUPLICATE KEY UPDATE log_date = NOW()';
        $params = array($matchup_string, $bookie_id, $type, $metadata);
        DBTools::doParamQuery($query, $params);
        return DBTools::getAffectedRows();
    }

    /**
     * Retrieves all stored unmatched entries
     */
    public static function getUnmatched($limit = 10, $type = -1)
    {
        $params = [];
        $extra_where = '';
        if (in_array($type, [0, 1, 2])) {
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

    public static function setMetaDataForMatchup($matchup_id, $attribute, $value, $bookie_id)
    {
        $query = 'INSERT INTO matchups_metadata(matchup_id, mattribute, mvalue, source_bookie_id) VALUES (?,?,?,?)
                        ON DUPLICATE KEY UPDATE mvalue = ?';
        $params = array($matchup_id, $attribute, $value, $bookie_id, $value);

        return DBTools::doParamQuery($query, $params);
    }

    public static function getMetaDataForMatchup(int $matchup_id, string $metadata_attribute = null, int $bookie_id = null): array
    {
        $extra_where = '';
        $params = [$matchup_id];
        if ($metadata_attribute) {
            $extra_where .= ' AND mattribute = ? ';
            $params[] = $metadata_attribute;
        }
        if ($bookie_id) {
            $extra_where .= ' AND source_bookie_id = ? ';
            $params[] = $bookie_id;
        }

        $query = 'SELECT * 
                    FROM matchups_metadata 
                    WHERE matchup_id = ? ' . $extra_where . '
                    ORDER BY source_bookie_id ASC';

        return PDOTools::findMany($query, $params);
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
            $events[] = new Event((int) $row['id'], $row['date'], $row['name'], $row['display']);
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

    public static function deleteAllOldEventsWithoutOdds(): int
    {
        $query = 'DELETE
                    FROM events e 
                    WHERE NOT EXISTS
                                (SELECT null 
                                    FROM fights f
                                    WHERE f.event_id = e.id)
                    AND LEFT(e.date, 10) < LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10);';

        $rows = 0;
        try {
            $rows = PDOTools::delete($query, []);
        } catch (\PDOException $e) {
            throw new \Exception("Unable to delete old entries", 10);
        }
        return $rows;
    }
}
