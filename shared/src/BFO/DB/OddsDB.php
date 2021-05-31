<?php

namespace BFO\DB;

use BFO\Utils\DB\DBTools;
use BFO\Utils\DB\PDOTools;

use BFO\DataTypes\FightOdds;
use BFO\DataTypes\PropBet;
use BFO\DataTypes\PropType;
use BFO\DataTypes\EventPropBet;
use Exception;

class OddsDB
{
    public static function addPropBet($propbet_obj)
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


    public static function getPropBetsForMatchup($matchup_id)
    {
        $query = 'SELECT lp.bookie_id, lp.prop_odds, lp.negprop_odds, lp.proptype_id, lp.date, pt.prop_desc, pt.negprop_desc, lp.date, lp.team_num
                    FROM lines_props lp, prop_types pt
                    WHERE lp.matchup_id = ?
                        AND lp.proptype_id = pt.id
                        ORDER BY pt.prop_desc ASC, lp.team_num ASC';
        $params = [$matchup_id];
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

    public static function getPropTypes(int $proptype_id = null): array
    {
        $extra_where = '';
        $params = [];
        if ($proptype_id) {
            $extra_where .= ' AND pt.id = :proptype_id';
            $params[':proptype_id'] = $proptype_id;
        }

        $query = 'SELECT pt.id, pt.prop_desc, pt.negprop_desc, pt.is_eventprop
                    FROM prop_types pt
                        WHERE 1=1 
                        ' . $extra_where . ' 
                    ORDER BY LEFT(pt.prop_desc,4) = "Over" DESC, id ASC';

        $prop_types = [];
        try {
            foreach (PDOTools::findMany($query, $params) as $row) {
                $prop_type = new PropType(
                    (int) $row['id'],
                    $row['prop_desc'],
                    $row['negprop_desc']
                );
                $prop_type->setEventProp((bool) $row['is_eventprop']);
                $prop_types[] = $prop_type;
            }
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Unknown error " . $e->getMessage(), 10);
            }
        }
        return $prop_types;
    }

    /**
     * Retrieves the prop types that a certain matchup has props and odds for
     *
     * Since these are matchup specific prop types we will go ahead and replace
     * the <T> variables with the actual team name
     */
    public static function getAllPropTypesForMatchup($matchup_id): array
    {
        $query = 'SELECT pt.id, pt.prop_desc, pt.negprop_desc, lp.team_num
                    FROM prop_types pt, lines_props lp
                    WHERE lp.proptype_id = pt.id
                        AND  lp.matchup_id = ?
                        GROUP BY lp.matchup_id, lp.team_num, pt.id
                    ORDER BY LEFT(pt.prop_desc,4) = "Over" DESC, id ASC, lp.team_num ASC';

        $params = [$matchup_id];

        $result = DBTools::doParamQuery($query, $params);

        $prop_types = [];
        while ($row = mysqli_fetch_array($result)) {
            $prop_types[] = new PropType(
                (int) $row['id'],
                $row['prop_desc'],
                $row['negprop_desc'],
                (int) $row['team_num']
            );
        }

        return $prop_types;
    }

    /**
     * Retrieves the prop types that a certain event has props and odds for
     *
     * @param int $event_id Matchup ID
     * @return Array Collection of PropType objects
     */
    public static function getAllPropTypesForEvent($event_id): array
    {
        $query = 'SELECT pt.id, pt.prop_desc, pt.negprop_desc
                    FROM prop_types pt, lines_eventprops lep
                    WHERE lep.proptype_id = pt.id
                        AND  lep.event_id = ?
                        GROUP BY lep.event_id, pt.id
                    ORDER BY id ASC';

        $params = array($event_id);

        $result = DBTools::doParamQuery($query, $params);

        $prop_types = [];
        while ($row = mysqli_fetch_array($result)) {
            $prop_types[] = new PropType(
                (int) $row['id'],
                $row['prop_desc'],
                $row['negprop_desc'],
                0
            );
        }

        return $prop_types;
    }


    public static function getLatestPropOdds($matchup_id, $bookie_id, $proptype_id, $team_num, $offset = 0)
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

