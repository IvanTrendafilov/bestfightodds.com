<?php

namespace BFO\DB;

use BFO\Utils\DB\DBTools;
use BFO\Utils\DB\PDOTools;

use BFO\DataTypes\FightOdds;
use BFO\DataTypes\PropBet;
use BFO\DataTypes\EventPropBet;
use Exception;

class OddsDB
{
    public static function addPropBet(PropBet $propbet_obj): bool
    {
        $query = 'INSERT IGNORE INTO lines_props(matchup_id, bookie_id, prop_odds, negprop_odds, proptype_id, date, team_num)
                    VALUES(?, ?, ?, ?, ?, NOW(), ?)';

        $params = array(
            $propbet_obj->getMatchupID(),
            $propbet_obj->getBookieID(),
            $propbet_obj->getPropOdds(),
            $propbet_obj->getNegPropOdds(),
            $propbet_obj->getPropTypeID(),
            $propbet_obj->getTeamNumber()
        );

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

    public static function addEventPropBet(EventPropBet $event_propbet_obj)
    {
        $query = 'INSERT IGNORE INTO lines_eventprops(event_id, bookie_id, prop_odds, negprop_odds, proptype_id, date)
                    VALUES(?, ?, ?, ?, ?, NOW())';

        $params = array(
            $event_propbet_obj->getEventID(),
            $event_propbet_obj->getBookieID(),
            $event_propbet_obj->getPropOdds(),
            $event_propbet_obj->getNegPropOdds(),
            $event_propbet_obj->getPropTypeID()
        );

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

    public static function getLatestPropOdds(int $matchup_id, int $bookie_id, int $proptype_id, int $team_num, int $offset = 0): ?PropBet
    {
        $params = [$matchup_id, $bookie_id, $proptype_id, $team_num];

        if (!is_integer($offset)) {
            return null;
        }

        $query = 'SELECT lp.bookie_id, lp.prop_odds, lp.negprop_odds, lp.proptype_id, lp.date, pt.prop_desc, pt.negprop_desc, lp.date, lp.team_num
                    FROM lines_props lp, prop_types pt
                    WHERE lp.matchup_id = ?
                        AND lp.bookie_id = ?
                        AND lp.proptype_id = ?
                        AND lp.proptype_id = pt.id
                        AND lp.team_num = ?
                        ORDER BY lp.date DESC
                        LIMIT ' . $offset . ', 1';

        $result = DBTools::doParamQuery($query, $params);

        $props = array();
        while ($aRow = mysqli_fetch_array($result)) {
            $props[] = new PropBet(
                $matchup_id,
                $aRow['bookie_id'],
                $aRow['prop_desc'],
                $aRow['prop_odds'],
                $aRow['negprop_desc'],
                $aRow['negprop_odds'],
                $aRow['proptype_id'],
                $aRow['date'],
                $aRow['team_num']
            );
        }
        if (count($props) > 0) {
            return $props[0];
        }

        return null;
    }

    public static function getLatestEventPropOdds(int $event_id, int $bookie_id, int $proptype_id, int $offset = 0): ?EventPropBet
    {
        $params = array($event_id, $bookie_id, $proptype_id);

        if (!is_integer($offset)) {
            return null;
        }

        $query = 'SELECT lep.bookie_id, lep.prop_odds, lep.negprop_odds, lep.proptype_id, lep.date, pt.prop_desc, pt.negprop_desc, lep.date
                    FROM lines_eventprops lep, prop_types pt
                    WHERE lep.event_id = ?
                        AND lep.bookie_id = ?
                        AND lep.proptype_id = ?
                        AND lep.proptype_id = pt.id
                        ORDER BY lep.date DESC
                        LIMIT ' . $offset . ', 1';

        $result = DBTools::doParamQuery($query, $params);

        $props = [];
        while ($row = mysqli_fetch_array($result)) {
            $props[] = new EventPropBet(
                (int) $event_id,
                (int) $row['bookie_id'],
                $row['prop_desc'],
                $row['prop_odds'],
                $row['negprop_desc'],
                $row['negprop_odds'],
                (int) $row['proptype_id'],
                $row['date']
            );
        }
        if (count($props) > 0) {
            return $props[0];
        }

        return null;
    }

    public static function getAllPropOddsForMatchupPropType(int $matchup_id, int $bookie_id, int $proptype_id, int $team_num): array
    {
        $params = [$matchup_id, $bookie_id, $proptype_id, $team_num];

        $query = 'SELECT lp.bookie_id, lp.prop_odds, lp.negprop_odds, lp.proptype_id, lp.date, pt.prop_desc, pt.negprop_desc, lp.date, lp.team_num
                    FROM lines_props lp, prop_types pt
                    WHERE lp.matchup_id = ?
                        AND lp.bookie_id = ?
                        AND lp.proptype_id = ?
                        AND lp.proptype_id = pt.id
                        AND lp.team_num = ?
                        ORDER BY lp.date ASC';

        $result = DBTools::doParamQuery($query, $params);

        $props = [];
        while ($row = mysqli_fetch_array($result)) {
            $props[] = new PropBet(
                $matchup_id,
                $row['bookie_id'],
                $row['prop_desc'],
                $row['prop_odds'],
                $row['negprop_desc'],
                $row['negprop_odds'],
                $row['proptype_id'],
                $row['date'],
                $row['team_num']
            );
        }

        return $props;
    }

    public static function getAllPropOddsForEventPropType(int $event_id, int $bookie_id, int $proptype_id): array
    {
        $params = [$event_id, $bookie_id, $proptype_id];

        $query = 'SELECT lep.bookie_id, lep.prop_odds, lep.negprop_odds, lep.proptype_id, lep.date, pt.prop_desc, pt.negprop_desc, lep.date
                    FROM lines_eventprops lep, prop_types pt
                    WHERE lep.event_id = ?
                        AND lep.bookie_id = ?
                        AND lep.proptype_id = ?
                        AND lep.proptype_id = pt.id
                        ORDER BY lep.date ASC';

        $result = DBTools::doParamQuery($query, $params);

        $props = [];
        while ($row = mysqli_fetch_array($result)) {
            $props[] = new EventPropBet(
                (int) $event_id,
                (int) $row['bookie_id'],
                $row['prop_desc'],
                $row['prop_odds'],
                $row['negprop_desc'],
                $row['negprop_odds'],
                (int) $row['proptype_id'],
                $row['date']
            );
        }

        return $props;
    }


    public static function getOpeningOddsForMatchup(int $matchup_id): ?FightOdds
    {
        $query = 'SELECT fight_id, fighter1_odds, fighter2_odds, bookie_id, date
                    FROM fightodds
                        WHERE fight_id = ? 
                    ORDER BY date ASC
                    LIMIT 0,1';

        $params = [$matchup_id];

        $result = DBTools::doParamQuery($query, $params);

        $odds_col = [];
        while ($row = mysqli_fetch_array($result)) {
            $odds_col[] = new FightOdds((int) $row['fight_id'], (int) $row['bookie_id'], $row['fighter1_odds'], $row['fighter2_odds'], $row['date']);
        }
        if (sizeof($odds_col) > 0) {
            return $odds_col[0];
        }
        return null;
    }

    public static function getCorrelationsForBookie(int $bookie_id): array
    {
        $params = [$bookie_id];

        $query = 'SELECT lc.correlation, lc.bookie_id, lc.matchup_id
                        FROM lines_correlations lc 
                        WHERE lc.bookie_id = ? ';

        $result = DBTools::doParamQuery($query, $params);

        $return = [];
        while ($row = mysqli_fetch_array($result)) {
            $return[] = array(
                'correlation' => $row['correlation'],
                'matchup_id' => (int) $row['matchup_id']
            );
        }
        return $return;
    }

    public static function getMatchupIDForCorrelation(int $bookie_id, string $correlation): ?int
    {
        $params = [$bookie_id, $correlation];

        $query = 'SELECT matchup_id 
                    FROM lines_correlations 
                    WHERE bookie_id = ? AND correlation = ?';

        $result = DBTools::doParamQuery($query, $params);

        return (int) DBTools::getSingleValue($result);
    }

    /**
     * Stores a collection of correlations
     *
     * Accepts an array of correlations defined as follows:
     *
     * array('correlation' => xxx, 'matchup_id' => xxx)
     */
    public static function storeCorrelations(int $bookie_id, array $correlations): bool
    {
        $params = [];

        $query = 'INSERT IGNORE INTO lines_correlations(correlation, bookie_id, matchup_id) VALUES ';
        foreach ($correlations as $correlation) {
            if (count($params) > 0) {
                $query .= ', ';
            }

            if (isset($correlation['correlation']) && isset($correlation['matchup_id'])) {
                $params[] = $correlation['correlation'];
                $params[] = $bookie_id;
                $params[] = $correlation['matchup_id'];

                $query .= '(?,?,?)';
            }
        }

        //Only execute if we are adding at least one entry
        if (count($params) > 0) {
            DBTools::doParamQuery($query, $params);
            return (DBTools::getAffectedRows() > 0 ? true : false);
        }
        return false;
    }

    /**
     * Cleans correlations by removing the ones that are not needed anymore. This is determined
     * by checking if the matchup it is associated with is in the past
     */
    public static function cleanCorrelations()
    {
        //Delete correlations that are for old events
        $query = 'DELETE lc.* 
                    FROM lines_correlations lc 
                        LEFT JOIN fights f ON lc.matchup_id = f.id 
                        LEFT JOIN events e ON f.event_id = e.id 
                    WHERE LEFT(e.date, 10) < LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)';

        DBTools::doQuery($query);
        $iCount = DBTools::getAffectedRows();

        //Delete orphan correlations that are related to matchups that have been removed
        $query = 'DELETE lc.*
                    FROM lines_correlations lc
                        LEFT JOIN fights f ON lc.matchup_id = f.id
                    WHERE
                        f.id IS NULL';
        DBTools::doQuery($query);

        $iCount += DBTools::getAffectedRows();

        return $iCount;
    }

    public static function getCompletePropsForEvent(int $event_id, int $offset = 0, int $bookie_id = null): ?array
    {
        if ($offset != 0 && $offset != 1) {
            return false;
        }

        $extra_query = '';
        $params = [$event_id, $event_id];
        if ($offset == 1) {
            $extra_query = ' AND lep4.date != (SELECT
                MAX(lep5.date)  FROM lines_eventprops lep5
            WHERE
                lep5.event_id = ? AND lep5.bookie_id =
                lep4.bookie_id AND lep5.proptype_id = lep4.proptype_id) ';
            $params[] = $event_id;
        }
        if ($bookie_id) {
            $extra_query .= ' AND bookie_id = ? ';
            $params[] = $bookie_id;
        }

        $query = 'SELECT
                    lep2.event_id,
                    lep2.bookie_id,
                    lep2.proptype_id,
                    lep2.date,
                    lep2.prop_odds,
                    lep2.negprop_odds,
                    pt.prop_desc, 
                    pt.negprop_desc
                FROM
                    bookies b, prop_types pt, lines_eventprops AS lep2,
                    (SELECT
                        MAX(lep4.date) as date, bookie_id, proptype_id
                    FROM
                        lines_eventprops lep4
                    WHERE
                        lep4.event_id = ? ' . $extra_query . ' 
                    GROUP BY bookie_id , proptype_id) AS lep3
                WHERE
                    lep2.event_id = ? AND lep2.bookie_id = lep3.bookie_id AND
                lep2.proptype_id = lep3.proptype_id AND
                lep2.date = lep3.date AND lep2.proptype_id = pt.id AND lep2.bookie_id = b.id ORDER BY b.position
                ;';

        $result = DBTools::doParamQuery($query, $params);

        $props = [];
        while ($row = mysqli_fetch_array($result)) {
            $props[] = new EventPropBet(
                (int) $event_id,
                (int) $row['bookie_id'],
                $row['prop_desc'],
                $row['prop_odds'],
                $row['negprop_desc'],
                $row['negprop_odds'],
                (int) $row['proptype_id'],
                $row['date']
            );
        }
        if (count($props) > 0) {
            return $props;
        }

        return null;
    }

