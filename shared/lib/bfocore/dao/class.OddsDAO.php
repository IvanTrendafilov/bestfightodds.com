<?php

require_once('lib/bfocore/utils/db/class.DBTools.php');
require_once('lib/bfocore/utils/db/class.PDOTools.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');

class OddsDAO
{
    public static function addPropBet($a_oPropBet)
    {
        $sQuery = 'INSERT IGNORE INTO lines_props(matchup_id, bookie_id, prop_odds, negprop_odds, proptype_id, date, team_num)
                    VALUES(?, ?, ?, ?, ?, NOW(), ?)';

        $aParams = array($a_oPropBet->getMatchupID(),
            $a_oPropBet->getBookieID(),
            $a_oPropBet->getPropOdds(),
            $a_oPropBet->getNegPropOdds(),
            $a_oPropBet->getPropTypeID(),
            $a_oPropBet->getTeamNumber());

        try 
        {
            $id = PDOTools::insert($sQuery, $aParams);
        }
        catch(PDOException $e)
        {
            if($e->getCode() == 23000)
            {
                throw new Exception("Duplicate entry", 10);	
            }
            else
            {
                throw new Exception("Unknown error " . $e->getMessage(), 10);	
            }
            return false;
        }
        return true;
    }

    public static function addEventPropBet($a_oEventPropBet)
    {
        $sQuery = 'INSERT IGNORE INTO lines_eventprops(event_id, bookie_id, prop_odds, negprop_odds, proptype_id, date)
                    VALUES(?, ?, ?, ?, ?, NOW())';

        $aParams = array($a_oEventPropBet->getEventID(),
            $a_oEventPropBet->getBookieID(),
            $a_oEventPropBet->getPropOdds(),
            $a_oEventPropBet->getNegPropOdds(),
            $a_oEventPropBet->getPropTypeID());

        try 
        {
            $id = PDOTools::insert($sQuery, $aParams);
        }
        catch(PDOException $e)
        {
            if($e->getCode() == 23000)
            {
                throw new Exception("Duplicate entry", 10);	
            }
            else
            {
                throw new Exception("Unknown error " . $e->getMessage(), 10);	
            }
            return false;
        }
        return true;
    }


    public static function getPropBetsForMatchup($a_iMatchupID)
    {
        $sQuery = 'SELECT lp.bookie_id, lp.prop_odds, lp.negprop_odds, lp.proptype_id, lp.date, pt.prop_desc, pt.negprop_desc, lp.date, lp.team_num
                    FROM lines_props lp, prop_types pt
                    WHERE lp.matchup_id = ?
                        AND lp.proptype_id = pt.id
                        ORDER BY pt.prop_desc ASC, lp.team_num ASC';
        $aParams = array($a_iMatchupID);
        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aProps = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aProps[] = new PropBet($a_iMatchupID,
                            $aRow['bookie_id'],
                            $aRow['prop_desc'],
                            $aRow['prop_odds'],
                            $aRow['negprop_desc'],
                            $aRow['negprop_odds'],
                            $aRow['proptype_id'],
                            $aRow['date'],
                            $aRow['team_num']);
        }