        $rResult = DBTools::doParamQuery($query, $params);

        $props = array();
        while ($aRow = mysqli_fetch_array($rResult)) {
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

    public static function getLatestEventPropOdds($event_id, $bookie_id, $proptype_id, $offset = 0)
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

        $rResult = DBTools::doParamQuery($query, $params);

        $props = [];
        while ($row = mysqli_fetch_array($rResult)) {
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


    public static function getBestPropOddsForMatchup($matchup_id, $proptype_id, $team_num)
    {
        $query = 'SELECT MAX(co1.prop_odds) AS prop_odds, MAX(co1.negprop_odds) AS negprop_odds, co1.bookie_id, co1.date
            FROM lines_props AS co1, (SELECT co2.bookie_id, MAX(co2.date) as date
                            FROM lines_props AS co2
                           WHERE co2.matchup_id = ?
                            AND co2.proptype_id = ?
                            AND co2.team_num = ?
                             GROUP BY co2.bookie_id) AS co3
            WHERE co1.bookie_id = co3.bookie_id
            AND co1.date = co3.date
            AND co1.matchup_id = ?
            AND co1.proptype_id = ?
            AND co1.team_num = ?
              GROUP BY co1.matchup_id, co1.team_num, co1.proptype_id
              LIMIT 0,1;';

        $params = [$matchup_id, $proptype_id, $team_num, $matchup_id, $proptype_id, $team_num];

        $result = DBTools::doParamQuery($query, $params);

        //$aFightOddsCol = array();

        if ($row = mysqli_fetch_array($result)) {
            return new PropBet(
                $matchup_id,
                $row['bookie_id'],
                '',
                $row['prop_odds'],
                '',
                $row['negprop_odds'],
                $proptype_id,
                $row['date'],
                $team_num
            );
        }
        return null;
    }

    /**
     * Gets all prop odds for the specific prop type and  matchup
     *
     * @param <type> $matchup_id
     * @param <type> $bookie_id
     * @param <type> $proptype_id
     * @param <type> $team_num
     * @return PropBet Collection of prop bets odds
     *
     */
    public static function getAllPropOddsForMatchupPropType($matchup_id, $bookie_id, $proptype_id, $team_num)
    {
        $params = array($matchup_id, $bookie_id, $proptype_id, $team_num);

        $query = 'SELECT lp.bookie_id, lp.prop_odds, lp.negprop_odds, lp.proptype_id, lp.date, pt.prop_desc, pt.negprop_desc, lp.date, lp.team_num
                    FROM lines_props lp, prop_types pt
                    WHERE lp.matchup_id = ?
                        AND lp.bookie_id = ?
                        AND lp.proptype_id = ?
                        AND lp.proptype_id = pt.id
                        AND lp.team_num = ?
                        ORDER BY lp.date ASC';

        $result = DBTools::doParamQuery($query, $params);

        $aProps = array();
        while ($aRow = mysqli_fetch_array($result)) {
            $aProps[] = new PropBet(
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

        return $aProps;
    }

    /**
     * Gets all prop odds for the specific prop type and event
     *
     * @param <type> $event_id
     * @param <type> $bookie_id
     * @param <type> $proptype_id
     * @return PropBet Collection of event prop bets odds
     *
     */
    public static function getAllPropOddsForEventPropType($event_id, $bookie_id, $proptype_id)
    {
        $params = array($event_id, $bookie_id, $proptype_id);

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


    public static function getOpeningOddsForMatchup($matchup_id)
    {
        $query = 'SELECT fight_id, fighter1_odds, fighter2_odds, bookie_id, date
                    FROM fightodds
                        WHERE fight_id = ? 
                    ORDER BY date ASC
                    LIMIT 0,1';

        $params = array($matchup_id);

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

    /**
     * Get openings odds for a specific prop
     *
     * @param int Matchup ID
     * @param int Proptype ID
     * @return FightOdds The opening odds or null if none was found
     */
    public static function getOpeningOddsForProp($matchup_id, $proptype_id, $team_num)
    {
        $params = array($matchup_id, $proptype_id, $team_num);

        $query = 'SELECT lp.bookie_id, lp.prop_odds, lp.negprop_odds, lp.proptype_id, lp.date, pt.prop_desc, pt.negprop_desc, lp.date, lp.team_num
                    FROM lines_props lp, prop_types pt
                    WHERE lp.matchup_id = ?
                        AND lp.proptype_id = ?
                        AND lp.proptype_id = pt.id
                        AND lp.team_num = ?
                        ORDER BY lp.date ASC
                        LIMIT 0, 1';

        $result = DBTools::doParamQuery($query, $params);

        $aProps = array();
        while ($aRow = mysqli_fetch_array($result)) {
            $aProps[] = new PropBet(
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
        if (count($aProps) > 0) {
            return $aProps[0];
        }

        return null;
    }


    /**
     * Get openings odds for a specific event prop
     *
     * @param int Matchup ID
     * @param int Proptype ID
     * @return FightOdds The opening odds or null if none was found
     */
    public static function getOpeningOddsForEventProp($event_id, $proptype_id)
    {
        $params = array($event_id, $proptype_id);

        $query = 'SELECT lep.bookie_id, lep.prop_odds, lep.negprop_odds, lep.proptype_id, lep.date, pt.prop_desc, pt.negprop_desc, lep.date
                    FROM lines_eventprops lep, prop_types pt
                    WHERE lep.event_id = ?
                        AND lep.proptype_id = ?
                        AND lep.proptype_id = pt.id
                        ORDER BY lep.date ASC
                        LIMIT 0, 1';

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

    /**
     * Get openings odds for a specific prop and bookie
     */
    public static function getOpeningOddsForPropAndBookie($matchup_id, $proptype_id, $bookie_id, $team_num): ?PropBet
    {
        $params = [$matchup_id, $proptype_id, $bookie_id, $team_num];

        $query = 'SELECT lp.bookie_id, lp.prop_odds, lp.negprop_odds, lp.proptype_id, lp.date, pt.prop_desc, pt.negprop_desc, lp.date, lp.team_num
                    FROM lines_props lp, prop_types pt
                    WHERE lp.matchup_id = ?
                        AND lp.proptype_id = ?
                        AND lp.bookie_id = ?
                        AND lp.proptype_id = pt.id
                        AND lp.team_num = ?
                        ORDER BY lp.date ASC
                        LIMIT 0, 1';

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
        if (count($props) > 0) {
            return $props[0];
        }

        return null;
    }

    /**
     * Get openings odds for a specific prop and bookkie
     *
     * @param int Event ID
     * @param int Proptype ID
     * @param int Bookie ID
     * @return FightOdds The opening odds or null if none was found
     */
    public static function getOpeningOddsForEventPropAndBookie($event_id, $proptype_id, $bookie_id)
    {
        $params = array($event_id, $proptype_id, $bookie_id);

        $query = 'SELECT lep.bookie_id, lep.prop_odds, lep.negprop_odds, lep.proptype_id, lep.date, pt.prop_desc, pt.negprop_desc, lep.date
                    FROM lines_eventprops lep, prop_types pt
                    WHERE lep.event_id = ?
                        AND lep.proptype_id = ?
                        AND lep.bookie_id = ?
                        AND lep.proptype_id = pt.id
                        ORDER BY lep.date ASC
                        LIMIT 0, 1';

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


    /**
     * Get all correlations for the specified bookie
     *
     * @param int $bookie_id Bookie ID
     * @return array Collection of correlations
     */
    public static function getCorrelationsForBookie($bookie_id)
    {
        $params = array($bookie_id);

        $query = 'SELECT lc.correlation, lc.bookie_id, lc.matchup_id
                        FROM lines_correlations lc 
                        WHERE lc.bookie_id = ? ';

        $result = DBTools::doParamQuery($query, $params);

        $return = [];
        while ($row = mysqli_fetch_array($result)) {
            $return[] = array(
                'correlation' => $row['correlation'],
                'matchup_id' => $row['matchup_id']
            );
        }
        return $return;
    }


    public static function getMatchupForCorrelation($bookie_id, $a_sCorrelation)
    {
        $params = array($bookie_id, $a_sCorrelation);

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
     *
     * @param int $bookie_id Bookie ID
     * @param array $correlations Collection of correlations as defined above
     */
    public static function storeCorrelations($bookie_id, $correlations)
    {
        $params = array();

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


    public static function getCompletePropsForMatchup($matchup_id, $offset = 0)
    {
        if ($offset != 0 && $offset != 1) {
            return false;
        }

        $extra_query = '';
        $params = array($matchup_id, $matchup_id);
        if ($offset == 1) {
            $extra_query = ' AND lp4.date != (SELECT
                MAX(lp5.date)  FROM        lines_props lp5
            WHERE
                lp5.matchup_id = ? AND lp5.bookie_id =
                lp4.bookie_id AND lp5.proptype_id = lp4.proptype_id AND lp5.team_num =
                lp4.team_num) ';
            $params[] = $matchup_id;
        }


        $query = 'SELECT
                    lp2.matchup_id,
                    lp2.bookie_id,
                    lp2.proptype_id,
                    lp2.team_num,
                    lp2.date,
                    lp2.prop_odds,
                    lp2.negprop_odds,
                    pt.prop_desc, 
                    pt.negprop_desc
                FROM
                    bookies b, prop_types pt, lines_props AS lp2,
                    (SELECT
                        MAX(lp4.date) as date, bookie_id, proptype_id, team_num
                    FROM
                        lines_props lp4
                    WHERE
                        lp4.matchup_id = ? ' . $extra_query . ' 
                    GROUP BY bookie_id , proptype_id , team_num) AS lp3
                WHERE
                    lp2.matchup_id = ? AND lp2.bookie_id = lp3.bookie_id AND
                lp2.proptype_id = lp3.proptype_id AND lp2.team_num = lp3.team_num AND
                lp2.date = lp3.date AND lp2.proptype_id = pt.id AND lp2.bookie_id = b.id ORDER BY b.position
                ;';

        $result = DBTools::doParamQuery($query, $params);

        $props = array();
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
        if (count($props) > 0) {
            return $props;
        }

        return null;
    }

    public static function getCompletePropsForEvent($event_id, $a_iOffset = 0, $bookie_id = null)
    {
        if ($a_iOffset != 0 && $a_iOffset != 1) {
            return false;
        }

        $sExtraQuery = '';
        $params = array($event_id, $event_id);
        if ($a_iOffset == 1) {
            $sExtraQuery = ' AND lep4.date != (SELECT
                MAX(lep5.date)  FROM lines_eventprops lep5
            WHERE
                lep5.event_id = ? AND lep5.bookie_id =
                lep4.bookie_id AND lep5.proptype_id = lep4.proptype_id) ';
            $params[] = $event_id;
        }
        if ($bookie_id != null) {
            $sExtraQuery .= ' AND bookie_id = ? ';
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
                        lep4.event_id = ? ' . $sExtraQuery . ' 
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

    public static function removeOddsForMatchupAndBookie($matchup_id, $bookie_id)
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

    public static function removePropOddsForMatchupAndBookie($matchup_id, $bookie_id, $proptype_id = null, $team_num = null)
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

    public static function removePropOddsForEventAndBookie($event_id, $bookie_id, $proptype_id = null)
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

    public static function getAllLatestPropOddsForMatchupAndBookie($matchup_id, $bookie_id, $proptype_id = -1)
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

    public static function flagOddsForDeletion($bookie_id, $matchup_id = null, $event_id = null, $proptype_id = null, $team_num = null)
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

    public static function checkIfFlagged($bookie_id, $matchup_id = null, $event_id = null, $proptype_id = null, $team_num = null)
    {
        $params = [$bookie_id, $matchup_id ?? -1, $event_id ?? -1, $proptype_id ?? -1, $team_num ?? -1];

        $query = 'SELECT * FROM lines_flagged 
                WHERE bookie_id = ? 
                AND matchup_id = ?
                AND event_id = ?
                AND proptype_id = ?
                AND team_num = ?';

        $result = null;
        try {
            $result = PDOTools::findMany($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }
        return $result;
    }

    public static function removeFlagged(int $bookie_id, int $matchup_id = null, int $event_id = null, int $proptype_id = null, int $team_num = null): int
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

    public static function removeAllOldFlagged(): int
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

    public static function getAllFlaggedMatchups()
    {
        $query = 'SELECT f.*, e.name as event_name, e.date as event_date, f1.name AS team1_name, f2.name AS team2_name, lf.*, b.name as bookie_name, b.id as bookie_id, m.mvalue as gametime, m.max_value as max_gametime, m.min_value as min_gametime 
                    FROM lines_flagged lf 
                        LEFT JOIN fights f ON lf.matchup_id = f.id 
                        LEFT JOIN fighters f1 ON f.fighter1_id = f1.id 
                        LEFT JOIN fighters f2 ON f.fighter2_id = f2.id 
                        LEFT JOIN events e ON f.event_id = e.id 
                        LEFT JOIN bookies b ON lf.bookie_id = b.id
            LEFT JOIN 
                        (SELECT matchup_id, AVG(mvalue) as mvalue, MAX(mvalue) as max_value, MIN(mvalue) as min_value 
                            FROM events em 
                                LEFT JOIN fights fm ON em.id = fm.event_id 
                                LEFT JOIN matchups_metadata mm ON fm.id = mm.matchup_id 
                            WHERE mm.mattribute = "gametime" 
                            GROUP BY matchup_id)  m ON f.id = m.matchup_id
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

        /*$query = 'SELECT lf.*, TIMESTAMPDIFF(HOUR, initial_flagdate, NOW()) AS timediff, pt.prop_desc, pt.negprop_desc, 
                        f1.name AS team1_name, f2.name AS team2_name, b.name AS bookie_name, e.name AS event_name FROM lines_flagged lf 
                    LEFT JOIN fights f ON lf.matchup_id = f.id 
                    LEFT JOIN events e ON (f.event_id = e.id  OR lf.event_id = e.id)
                    LEFT JOIN prop_types pt ON lf.proptype_id = pt.id
                    LEFT JOIN fighters f1 ON f.fighter1_id = f1.id
                    LEFT JOIN fighters f2 ON f.fighter2_id = f2.id 
                    LEFT JOIN bookies b ON lf.bookie_id = b.id 
                WHERE LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL -' . $gametime_threshold . ' HOUR), 10) HAVING timediff >= ' . $flagged_time . ';';*/


        $query = 'SELECT lf.*, TIMESTAMPDIFF(HOUR, initial_flagdate, NOW()) AS timediff, pt.prop_desc, pt.negprop_desc, 
                        f1.name AS team1_name, f2.name AS team2_name, b.name AS bookie_name, e.name AS event_name,  
                        m.mvalue as gametime, m.max_value as max_gametime, m.min_value as min_gametime
                        FROM lines_flagged lf
                    LEFT JOIN fights f ON lf.matchup_id = f.id 
                    LEFT JOIN events e ON (f.event_id = e.id  OR lf.event_id = e.id)
                    LEFT JOIN prop_types pt ON lf.proptype_id = pt.id
                    LEFT JOIN fighters f1 ON f.fighter1_id = f1.id
                    LEFT JOIN fighters f2 ON f.fighter2_id = f2.id 
                    LEFT JOIN bookies b ON lf.bookie_id = b.id 
                    LEFT JOIN 
                            (SELECT matchup_id, ROUND(AVG(mvalue)) as mvalue, MAX(mvalue) as max_value, MIN(mvalue) as min_value 
                                FROM events em 
                                    LEFT JOIN fights fm ON em.id = fm.event_id 
                                    LEFT JOIN matchups_metadata mm ON fm.id = mm.matchup_id 
                                WHERE mm.mattribute = "gametime" 
                                GROUP BY matchup_id) m ON f.id = m.matchup_id 
                WHERE FROM_UNIXTIME(m.min_value) > (NOW() + INTERVAL ' . $gametime_threshold . ' HOUR) HAVING timediff >= ' . $flagged_time . '';

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

    public static function getLatestPropOddsV2($event_id = null, $matchup_id = null, $bookie_id = null, $proptype_id = null, $team_num = null)
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