    public static function getLatestChangeDate(int $event_id): string
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
        return (string) DBTools::getSingleValue($result);
    }

    public static function getBestOddsForFight(int $matchup_id): ?FightOdds
    {
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

        $result = DBTools::doParamQuery($query, $params);

        $odds_col = array();

        while ($row = mysqli_fetch_array($result)) {
            $odds_col[] = new FightOdds((int) $matchup_id, -1, $row['fighter1_odds'], $row['fighter2_odds'], '');
        }
        if (sizeof($odds_col) > 0) {
            return $odds_col[0];
        }
        return null;
    }

    public static function getAllLatestOddsForFight(int $matchup_id, int $offset = 0): array
    {
        if ($offset != 0 && $offset != 1) {
            return null;
        }

        $params = [$matchup_id, $matchup_id];
        $extra_query = '';

        if ($offset == 1) {
            $extra_query = ' AND fo4.date < (SELECT
                MAX(fo5.date) AS date
            FROM
                fightodds fo5
            WHERE
                fo5.fight_id = ? AND fo5.bookie_id = fo4.bookie_id) ';
            $params[] = $matchup_id;
        }

        $query = 'SELECT
            fo2.fight_id, fo2.fighter1_odds, fo2.fighter2_odds, fo2.bookie_id, fo2.date
            FROM
                fightodds AS fo2, bookies bo,
                (SELECT
                    MAX(fo4.date) as date, bookie_id
                FROM
                    fightodds fo4
                WHERE
                    fo4.fight_id = ? ' . $extra_query . ' 
                GROUP BY bookie_id) AS fo3
            WHERE
                fo2.fight_id = ? AND fo2.bookie_id = fo3.bookie_id AND fo2.date
            = fo3.date AND fo2.bookie_id = bo.id GROUP BY fo2.bookie_id ORDER BY bo.position,
            fo2.bookie_id, fo2.fight_id ASC;';

        $result = DBTools::doParamQuery($query, $params);
        $odds = array();
        while ($row = mysqli_fetch_array($result)) {
            $odds[] = new FightOdds((int) $row['fight_id'], (int) $row['bookie_id'], (string) $row['fighter1_odds'], (string) $row['fighter2_odds'], (string) $row['date']);
        }

        return $odds;
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
                $odds_col[] = new FightOdds((int) $row['fight_id'], (int) $row['bookie_id'], $row['fighter1_odds'], $row['fighter2_odds'], $row['date']);
            }
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }
        return $odds_col;
    }

    public static function getLatestOddsForFightAndBookie(int $matchup_id, int $bookie_id): ?FightOdds
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
            $odds_col[] = new FightOdds((int) $row['fight_id'], (int) $row['bookie_id'], $row['fighter1_odds'], $row['fighter2_odds'], $row['date']);
        }
        if (sizeof($odds_col) > 0) {
            return $odds_col[0];
        }
        return null;
    }

    public static function addNewFightOdds(FightOdds $fightodds_obj): ?int
    {
        $query = 'INSERT INTO fightodds(fight_id, fighter1_odds, fighter2_odds, bookie_id, date)
                    SELECT f.id, ?, ?, b.id, NOW()
                        FROM fights f, bookies b
                        WHERE f.id = ? AND b.id = ?';

        $params = [$fightodds_obj->getOdds(1), $fightodds_obj->getOdds(2), $fightodds_obj->getFightID(), $fightodds_obj->getBookieID()];

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

    public static function removeOddsForMatchupAndBookie(int $matchup_id, int $bookie_id)
    {
        $query = 'DELETE fo.*
                    FROM fightodds fo
                    WHERE
                        fo.fight_id = ?
                        AND fo.bookie_id = ?';
        $params = [$matchup_id, $bookie_id];
        DBTools::doParamQuery($query, $params);
        $deleted_odds = DBTools::getAffectedRows();

        //Also remove any metadata related to the matchup for this bookie
        $query = "DELETE FROM matchups_metadata WHERE matchup_id = ? AND source_bookie_id = ?";
        DBTools::doParamQuery($query, $params);

        return $deleted_odds;
    }

    public static function removePropOddsForMatchupAndBookie(int $matchup_id, int $bookie_id, int $proptype_id = null, int $team_num = null)
    {
        $params = [$matchup_id, $bookie_id];
        $extra_where = '';
        if ($proptype_id != null) {
            $params[] = $proptype_id;
            $extra_where .= ' AND proptype_id = ? ';
        }
        if ($team_num != null) {
            $params[] = $team_num;
            $extra_where .= ' AND team_num = ? ';
        }

        $query = 'DELETE lp.*
                    FROM lines_props lp
                    WHERE
                        lp.matchup_id = ?
                        AND lp.bookie_id = ? ' . $extra_where;


        DBTools::doParamQuery($query, $params);
        return DBTools::getAffectedRows();
    }

    public static function removePropOddsForEventAndBookie(int $event_id, int $bookie_id, int $proptype_id = null)
    {
        $params = [$event_id, $bookie_id];
        $extra_where = '';
        if ($proptype_id != null) {
            $params[] = $proptype_id;
            $extra_where .= ' AND proptype_id = ? ';
        }

        $query = 'DELETE lep.*
                    FROM lines_eventprops lep
                    WHERE
                        lep.event_id = ?
                        AND lep.bookie_id = ? ' . $extra_where;

        DBTools::doParamQuery($query, $params);
        return DBTools::getAffectedRows();
    }

    public static function getAllLatestPropOddsForMatchupAndBookie(int $matchup_id, int $bookie_id, int $proptype_id = -1): array
    {
        $extra_where = '';
        $params = [$matchup_id, $bookie_id];
        if ($proptype_id != -1) {
            $extra_where = ' AND proptype_id = ?';
            $params[] = $proptype_id;
        }

        $query = 'SELECT lp.*, pt.* 
                    FROM lines_props lp 
                    LEFT OUTER JOIN lines_props lp2
                        ON (lp.bookie_id = lp2.bookie_id AND lp.matchup_id = lp2.matchup_id  AND lp.proptype_id = lp2.proptype_id AND lp.team_num = lp2.team_num AND lp.date < lp2.date)
                        INNER JOIN prop_types pt ON lp.proptype_id = pt.id 
                    WHERE lp2.bookie_id IS NULL AND lp2.matchup_id IS NULL AND lp2.proptype_id IS NULL AND lp2.team_num IS NULL
                        AND lp.matchup_id = ? AND lp.bookie_id = ?;' . $extra_where;

        $result = PDOTools::findMany($query, $params);

        $prop_odds = [];
        foreach ($result as $row) {
            $prop_odds[] = new PropBet(
                $row['matchup_id'],
                $row['bookie_id'],
                $row['prop_desc'],
                $row['prop_odds'],
                $row['negprop_desc'],
                $row['negprop_odds'],
                $row['proptype_id'],
                $row['date'],
                $row['team_num']
            );
        }
        return $prop_odds;
    }

    public static function flagOddsForDeletion(int $bookie_id, int $matchup_id = null, int $event_id = null, int $proptype_id = null, int $team_num = null): ?int
    {
        if (!is_numeric($bookie_id) || ($matchup_id == null && $event_id == null)) {
            throw new \Exception("Invalid input", 10);
        }

        $query = 'INSERT INTO lines_flagged(bookie_id, matchup_id, event_id, proptype_id, team_num, initial_flagdate, last_flagdate) VALUES (?,?,?,?,?, NOW(), NOW()) ON DUPLICATE KEY UPDATE last_flagdate = NOW()';
        $params = [$bookie_id, $matchup_id, $event_id ?? -1, $proptype_id ?? -1, $team_num ?? -1];

        $id = null;
        try {
            $id = PDOTools::insert($query, $params);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Duplicate entry", 10);
            } else {
                throw new \Exception("Unknown error " . $e->getMessage(), 10);
            }
        }
        return $id;
    }

    public static function removeFlagged(int $bookie_id, int $matchup_id = null, int $event_id = null, int $proptype_id = null, int $team_num = null): ?int
    {
        $params = [$bookie_id, $matchup_id ?? -1, $event_id ?? -1, $proptype_id ?? -1, $team_num ?? -1];

        $query = 'DELETE FROM lines_flagged 
            WHERE bookie_id = ? 
            AND matchup_id = ?
            AND event_id = ?
            AND proptype_id = ?
            AND team_num = ?';

        $result = null;
        try {
            $result = PDOTools::delete($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }
        return $result;
    }

    public static function removeAllOldFlagged(): ?int
    {
        $query = 'DELETE lf FROM lines_flagged lf 
                        LEFT JOIN fights f ON lf.matchup_id = f.id 
                        LEFT JOIN events e ON f.event_id = e.id 
                    WHERE LEFT(e.date, 10) < LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)';
        $result = null;
        try {
            $result = PDOTools::delete($query, []);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }
        return $result;
    }

    public static function getAllFlaggedMatchups(): ?array
    {
        $query = 'SELECT f.*, e.name as event_name, e.date as event_date, f1.name AS team1_name, f2.name AS team2_name, lf.*, b.name as bookie_name, b.id as bookie_id, m.mvalue as gametime
                    FROM lines_flagged lf 
                        LEFT JOIN fights f ON lf.matchup_id = f.id 
                        LEFT JOIN fighters f1 ON f.fighter1_id = f1.id 
                        LEFT JOIN fighters f2 ON f.fighter2_id = f2.id 
                        LEFT JOIN events e ON f.event_id = e.id 
                        LEFT JOIN bookies b ON lf.bookie_id = b.id
            LEFT JOIN 
                        (SELECT matchup_id, source_bookie_id, mvalue 
                                FROM  matchups_metadata mm
                                WHERE mm.mattribute = "gametime" ) m ON f.id = m.matchup_id AND b.id = m.source_bookie_id
                WHERE proptype_id = -1
                ORDER BY e.date ASC;';

        $result = null;
        try {
            $result = PDOTools::findMany($query);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }
        return $result;
    }

    public static function getFlaggedOddsForDeletion()
    {
        //Get all flagged odds first. We select all flagged odds that have been flagged for more than 3 hours AND belongs to an event that is not starting in the next 4 hours.

        //These values can be tuned with time (TODO: Maybe move to config file?):
        $gametime_threshold = 4; //If the matchup is within this value (hours) we will not remove
        $flagged_time = 3; //How many hours the line must be flagged before deletion

        $query = 'SELECT lf.*, TIMESTAMPDIFF(HOUR, initial_flagdate, NOW()) AS timediff, pt.prop_desc, pt.negprop_desc, 
                        f1.name AS team1_name, f2.name AS team2_name, b.name AS bookie_name, e.name AS event_name,  
                        m.mvalue as gametime
                        FROM lines_flagged lf
                    LEFT JOIN fights f ON lf.matchup_id = f.id 
                    LEFT JOIN events e ON (f.event_id = e.id  OR lf.event_id = e.id)
                    LEFT JOIN prop_types pt ON lf.proptype_id = pt.id
                    LEFT JOIN fighters f1 ON f.fighter1_id = f1.id
                    LEFT JOIN fighters f2 ON f.fighter2_id = f2.id 
                    LEFT JOIN bookies b ON lf.bookie_id = b.id 
                    LEFT JOIN 
                            (SELECT matchup_id, source_bookie_id, mvalue 
                                FROM  matchups_metadata mm
                                WHERE mm.mattribute = "gametime" ) m ON f.id = m.matchup_id AND b.id = m.source_bookie_id 
                WHERE FROM_UNIXTIME(m.mvalue) > (NOW() + INTERVAL ' . $gametime_threshold . ' HOUR) HAVING timediff >= ' . $flagged_time . '';

        $result = null;
        try {
            $result = PDOTools::findMany($query, []);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        $flagged_odds = [
            'matchup_odds' => [],
            'prop_odds' => [],
            'event_prop_odds' => []
        ];
        foreach ($result as $row) {
            if ($row['matchup_id'] != -1) {
                if ($row['proptype_id'] == -1) {
                    //Remove matchup odds
                    $flagged_odds['matchup_odds'][] = $row;
                } else {
                    //Remove prop odds for matchup
                    $flagged_odds['prop_odds'][] = $row;
                }
            } elseif ($row['event_id'] != -1 && $row['proptype_id'] != -1) {
                //Remove event prop odds
                $flagged_odds['event_prop_odds'][] = $row;
            }
        }

        return $flagged_odds;
    }

    public static function getLatestPropOddsV2($event_id = null, $matchup_id = null, $bookie_id = null, $proptype_id = null, $team_num = null): ?array
    {
        $params = [];
        $extra_where = '';
        if ($event_id != null) {
            $extra_where .= ' AND e.id = ? ';
            $params[] = $event_id;
        }
        if ($matchup_id != null) {
            $extra_where .= ' AND f.id = ? ';
            $params[] = $matchup_id;
        }
        if ($bookie_id != null) {
            $extra_where .= ' AND lp.bookie_id = ? ';
            $params[] = $bookie_id;
        }
        if ($proptype_id != null) {
            $extra_where .= ' AND pt.id = ? ';
            $params[] = $proptype_id;
        }
        if ($team_num != null) {
            $extra_where .= ' AND lp.team_num = ? ';
            $params[] = $team_num;
        }

        $query = 'select e.*, f.*, lp.*, pt.*, lp2.prop_odds as previous_prop_odds, lp2.negprop_odds as previous_negprop_odds from events e 
                LEFT JOIN fights f ON e.id = f.event_id 
                LEFT JOIN lines_props lp ON f.id = lp.matchup_id
                LEFT JOIN prop_types pt ON lp.proptype_id = pt.id
                LEFT JOIN lines_props lp2 ON lp.matchup_id = lp2.matchup_id AND lp.proptype_id = lp2.proptype_id AND lp.bookie_id = lp2.bookie_id AND lp.team_num = lp2.team_num  AND lp2.date = (SELECT MAX(date) FROM lines_props lp3 WHERE lp.bookie_id = lp3.bookie_id AND lp.matchup_id = lp3.matchup_id AND lp.proptype_id = lp3.proptype_id AND lp.team_num = lp3.team_num AND lp3.date < lp.date)
            WHERE lp.date = (SELECT MAX(lpd.date) FROM lines_props lpd WHERE lp.bookie_id = lpd.bookie_id AND lp.matchup_id = lpd.matchup_id AND lp.proptype_id = lpd.proptype_id AND lp.team_num = lpd.team_num) 
            ' . $extra_where . ' 
            ORDER BY LEFT(pt.prop_desc,4) = "Over" DESC, pt.id ASC, lp.team_num ASC';

        $ret = null;
        try {
            $ret = PDOTools::findMany($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
            return false;
        }
        return $ret;
    }

    public static function getLatestEventPropOddsV2($event_id, $bookie_id = null, $proptype_id = null)
    {
        $params = [];
        $params[] = $event_id;
        $extra_where = '';
        if ($event_id == null) { //Required
            return false;
        }
        if ($bookie_id != null) {
            $extra_where .= ' AND pt.bookie_id = ? ';
            $params[] = $bookie_id;
        }
        if ($proptype_id != null) {
            $extra_where .= ' AND pt.id = ? ';
            $params[] = $proptype_id;
        }

        $query = 'SELECT e.*, lp.*, pt.*, lp2.prop_odds as previous_prop_odds, lp2.negprop_odds as previous_negprop_odds from events e 
                    LEFT JOIN lines_eventprops lp ON e.id = lp.event_id
                    LEFT JOIN prop_types pt ON lp.proptype_id = pt.id
                    LEFT JOIN lines_eventprops lp2 ON lp.event_id = lp2.event_id AND lp.proptype_id = lp2.proptype_id AND lp.bookie_id = lp2.bookie_id AND lp2.date = (SELECT MAX(date) FROM lines_eventprops lp3 WHERE lp.bookie_id = lp3.bookie_id AND lp.event_id = lp3.event_id AND lp.proptype_id = lp3.proptype_id AND lp3.date < lp.date)
                WHERE lp.date = (SELECT MAX(lpd.date) FROM lines_eventprops lpd WHERE lp.bookie_id = lpd.bookie_id AND lp.event_id = lpd.event_id AND lp.proptype_id = lpd.proptype_id) 
                AND e.id = ? 
                ' . $extra_where . ' 
                ORDER BY pt.id ASC;';

        $ret = null;
        try {
            $ret = PDOTools::findMany($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
            return false;
        }
        return $ret;
    }

    public static function getLatestMatchupOddsV2($event_id = null, $matchup_id = null)
    {
        $params = [];
        $extra_where = '';

        if ($event_id == null && $matchup_id == null) { //Either event ID or matchup ID needs to be specified
            return false;
        }

        if ($event_id != null) {
            $extra_where .= ' AND e.id = ? ';
            $params[] = $event_id;
        }
        if ($matchup_id != null) {
            $extra_where .= ' AND f.id = ? ';
            $params[] = $matchup_id;
        }

        $query = 'select e.*, f.*, fo.*, fo2.fighter1_odds as previous_team1_odds, fo2.fighter2_odds as previous_team2_odds from events e 
                    LEFT JOIN fights f ON e.id = f.event_id 
                    LEFT JOIN fightodds fo ON f.id = fo.fight_id
                    LEFT JOIN fightodds fo2 ON fo.fight_id = fo2.fight_id AND fo.bookie_id = fo2.bookie_id AND fo2.date = (SELECT MAX(date) FROM fightodds fo3 WHERE fo.bookie_id = fo3.bookie_id AND fo.fight_id = fo3.fight_id AND fo3.date < fo.date)
                WHERE fo.date = (SELECT MAX(fod.date) FROM fightodds fod WHERE fo.bookie_id = fod.bookie_id AND fo.fight_id = fod.fight_id)  
        ' . $extra_where . '';

        $ret = null;
        try {
            $ret = PDOTools::findMany($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
            return false;
        }
        return $ret;
    }
}
