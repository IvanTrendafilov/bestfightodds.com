<?php

namespace BFO\DB;

use BFO\Utils\DB\DBTools;
use BFO\Utils\DB\PDOTools;
use BFO\Utils\OddsTools;
use BFO\DataTypes\Event;
use BFO\DataTypes\Fight;

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
                $found_events[] = new Event((int) $row['id'], $row['date'], $row['name'], (bool) $row['display']);
            }
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        return $found_events;
    }

    public static function getMatchups($future_matchups_only = false, $only_with_odds = false, $event_id = null, $matchup_id = null, $only_without_odds = false, $team_id = null, $create_source = null): array
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
            $params = [
                ':team1_id' => $team_id, ':team2_id' => $team_id,
                ':metadata_team1_id' =>  $team_id, ':metadata_team2_id' => $team_id
            ];
        }

        //0 = Unspecified, 1 = Automatic, 2 = Manual
        if ($create_source) {
            $extra_where .= ' AND mca.source = :mca_source ';
            $params = [':mca_source' => $create_source];
        }

        $sorting = 'ORDER BY f.is_mainevent DESC, gametime DESC, latest_date ASC';

        if ($team_id) { //Alternative sorting when fetching a teams matchups
            $sorting = 'ORDER BY is_future DESC, gametime DESC, latest_date DESC, id DESC';
        }

        $query = 'SELECT f.id, f1.name AS fighter1_name, f2.name AS fighter2_name, f.event_id, f1.id AS fighter1_id, f2.id AS fighter2_id, f.is_mainevent as is_mainevent, 
                        (SELECT MIN(date) FROM fightodds fo WHERE fo.fight_id = f.id) AS latest_date, m.mvalue as gametime, m.max_value as max_gametime, m.min_value as min_gametime,
                        LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10) AS is_future, mca.source AS create_source
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
                        LEFT JOIN matchups_createaudit mca ON f.id = mca.matchup_id 
                    WHERE 1=1 ' . $extra_where . '
                    ' . ($future_matchups_only ? ' AND LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10) ' : '') . '
                    ' . ($only_with_odds ? ' HAVING latest_date IS NOT NULL ' : '') . '
                    ' . ($only_without_odds ? ' HAVING latest_date IS NULL ' : '') . '
                        AND f.id IS NOT NULL ' . $sorting;


        $matchups = [];
        try {
            foreach (PDOTools::findMany($query, $params) as $row) {
                $fight_obj = new Fight((int) $row['id'], $row['fighter1_name'], $row['fighter2_name'], (int) $row['event_id']);
                $fight_obj->setFighterID(1, (int) $row['fighter1_id']);
                $fight_obj->setFighterID(2, (int) $row['fighter2_id']);
                $fight_obj->setMainEvent((bool) $row['is_mainevent']);
                $fight_obj->setIsFuture((bool) $row['is_future']);
                if (isset($row['gametime']) && is_numeric($row['gametime'])) {
                    $fight_obj->setMetadata('gametime', $row['gametime']);
                }
                if (isset($row['max_gametime']) && is_numeric($row['max_gametime'])) {
                    $fight_obj->setMetadata('max_gametime', $row['max_gametime']);
                }
                if (isset($row['min_gametime']) && is_numeric($row['min_gametime'])) {
                    $fight_obj->setMetadata('min_gametime', $row['min_gametime']);
                }

                if (isset($row['create_source'])) { //0 = Unspecified, 1 = From Sportsbook, 2 = Manual/Scheduler
                    $fight_obj->setCreateSource((int) $row['create_source']);
                }
                $matchups[] = $fight_obj;
            }
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        return $matchups;
    }

    public static function getMatchingMatchup(string $team1_name, string $team2_name, bool $future_only = false, bool $past_only = false, int $known_fighter_id = null, string $event_date = null, int $event_id = null): ?Fight
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
            $fight_obj = new Fight((int) $row['id'], $row['fighter1_name'], $row['fighter2_name'], (int) $row['event_id']);
            if ($row['fighter1_name'] > $row['fighter2_name']) {
                $fight_obj->setExternalOrderChanged(true);
            }

            if (OddsTools::compareNames($fight_obj->getTeam(($team1_name >= $team2_name ? 2 : 1)), $team1_name) > 82) {
                if (OddsTools::compareNames($fight_obj->getTeam(($team1_name >= $team2_name ? 1 : 2)), $team2_name) > 82) {
                    //Found a match
                    $matchup = EventDB::getMatchups(matchup_id: $row['id']);
                    $found_matchup = $matchup[0] ?? null;

                    if ($row['original'] == '0') { //Matched on altname
                        //Check if fight is ordered lexographically in the database. The reason for this check is to correct
                        //when we match on a matchup where altnames are used and the order may change when creating the Fight object
                        $is_ordered_in_db = EventDB::isFightOrderedInDatabase((int) $row['id']);
                        if ($is_ordered_in_db && $fight_obj->hasExternalOrderChanged()) {
                            $found_matchup->setExternalOrderChanged(true);
                        } else if (!$is_ordered_in_db && !$fight_obj->hasExternalOrderChanged()) {
                            $found_matchup->setExternalOrderChanged(true);
                        }
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
    public static function isFightOrderedInDatabase(int $fight_id): ?bool
    {
        $query = 'SELECT f1.name < f2.name AS ordered
                    FROM fights f 
                        LEFT JOIN fighters f1 ON f.fighter1_id = f1.id
                        LEFT JOIN fighters f2 ON f.fighter2_id = f2.id 
                    WHERE f.id = ?
                    LIMIT 0,1';

        $params = [$fight_id];

        try {
            $row = PDOTools::findOne($query, $params);
            if ($row) {
                return (bool) $row->ordered;
            }
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }
        return null;
    }

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

    public static function addCreateAudit(int $matchup_id, int $source): ?bool
    {
        $query = 'INSERT INTO matchups_createaudit VALUES (?,?) ON DUPLICATE KEY UPDATE source = ?';

        $params = [$matchup_id, $source, $source];
        try {
            $id = PDOTools::insert($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
            return false;
        }
        return true;
    }

    public static function removeEvent(int $event_id): bool
    {
        $query = "DELETE FROM events WHERE id = ?";
        $params = [$event_id];
        try {
            $result = PDOTools::delete($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }
        return true;
    }

    public static function removeMatchup(int $matchup_id): bool
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

        return true;
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

    public static function setFightAsMainEvent(int $matchup_id, bool $is_main_event)
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
        $events = [];
        while ($row = mysqli_fetch_array($result)) {
            $events[] = new Event((int) $row['id'], $row['date'], $row['name'], (bool) $row['display']);
        }

        return $events;
    }

    public static function getRecentEvents(int $limit, int $offset = 0): array
    {
        $query = 'SELECT id, date, name, display
                    FROM events
                    WHERE LEFT(date, 10) < LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)
                    ORDER BY date DESC, name DESC LIMIT ' . $offset . ',' . $limit . '';

        try {
            foreach (PDOTools::findMany($query) as $row) {
                $events[] = new Event((int) $row['id'], $row['date'], $row['name'], (bool) $row['display']);
            }
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        return $events;
    }

    /**
     * Writes an entry to the log for unmatched entries from parsing
     */
    public static function logUnmatched(string $matchup_string, int $bookie_id, int $type, string $metadata = ''): int
    {
        $query = 'INSERT INTO matchups_unmatched(matchup, bookie_id, type, metadata, log_date) VALUES (?,?,?,?, NOW()) ON DUPLICATE KEY UPDATE log_date = NOW(), metadata = ?';
        $params = [$matchup_string, $bookie_id, $type, $metadata, $metadata];
        DBTools::doParamQuery($query, $params);
        return DBTools::getAffectedRows();
    }

    /**
     * Retrieves all stored unmatched entries
     * 
     * Type: 0 = Matchup , 1 = Prop not matched to matchup, 2 = Prop not matched to template
     */
    public static function getUnmatched(int $limit = 10, int $type = -1): array
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
        $unmatched = [];
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

    public static function setMetaDataForMatchup(int $matchup_id, string $attribute, string $value, int $bookie_id)
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

    public static function getOldEventsWithoutOdds(): array
    {
        $query = 'SELECT * 
                    FROM events e 
                    WHERE NOT EXISTS
                                (SELECT null 
                                    FROM fights f
                                    WHERE f.event_id = e.id)
                    AND LEFT(e.date, 10) < LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10);';

        $found_events = [];
        try {
            foreach (PDOTools::findMany($query, []) as $row) {
                $found_events[] = new Event((int) $row['id'], $row['date'], $row['name'], (bool) $row['display']);
            }
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        return $found_events;
    }
}