        return $aProps;
    }

    /*public static function getPropBetsForEvent($a_iEventID)
    {
        $sQuery = 'SELECT lep.bookie_id, lep.prop_odds, lep.negprop_odds, lep.proptype_id, lep.date, pt.prop_desc, pt.negprop_desc, lep.date
                    FROM lines_eventprops lep, prop_types pt
                    WHERE lep.event_id = ?
                        AND lep.proptype_id = pt.id
                        ORDER BY pt.prop_desc ASC';
        $aParams = array($a_iEventID);
        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aProps = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aProps[] = new EventPropBet($a_iEventID,
                            $aRow['bookie_id'],
                            $aRow['prop_desc'],
                            $aRow['prop_odds'],
                            $aRow['negprop_desc'],
                            $aRow['negprop_odds'],
                            $aRow['proptype_id'],
                            $aRow['date']);
        }

        return $aProps;
    }*/

    public static function getAllMatchupPropsForEvent($a_iEventID)
    {
        //Under development
        $sQuery = 'SELECT lp.*
            FROM fights f INNER JOIN lines_props lp ON f.id = lp.matchup_id 
            INNER JOIN
                (SELECT matchup_id, proptype_id, bookie_id, team_num, date AS MaxDateTime
                FROM lines_props
                GROUP BY matchup_id, proptype_id, bookie_id, team_num) groupedtt 
            ON (lp.matchup_id = groupedtt.matchup_id 
            AND lp.proptype_id = groupedtt.proptype_id 
            AND lp.bookie_id = groupedtt.bookie_id 
            AND lp.team_num = groupedtt.team_num 
            AND lp.date = groupedtt.MaxDateTime)
            WHERE f.event_id = ?;';

        $rResult = DBTools::doParamQuery($sQuery, [$a_iEventID]);

        $aPropTypes = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aPropTypes[] = new PropType($aRow['id'],
                            $aRow['prop_desc'],
                            $aRow['negprop_desc']);
        }

        return $aPropTypes;
    }


    public static function getAllPropTypes()
    {
        $sQuery = 'SELECT pt.id, pt.prop_desc, pt.negprop_desc, pt.is_eventprop
                    FROM prop_types pt
                    ORDER BY LEFT(pt.prop_desc,4) = "Over" DESC, id ASC';

        $rResult = DBTools::doQuery($sQuery);

        $aPropTypes = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $oTempPT = new PropType($aRow['id'],
                            $aRow['prop_desc'],
                            $aRow['negprop_desc']);
            $oTempPT->setEventProp($aRow['is_eventprop']);
            $aPropTypes[] = $oTempPT;
        }

        return $aPropTypes;
    }

    public static function getPropTypeByID($a_iID)
    {
        $sQuery = 'SELECT pt.id, pt.prop_desc, pt.negprop_desc, pt.is_eventprop
                    FROM prop_types pt
                    WHERE pt.id = ?';

        $rResult = DBTools::doParamQuery($sQuery, [$a_iID]);

        $aPropTypes = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $oTempPT = new PropType($aRow['id'],
                            $aRow['prop_desc'],
                            $aRow['negprop_desc']);
            $oTempPT->setEventProp($aRow['is_eventprop']);
            $aPropTypes[] = $oTempPT;
        }

        if (sizeof($aPropTypes) > 0)
        {
            return $aPropTypes[0];
        }
        return null;
    }

    /**
     * Retrieves the prop types that a certain matchup has props and odds for
     *
     * Since these are matchup specific prop types we will go ahead and replace
     * the <T> variables with the actual team name
     * 
     * @param int $a_iMatchupID Matchup ID
     * @return Array Collection of PropType objects
     */
    public static function getAllPropTypesForMatchup($a_iMatchupID)
    {
        $sQuery = 'SELECT pt.id, pt.prop_desc, pt.negprop_desc, lp.team_num
                    FROM prop_types pt, lines_props lp
                    WHERE lp.proptype_id = pt.id
                        AND  lp.matchup_id = ?
                        GROUP BY lp.matchup_id, lp.team_num, pt.id
                    ORDER BY LEFT(pt.prop_desc,4) = "Over" DESC, id ASC, lp.team_num ASC';

        $aParams = array($a_iMatchupID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aPropTypes = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aPropTypes[] = new PropType($aRow['id'],
                            $aRow['prop_desc'],
                            $aRow['negprop_desc'],
                            $aRow['team_num']);
        }

        return $aPropTypes;
    }

    /**
     * Retrieves the prop types that a certain event has props and odds for
     *
     * @param int $a_iEventID Matchup ID
     * @return Array Collection of EventPropType objects
     */
    public static function getAllPropTypesForEvent($a_iEventID)
    {
        $sQuery = 'SELECT pt.id, pt.prop_desc, pt.negprop_desc
                    FROM prop_types pt, lines_eventprops lep
                    WHERE lep.proptype_id = pt.id
                        AND  lep.event_id = ?
                        GROUP BY lep.event_id, pt.id
                    ORDER BY id ASC';

        $aParams = array($a_iEventID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aPropTypes = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aPropTypes[] = new PropType($aRow['id'],
                            $aRow['prop_desc'],
                            $aRow['negprop_desc'],
                            0);
        }

        return $aPropTypes;
    }


    public static function getLatestPropOdds($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum, $a_iOffset = 0)
    {
        $aParams = array($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum);

        if (!is_integer($a_iOffset))
        {
            return null;
        }

        $sQuery = 'SELECT lp.bookie_id, lp.prop_odds, lp.negprop_odds, lp.proptype_id, lp.date, pt.prop_desc, pt.negprop_desc, lp.date, lp.team_num
                    FROM lines_props lp, prop_types pt
                    WHERE lp.matchup_id = ?
                        AND lp.bookie_id = ?
                        AND lp.proptype_id = ?
                        AND lp.proptype_id = pt.id
                        AND lp.team_num = ?
                        ORDER BY lp.date DESC
                        LIMIT ' . $a_iOffset . ', 1';

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aProps = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aProps[] = new PropBet($a_iMatchupID,
                            $aRow['bookie_id'],
                            $aRow['prop_desc'],
                            $aRow['prop_odds'],
                            $aRow['negprop_desc'],
                            $aRow['negprop_odds'],
                            $aRow['proptype_id'],
                            $aRow['date'],
                            $aRow['team_num']);
        }
        if (count($aProps) > 0)
        {
            return $aProps[0];
        }

        return null;
    }

    public static function getLatestEventPropOdds($a_iEventID, $a_iBookieID, $a_iPropTypeID, $a_iOffset = 0)
    {
        $aParams = array($a_iEventID, $a_iBookieID, $a_iPropTypeID);

        if (!is_integer($a_iOffset))
        {
            return null;
        }

        $sQuery = 'SELECT lep.bookie_id, lep.prop_odds, lep.negprop_odds, lep.proptype_id, lep.date, pt.prop_desc, pt.negprop_desc, lep.date
                    FROM lines_eventprops lep, prop_types pt
                    WHERE lep.event_id = ?
                        AND lep.bookie_id = ?
                        AND lep.proptype_id = ?
                        AND lep.proptype_id = pt.id
                        ORDER BY lep.date DESC
                        LIMIT ' . $a_iOffset . ', 1';

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aProps = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aProps[] = new EventPropBet($a_iEventID,
                            $aRow['bookie_id'],
                            $aRow['prop_desc'],
                            $aRow['prop_odds'],
                            $aRow['negprop_desc'],
                            $aRow['negprop_odds'],
                            $aRow['proptype_id'],
                            $aRow['date']);
        }
        if (count($aProps) > 0)
        {
            return $aProps[0];
        }

        return null;
    }


    public static function getBestPropOddsForMatchup($a_iMatchupID, $a_iPropTypeID, $a_iTeam)
    {
        $sQuery = 'SELECT MAX(co1.prop_odds) AS prop_odds, MAX(co1.negprop_odds) AS negprop_odds, co1.bookie_id, co1.date
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
        
        

        $aParams = array($a_iMatchupID, $a_iPropTypeID, $a_iTeam, $a_iMatchupID, $a_iPropTypeID, $a_iTeam);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        //$aFightOddsCol = array();

        if ($aRow = mysqli_fetch_array($rResult))
        {
            return new PropBet($a_iMatchupID,
                            $aRow['bookie_id'],
                            '',
                            $aRow['prop_odds'],
                            '',
                            $aRow['negprop_odds'],
                            $a_iPropTypeID,
                            $aRow['date'],
                            $a_iTeam);
        }
        return null;
    }

    public static function getBestPropOddsForEvent($a_iEventID, $a_iPropTypeID)
    {
        $sQuery = 'SELECT MAX(co1.prop_odds) AS prop_odds, MAX(co1.negprop_odds) AS negprop_odds, co1.bookie_id, co1.date
            FROM lines_eventprops AS co1, (SELECT co2.bookie_id, MAX(co2.date) as date
                            FROM lines_eventprops AS co2
                           WHERE co2.event_id = ?
                            AND co2.proptype_id = ?
                             GROUP BY co2.bookie_id) AS co3
            WHERE co1.bookie_id = co3.bookie_id
            AND co1.date = co3.date
            AND co1.event_id = ?
            AND co1.proptype_id = ?
              GROUP BY co1.event_id, co1.proptype_id
              LIMIT 0,1;';
        
        $aParams = array($a_iEventID, $a_iPropTypeID, $a_iEventID, $a_iPropTypeID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        //$aFightOddsCol = array();

        if ($aRow = mysqli_fetch_array($rResult))
        {
            return new EventPropBet($a_iEventID,
                            $aRow['bookie_id'],
                            '',
                            $aRow['prop_odds'],
                            '',
                            $aRow['negprop_odds'],
                            $a_iPropTypeID,
                            $aRow['date']);
        }
        return null;
    }


    /**
     * Gets all prop odds for the specific prop type and  matchup
     *
     * @param <type> $a_iMatchupID
     * @param <type> $a_iBookieID
     * @param <type> $a_iPropTypeID
     * @param <type> $a_iTeamNum
     * @return PropBet Collection of prop bets odds
     *
     */
    public static function getAllPropOddsForMatchupPropType($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum)
    {

        $aParams = array($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum);

        $sQuery = 'SELECT lp.bookie_id, lp.prop_odds, lp.negprop_odds, lp.proptype_id, lp.date, pt.prop_desc, pt.negprop_desc, lp.date, lp.team_num
                    FROM lines_props lp, prop_types pt
                    WHERE lp.matchup_id = ?
                        AND lp.bookie_id = ?
                        AND lp.proptype_id = ?
                        AND lp.proptype_id = pt.id
                        AND lp.team_num = ?
                        ORDER BY lp.date ASC';

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aProps = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aProps[] = new PropBet($a_iMatchupID,
                            $aRow['bookie_id'],
                            $aRow['prop_desc'],
                            $aRow['prop_odds'],
                            $aRow['negprop_desc'],
                            $aRow['negprop_odds'],
                            $aRow['proptype_id'],
                            $aRow['date'],
                            $aRow['team_num']);
        }

        return $aProps;
    }

    /**
     * Gets all prop odds for the specific prop type and event
     *
     * @param <type> $a_iEventID
     * @param <type> $a_iBookieID
     * @param <type> $a_iPropTypeID
     * @return PropBet Collection of event prop bets odds
     *
     */
    public static function getAllPropOddsForEventPropType($a_iEventID, $a_iBookieID, $a_iPropTypeID)
    {

        $aParams = array($a_iEventID, $a_iBookieID, $a_iPropTypeID);

        $sQuery = 'SELECT lep.bookie_id, lep.prop_odds, lep.negprop_odds, lep.proptype_id, lep.date, pt.prop_desc, pt.negprop_desc, lep.date
                    FROM lines_eventprops lep, prop_types pt
                    WHERE lep.event_id = ?
                        AND lep.bookie_id = ?
                        AND lep.proptype_id = ?
                        AND lep.proptype_id = pt.id
                        ORDER BY lep.date ASC';

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aProps = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aProps[] = new EventPropBet($a_iEventID,
                            $aRow['bookie_id'],
                            $aRow['prop_desc'],
                            $aRow['prop_odds'],
                            $aRow['negprop_desc'],
                            $aRow['negprop_odds'],
                            $aRow['proptype_id'],
                            $aRow['date']);
        }

        return $aProps;
    }


    public static function getOpeningOddsForMatchup($a_iMatchupID)
    {
        $sQuery = 'SELECT fight_id, fighter1_odds, fighter2_odds, bookie_id, date
                    FROM fightodds
                        WHERE fight_id = ? 
                    ORDER BY date ASC
                    LIMIT 0,1';

        $aParams = array($a_iMatchupID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aFightOddsCol = array();
        while ($aFightOdds = mysqli_fetch_array($rResult))
        {
            $aFightOddsCol[] = new FightOdds($aFightOdds['fight_id'], $aFightOdds['bookie_id'], $aFightOdds['fighter1_odds'], $aFightOdds['fighter2_odds'], $aFightOdds['date']);
        }
        if (sizeof($aFightOddsCol) > 0)
        {
            return $aFightOddsCol[0];
        }
        return null;
    }

    /**
     * Gets the openings odds for the specified matchup and bookie
     * 
     * @param int $a_iMatchupID Matchup ID
     * @param int $a_iBookieID Bookie ID
     * @return \FightOdds|null The odds object. Null if not found
     */
    public static function getOpeningOddsForMatchupAndBookie($a_iMatchupID, $a_iBookieID)
    {


        $sQuery = 'SELECT fight_id, fighter1_odds, fighter2_odds, bookie_id, date
                    FROM fightodds
                    WHERE bookie_id = ? 
                        AND fight_id = ? 
                    ORDER BY date ASC
                    LIMIT 0,1';

        $aParams = array($a_iBookieID, $a_iMatchupID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aFightOddsCol = array();
        while ($aFightOdds = mysqli_fetch_array($rResult))
        {
            $aFightOddsCol[] = new FightOdds($aFightOdds['fight_id'], $aFightOdds['bookie_id'], $aFightOdds['fighter1_odds'], $aFightOdds['fighter2_odds'], $aFightOdds['date']);
        }
        if (sizeof($aFightOddsCol) > 0)
        {

            return $aFightOddsCol[0];
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
    public static function getOpeningOddsForProp($a_iMatchupID, $a_iPropTypeID, $a_iTeamNum)
    {
        $aParams = array($a_iMatchupID, $a_iPropTypeID, $a_iTeamNum);

        $sQuery = 'SELECT lp.bookie_id, lp.prop_odds, lp.negprop_odds, lp.proptype_id, lp.date, pt.prop_desc, pt.negprop_desc, lp.date, lp.team_num
                    FROM lines_props lp, prop_types pt
                    WHERE lp.matchup_id = ?
                        AND lp.proptype_id = ?
                        AND lp.proptype_id = pt.id
                        AND lp.team_num = ?
                        ORDER BY lp.date ASC
                        LIMIT 0, 1';

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aProps = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aProps[] = new PropBet($a_iMatchupID,
                            $aRow['bookie_id'],
                            $aRow['prop_desc'],
                            $aRow['prop_odds'],
                            $aRow['negprop_desc'],
                            $aRow['negprop_odds'],
                            $aRow['proptype_id'],
                            $aRow['date'],
                            $aRow['team_num']);
        }
        if (count($aProps) > 0)
        {
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
    public static function getOpeningOddsForEventProp($a_iEventID, $a_iPropTypeID)
    {
        $aParams = array($a_iEventID, $a_iPropTypeID);

        $sQuery = 'SELECT lep.bookie_id, lep.prop_odds, lep.negprop_odds, lep.proptype_id, lep.date, pt.prop_desc, pt.negprop_desc, lep.date
                    FROM lines_eventprops lep, prop_types pt
                    WHERE lep.event_id = ?
                        AND lep.proptype_id = ?
                        AND lep.proptype_id = pt.id
                        ORDER BY lep.date ASC
                        LIMIT 0, 1';

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aProps = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aProps[] = new EventPropBet($a_iEventID,
                            $aRow['bookie_id'],
                            $aRow['prop_desc'],
                            $aRow['prop_odds'],
                            $aRow['negprop_desc'],
                            $aRow['negprop_odds'],
                            $aRow['proptype_id'],
                            $aRow['date']);
        }
        if (count($aProps) > 0)
        {
            return $aProps[0];
        }

        return null;
    }

    /**
     * Get openings odds for a specific prop and bookie
     * 
     * @param int Matchup ID
     * @param int Proptype ID
     * @param int Bookie ID
     * @return FightOdds The opening odds or null if none was found
     */
    public static function getOpeningOddsForPropAndBookie($a_iMatchupID, $a_iPropTypeID, $a_iBookieID, $a_iTeamNum)
    {
        $aParams = array($a_iMatchupID, $a_iPropTypeID, $a_iBookieID, $a_iTeamNum);

        $sQuery = 'SELECT lp.bookie_id, lp.prop_odds, lp.negprop_odds, lp.proptype_id, lp.date, pt.prop_desc, pt.negprop_desc, lp.date, lp.team_num
                    FROM lines_props lp, prop_types pt
                    WHERE lp.matchup_id = ?
                        AND lp.proptype_id = ?
                        AND lp.bookie_id = ?
                        AND lp.proptype_id = pt.id
                        AND lp.team_num = ?
                        ORDER BY lp.date ASC
                        LIMIT 0, 1';

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aProps = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aProps[] = new PropBet($a_iMatchupID,
                            $aRow['bookie_id'],
                            $aRow['prop_desc'],
                            $aRow['prop_odds'],
                            $aRow['negprop_desc'],
                            $aRow['negprop_odds'],
                            $aRow['proptype_id'],
                            $aRow['date'],
                            $aRow['team_num']);
        }
        if (count($aProps) > 0)
        {
            return $aProps[0];
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
    public static function getOpeningOddsForEventPropAndBookie($a_iEventID, $a_iPropTypeID, $a_iBookieID)
    {
        $aParams = array($a_iEventID, $a_iPropTypeID, $a_iBookieID, $a_iTeamNum);

        $sQuery = 'SELECT lep.bookie_id, lep.prop_odds, lep.negprop_odds, lep.proptype_id, lep.date, pt.prop_desc, pt.negprop_desc, lep.date
                    FROM lines_eventprops lep, prop_types pt
                    WHERE lep.matchup_id = ?
                        AND lep.proptype_id = ?
                        AND lep.bookie_id = ?
                        AND lep.proptype_id = pt.id
                        ORDER BY lep.date ASC
                        LIMIT 0, 1';

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aProps = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aProps[] = new EventPropBet($a_iEventID,
                            $aRow['bookie_id'],
                            $aRow['prop_desc'],
                            $aRow['prop_odds'],
                            $aRow['negprop_desc'],
                            $aRow['negprop_odds'],
                            $aRow['proptype_id'],
                            $aRow['date']);
        }
        if (count($aProps) > 0)
        {
            return $aProps[0];
        }

        return null;
    }


    /**
     * Get all correlations for the specified bookie
     * 
     * @param int $a_iBookieID Bookie ID
     * @return array Collection of correlations 
     */
    public static function getCorrelationsForBookie($a_iBookieID)
    {
        $aParams = array($a_iBookieID);

        $sQuery = 'SELECT lc.correlation, lc.bookie_id, lc.matchup_id
                        FROM lines_correlations lc 
                        WHERE lc.bookie_id = ? ';

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aReturn = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aReturn[] = array('correlation' => $aRow['correlation'],
                'matchup_id' => $aRow['matchup_id']);
        }
        return $aReturn;
    }


    public static function getMatchupForCorrelation($a_iBookieID, $a_sCorrelation)
    {
        $aParams = array($a_iBookieID, $a_sCorrelation);

        $sQuery = 'SELECT matchup_id 
                    FROM lines_correlations 
                    WHERE bookie_id = ? AND correlation = ?';

        $rResult = DBTools::doParamQuery($sQuery, $aParams);        

        return DBTools::getSingleValue($rResult);
    }

    /**
     * Stores a collection of correlations
     * 
     * Accepts an array of correlations defined as follows:
     * 
     * array('correlation' => xxx, 'matchup_id' => xxx)
     * 
     * @param int $a_iBookieID Bookie ID
     * @param array $a_aCorrelations Collection of correlations as defined above
     */
    public static function storeCorrelations($a_iBookieID, $a_aCorrelations)
    {
        $aParams = array();

        $sQuery = 'INSERT IGNORE INTO lines_correlations(correlation, bookie_id, matchup_id) VALUES ';
        foreach ($a_aCorrelations as $aCorrelation)
        {
            if (count($aParams) > 0)
            {
                $sQuery .= ', ';
            }

            if (isset($aCorrelation['correlation']) && isset($aCorrelation['matchup_id']))
            {
                $aParams[] = $aCorrelation['correlation'];
                $aParams[] = $a_iBookieID;
                $aParams[] = $aCorrelation['matchup_id'];

                $sQuery .= '(?,?,?)';
            }
        }

        //Only execute if we are adding at least one entry
        if (count($aParams) > 0)
        {
            DBTools::doParamQuery($sQuery, $aParams);
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
        $sQuery = 'DELETE lc.* 
                    FROM lines_correlations lc 
                        LEFT JOIN fights f ON lc.matchup_id = f.id 
                        LEFT JOIN events e ON f.event_id = e.id 
                    WHERE LEFT(e.date, 10) < LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)';
        
        DBTools::doQuery($sQuery);
        $iCount = DBTools::getAffectedRows();

        //Delete orphan correlations that are related to matchups that have been removed
        $sQuery = 'DELETE lc.*
                    FROM lines_correlations lc
                        LEFT JOIN fights f ON lc.matchup_id = f.id
                    WHERE
                        f.id IS NULL';
        DBTools::doQuery($sQuery);

        $iCount += DBTools::getAffectedRows();

        return $iCount;
    }


    public static function getCompletePropsForMatchup($a_iMatchup, $a_iOffset = 0)
    {
        if ($a_iOffset != 0 && $a_iOffset != 1)
        {
            return false;
        }

        $sExtraQuery = '';
        $aParams = array($a_iMatchup, $a_iMatchup);
        if ($a_iOffset == 1)
        {

            $sExtraQuery = ' AND lp4.date != (SELECT
                MAX(lp5.date)  FROM        lines_props lp5
            WHERE
                lp5.matchup_id = ? AND lp5.bookie_id =
                lp4.bookie_id AND lp5.proptype_id = lp4.proptype_id AND lp5.team_num =
                lp4.team_num) ';
            $aParams[] = $a_iMatchup;
        }


        $sQuery = 'SELECT
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
                        lp4.matchup_id = ? ' . $sExtraQuery . ' 
                    GROUP BY bookie_id , proptype_id , team_num) AS lp3
                WHERE
                    lp2.matchup_id = ? AND lp2.bookie_id = lp3.bookie_id AND
                lp2.proptype_id = lp3.proptype_id AND lp2.team_num = lp3.team_num AND
                lp2.date = lp3.date AND lp2.proptype_id = pt.id AND lp2.bookie_id = b.id ORDER BY b.position
                ;';

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aProps = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aProps[] = new PropBet($a_iMatchup,
                            $aRow['bookie_id'],
                            $aRow['prop_desc'],
                            $aRow['prop_odds'],
                            $aRow['negprop_desc'],
                            $aRow['negprop_odds'],
                            $aRow['proptype_id'],
                            $aRow['date'],
                            $aRow['team_num']);
        }
        if (count($aProps) > 0)
        {
            return $aProps;
        }

        return null;
    }

    public static function getCompletePropsForEvent($a_iEventID, $a_iOffset = 0, $a_iBookieID = null)
    {
        if ($a_iOffset != 0 && $a_iOffset != 1)
        {
            return false;
        }

        $sExtraQuery = '';
        $aParams = array($a_iEventID, $a_iEventID);
        if ($a_iOffset == 1)
        {

            $sExtraQuery = ' AND lep4.date != (SELECT
                MAX(lep5.date)  FROM lines_eventprops lep5
            WHERE
                lep5.event_id = ? AND lep5.bookie_id =
                lep4.bookie_id AND lep5.proptype_id = lep4.proptype_id) ';
            $aParams[] = $a_iEventID;
        }
        if ($a_iBookieID != null)
        {

            $sExtraQuery = ' AND bookie_id = ? ';
            $aParams[] = $a_iBookieID;
        }

        $sQuery = 'SELECT
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

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aProps = array();
        while ($aRow = mysqli_fetch_array($rResult))
        {
            $aProps[] = new EventPropBet($a_iEventID,
                            $aRow['bookie_id'],
                            $aRow['prop_desc'],
                            $aRow['prop_odds'],
                            $aRow['negprop_desc'],
                            $aRow['negprop_odds'],
                            $aRow['proptype_id'],
                            $aRow['date']);
        }
        if (count($aProps) > 0)
        {
            return $aProps;
        }

        return null;
    }
    
    public static function removeOddsForMatchupAndBookie($a_iMatchupID, $a_iBookieID)
    {
        $sQuery = 'DELETE fo.*
                    FROM fightodds fo
                    WHERE
                        fo.fight_id = ?
                        AND fo.bookie_id = ?';
        $aParams = [$a_iMatchupID, $a_iBookieID];
        DBTools::doParamQuery($sQuery, $aParams);
        return DBTools::getAffectedRows();
    }

    public static function removePropOddsForMatchupAndBookie($a_iMatchupID, $a_iBookieID)
    {
        $sQuery = 'DELETE lp.*
                    FROM lines_props lp
                    WHERE
                        lp.matchup_id = ?
                        AND lp.bookie_id = ?';
        $aParams = [$a_iMatchupID, $a_iBookieID];
        DBTools::doParamQuery($sQuery, $aParams);
        return DBTools::getAffectedRows();
    }

    public static function getAllLatestPropOddsForMatchupAndBookie($a_iMatchupID, $a_iBookieID, $a_iPropTypeID = -1)
    {
        $sExtraWhere = '';
        $aParams = [$a_iMatchupID, $a_iBookieID];
        if ($a_iPropTypeID != -1)
        {
            $sExtraWhere = ' AND proptype_id = ?';
            $aParams[] = $a_iPropTypeID;
        }

        $sQuery = 'SELECT lp.*, pt.* 
                    FROM lines_props lp 
                    LEFT OUTER JOIN lines_props lp2
                        ON (lp.bookie_id = lp2.bookie_id AND lp.matchup_id = lp2.matchup_id  AND lp.proptype_id = lp2.proptype_id AND lp.team_num = lp2.team_num AND lp.date < lp2.date)
                        INNER JOIN prop_types pt ON lp.proptype_id = pt.id 
                    WHERE lp2.bookie_id IS NULL AND lp2.matchup_id IS NULL AND lp2.proptype_id IS NULL AND lp2.team_num IS NULL
                        AND lp.matchup_id = ? AND lp.bookie_id = ?;' . $sExtraWhere;

        $aResult = PDOTools::findMany($sQuery, $aParams);

        $aRet = [];
        foreach ($aResult as $aRow)
        {
            $aRet[] = new PropBet($aRow['matchup_id'],
                            $aRow['bookie_id'],
                            $aRow['prop_desc'],
                            $aRow['prop_odds'],
                            $aRow['negprop_desc'],
                            $aRow['negprop_odds'],
                            $aRow['proptype_id'],
                            $aRow['date'],
                            $aRow['team_num']);
        }
        return $aRet;
    }

    public static function flagOddsForDeletion($a_iBookieID, $a_iMatchupID = null, $a_iEventID = null, $a_iPropTypeID = null, $a_iTeamNum = null)
    {
		if (!is_numeric($a_iBookieID) || ($a_iMatchupID == null && $a_iEventID == null))
		{
			throw new Exception("Invalid input", 10);	
        }

        $sQuery = 'INSERT INTO lines_flagged(bookie_id, matchup_id, event_id, proptype_id, team_num, initial_flagdate, last_flagdate) VALUES (?,?,?,?,?, NOW(), NOW()) ON DUPLICATE KEY UPDATE last_flagdate = NOW()';
        $aParams = [$a_iBookieID, $a_iMatchupID, $a_iEventID ?? -1, $a_iPropTypeID ?? -1, $a_iTeamNum ?? -1];

        $id = null;
		try 
		{
			$id = PDOTools::insert($sQuery, $aParams);
		}
		catch(PDOException $e)
		{
            if($e->getCode() == 23000)
            {
				throw new Exception("Duplicate entry", 10);	
            }
            else
            {
                throw new Exception("Unknown error " . $e->getMessage(), 10);	
            }
            

		}
		return $id;
    }

    public static function checkIfFlagged($a_iBookieID, $a_iMatchupID, $a_iEventID, $a_iPropTypeID, $a_iTeamNum)
    {
        $sWhere = '';
        $aParams = [$a_iBookieID];
        if ($a_iMatchupID != null)
        {
            $sWhere .= ' AND matchup_id = ? ';
            $aParams[] = $a_iMatchupID;
        }
        if ($a_iEventID != null)
        {
            $sWhere .= ' AND event_id = ? ';
            $aParams[] = $a_iEventID;
        }
        if ($a_iPropTypeID != null)
        {
            $sWhere .= ' AND proptype_id = ? ';
            $aParams[] = $a_iPropTypeID;
        }
        if ($a_iTeamNum != null)
        {
            $sWhere .= ' AND team_num = ? ';
            $aParams[] = $a_iTeamNum;
        }

        $sQuery = 'SELECT * FROM lines_flagged WHERE bookie_id = ? ' . $sWhere;

        $result = null;
        try 
		{
			$result = PDOTools::findMany($sQuery, $aParams);
		}
		catch(PDOException $e)
		{
            throw new Exception("Unknown error " . $e->getMessage(), 10);	
        }
        return $result;
        
    }

    public static function removeFlagged($a_iBookieID, $a_iMatchupID = null, $a_iEventID = null, $a_iPropTypeID = null, $a_iTeamNum = null)
    {
        $sWhere = '';
        $aParams = [$a_iBookieID];
        if ($a_iMatchupID != null)
        {
            $sWhere .= ' AND matchup_id = ? ';
            $aParams[] = $a_iMatchupID;
        }
        if ($a_iEventID != null)
        {
            $sWhere .= ' AND event_id = ? ';
            $aParams[] = $a_iEventID;
        }
        if ($a_iPropTypeID != null)
        {
            $sWhere .= ' AND proptype_id = ? ';
            $aParams[] = $a_iPropTypeID;
        }
        if ($a_iTeamNum != null)
        {
            $sWhere .= ' AND team_num = ? ';
            $aParams[] = $a_iTeamNum;
        }

        $sQuery = 'DELETE FROM lines_flagged WHERE bookie_id = ? ' . $sWhere;

        $result = null;
        try 
		{
			$result = PDOTools::delete($sQuery, $aParams);
		}
		catch(PDOException $e)
		{
            throw new Exception("Unknown error " . $e->getMessage(), 10);	
        }
        return $result;
        
    }


    
    public static function getLatestPropOddsV2($a_iEventID = null, $a_iMatchupID = null, $a_iBookieID = null, $a_iPropTypeID = null, $a_iTeamNum = null)
    {
        $aParams = [];
        $sExtraWhere = '';
        if ($a_iEventID != null)
        {
            $sExtraWhere .= ' AND e.id = ? ';
            $aParams[] = $a_iEventID;
        }
        if ($a_iMatchupID != null)
        {
            $sExtraWhere .= ' AND f.id = ? ';
            $aParams[] = $a_iMatchupID;
        }
        if ($a_iBookieID != null)
        {
            $sExtraWhere .= ' AND lp.bookie_id = ? ';
            $aParams[] = $a_iBookieID;
        }
        if ($a_iPropTypeID != null)
        {
            $sExtraWhere .= ' AND pt.id = ? ';
            $aParams[] = $a_iPropTypeID;
        }
        if ($a_iTeamNum != null)
        {
            $sExtraWhere .= ' AND lp.team_num = ? ';
            $aParams[] = $a_iTeamNum;
        }

        $sQuery = 'select e.*, f.*, lp.*, pt.*, lp2.prop_odds as previous_prop_odds, lp2.negprop_odds as previous_negprop_odds from events e 
                LEFT JOIN fights f ON e.id = f.event_id 
                LEFT JOIN lines_props lp ON f.id = lp.matchup_id
                LEFT JOIN prop_types pt ON lp.proptype_id = pt.id
                LEFT JOIN lines_props lp2 ON lp.matchup_id = lp2.matchup_id AND lp.proptype_id = lp2.proptype_id AND lp.bookie_id = lp2.bookie_id AND lp.team_num = lp2.team_num  AND lp2.date = (SELECT MAX(date) FROM lines_props lp3 WHERE lp.bookie_id = lp3.bookie_id AND lp.matchup_id = lp3.matchup_id AND lp.proptype_id = lp3.proptype_id AND lp.team_num = lp3.team_num AND lp3.date < lp.date)
            WHERE lp.date = (SELECT MAX(lpd.date) FROM lines_props lpd WHERE lp.bookie_id = lpd.bookie_id AND lp.matchup_id = lpd.matchup_id AND lp.proptype_id = lpd.proptype_id AND lp.team_num = lpd.team_num) 
            ' . $sExtraWhere . ' 
            ORDER BY LEFT(pt.prop_desc,4) = "Over" DESC ';

        $ret = null;
        try 
        {
            $ret = PDOTools::findMany($sQuery, $aParams);
        }
        catch(PDOException $e)
        {
            throw new Exception("Unknown error " . $e->getMessage(), 10);	
            return false;
        }
        return $ret;

    }

    public static function getLatestEventPropOddsV2($a_iEventID, $a_iBookieID = null, $a_iPropTypeID = null)
    {
        $aParams = [];
        $aParams[] = $a_iEventID;
        $sExtraWhere = '';
        if ($a_iEventID == null) //Required
        {
            return false;
        }
        if ($a_iBookieID != null)
        {
            $sExtraWhere .= ' AND pt.bookie_id = ? ';
            $aParams[] = $a_iBookieID;
        }
        if ($a_iPropTypeID != null)
        {
            $sExtraWhere .= ' AND pt.id = ? ';
            $aParams[] = $a_iPropTypeID;
        }

        $sQuery = 'SELECT e.*, lp.*, pt.*, lp2.prop_odds as previous_prop_odds, lp2.negprop_odds as previous_negprop_odds from events e 
                    LEFT JOIN lines_eventprops lp ON e.id = lp.event_id
                    LEFT JOIN prop_types pt ON lp.proptype_id = pt.id
                    LEFT JOIN lines_eventprops lp2 ON lp.event_id = lp2.event_id AND lp.proptype_id = lp2.proptype_id AND lp.bookie_id = lp2.bookie_id AND lp2.date = (SELECT MAX(date) FROM lines_eventprops lp3 WHERE lp.bookie_id = lp3.bookie_id AND lp.event_id = lp3.event_id AND lp.proptype_id = lp3.proptype_id AND lp3.date < lp.date)
                WHERE lp.date = (SELECT MAX(lpd.date) FROM lines_eventprops lpd WHERE lp.bookie_id = lpd.bookie_id AND lp.event_id = lpd.event_id AND lp.proptype_id = lpd.proptype_id) 
                AND e.id = ? 
                ' . $sExtraWhere . ' 
                ORDER BY pt.id ASC;';
        
        $ret = null;
        try 
        {
            $ret = PDOTools::findMany($sQuery, $aParams);
        }
        catch(PDOException $e)
        {
            throw new Exception("Unknown error " . $e->getMessage(), 10);	
            return false;
        }
        return $ret;

    }

    public static function getLatestMatchupOddsV2($a_iEventID = null, $a_iMatchupID = null)
    {
        $aParams = [];
        $sExtraWhere = '';

        if ($a_iEventID == null && $a_iMatchupID == null) //Either event ID or matchup ID needs to be specified
        {
            return false;
        }

        if ($a_iEventID != null)
        {
            $sExtraWhere .= ' AND e.id = ? ';
            $aParams[] = $a_iEventID;
        }
        if ($a_iMatchupID != null)
        {
            $sExtraWhere .= ' AND f.id = ? ';
            $aParams[] = $a_iMatchupID;
        }

        $sQuery = 'select e.*, f.*, fo.*, fo2.fighter1_odds as previous_team1_odds, fo2.fighter2_odds as previous_team2_odds from events e 
                    LEFT JOIN fights f ON e.id = f.event_id 
                    LEFT JOIN fightodds fo ON f.id = fo.fight_id
                    LEFT JOIN fightodds fo2 ON fo.fight_id = fo2.fight_id AND fo.bookie_id = fo2.bookie_id AND fo2.date = (SELECT MAX(date) FROM fightodds fo3 WHERE fo.bookie_id = fo3.bookie_id AND fo.fight_id = fo3.fight_id AND fo3.date < fo.date)
                WHERE fo.date = (SELECT MAX(fod.date) FROM fightodds fod WHERE fo.bookie_id = fod.bookie_id AND fo.fight_id = fod.fight_id)  
        ' . $sExtraWhere . '';

        $ret = null;
        try 
        {
            $ret = PDOTools::findMany($sQuery, $aParams);
        }
        catch(PDOException $e)
        {
            throw new Exception("Unknown error " . $e->getMessage(), 10);	
            return false;
        }
        return $ret;

    }

}

?>
