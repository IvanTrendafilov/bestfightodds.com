<?php

require_once('lib/bfocore/utils/db/class.DBTools.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');

class OddsDAO
{

    /**
     * Store a new Spread line. If no set ID is specified, a new set will be created.
     *
     * @param SpreadOdds $a_oSpreadOdds
     * @return boolean success
     */
    public static function addSingleSpread($a_oSpreadOdds, $a_iSetID = null)
    {
        if (count($a_oSpreadOdds) < 1)
        {
            return false;
        }

        $iSetID = $a_iSetID;

        if ($a_iSetID == null)
        {
            $bSuccess = OddsDAO::addSpreadSet($a_aSpreadOdds[0]->getMatchupID(), $a_aSpreadOdds[0]->getBookieID());
            if ($bSuccess == true)
            {
                $iSetID = DBTools::getLatestID();
            }
            else
            {
                return false;
            }
        }

        $sQuery = 'INSERT INTO lines_spread(team1_line, team2_line, team1_spread, team2_spread, set_id)
                        VALUES(?, ?, ?, ?, ?)';

        $aParams = array($a_oSpreadOdds->getMoneyline(1),
            $a_oSpreadOdds->getMoneyline(2),
            $a_oSpreadOdds->getSpread(1),
            $a_oSpreadOdds->getSpread(2),
            $iSetID);

        DBTools::doParamQuery($sQuery, $aParams);

        return (DBTools::getAffectedRows() > 0 ? true : false);
    }

    public static function addMultipleSpreads($a_aSpreadOdds, $a_iSetID = null)
    {
        $bSuccess = false;
        if ($a_iSetID == null)
        {
            $bSuccess = OddsDAO::addSpreadSet($a_aSpreadOdds[0]->getMatchupID(), $a_aSpreadOdds[0]->getBookieID());
        }

        if ($bSuccess == true)
        {
            $iSetID = DBTools::getLatestID();
            foreach ($a_aSpreadOdds as $oSpreadOdds)
            {
                OddsDAO::addSingleSpread($oSpreadOdds, $iSetID);
            }
            return true;
        }
        return false;
    }

    private static function addSpreadSet($a_iMatchupID, $a_iBookieID)
    {
        $sQuery = "INSERT INTO lines_spread_set(matchup_id, bookie_id, date)
                        VALUES(?, ?, NOW());";

        DBTools::doParamQuery($sQuery, array($a_iMatchupID, $a_iBookieID));

        return (DBTools::getAffectedRows() > 0 ? true : false);
    }

    /**
     * Store a new Totals line
     *
     * @param TotalOdds $a_oTotalOdds
     * @return boolean success
     */
    public static function addSingleTotals($a_oTotalOdds, $a_iSetID = null)
    {
        if (count($a_oTotalOdds) < 1)
        {
            return false;
        }

        $iSetID = $a_iSetID;

        if ($a_iSetID == null)
        {
            $bSuccess = OddsDAO::addTotalsSet($a_oTotalOdds[0]->getMatchupID(), $a_oTotalOdds[0]->getBookieID());
            if ($bSuccess == true)
            {
                $iSetID = DBTools::getLatestID();
            }
            else
            {
                return false;
            }
        }

        $sQuery = 'INSERT INTO lines_totals(totalpoints, over_line, under_line, set_id)
                        VALUES(?, ?, ?, ?)';

        $aParams = array($a_oTotalOdds->getTotal(),
            $a_oTotalOdds->getOverMoneyline(),
            $a_oTotalOdds->getUnderMoneyline(),
            $iSetID);

        DBTools::doParamQuery($sQuery, $aParams);

        return (DBTools::getAffectedRows() > 0 ? true : false);
    }

    public static function addMultipleTotals($a_aTotalsOdds, $a_iSetID = null)
    {
        $bSuccess = false;
        if ($a_iSetID == null)
        {
            $bSuccess = OddsDAO::addTotalsSet($a_aTotalsOdds[0]->getMatchupID(), $a_aTotalsOdds[0]->getBookieID());
        }

        if ($bSuccess == true)
        {
            $iSetID = DBTools::getLatestID();
            foreach ($a_aTotalsOdds as $oTotalsOdds)
            {
                OddsDAO::addSingleTotals($oTotalsOdds, $iSetID);
            }
            return true;
        }
        return false;
    }

    private static function addTotalsSet($a_iMatchupID, $a_iBookieID)
    {
        $sQuery = "INSERT INTO lines_totals_set(matchup_id, bookie_id, date)
                        VALUES(?, ?, NOW());";

        DBTools::doParamQuery($sQuery, array($a_iMatchupID, $a_iBookieID));

        return (DBTools::getAffectedRows() > 0 ? true : false);
    }

    /**
     * Retrieve all latest spreads for the specified matchup
     */
    public static function getLatestSpreadsForMatchup($a_iMatchupID, $a_iOffset = 0)
    {
        if (!is_numeric($a_iOffset))
        {
            return false;
        }

        $sQuery = 'SELECT lss1.matchup_id, lss1.id, lss1.bookie_id, lss1.date
                    FROM (SELECT lss2.matchup_id, (SELECT lss3.id
                                                      FROM lines_spread_set lss3
                                                      WHERE lss3.matchup_id = lss2.matchup_id
                                                          AND lss3.bookie_id = lss2.bookie_id
                                                      ORDER BY date DESC
                                                      LIMIT ' . DBTools::makeParamSafe($a_iOffset) . ',1) AS id,
                                                    lss2.bookie_id,
                                                    (SELECT lss4.date
                                                      FROM lines_spread_set lss4
                                                      WHERE lss4.matchup_id = lss2.matchup_id
                                                          AND lss4.bookie_id = lss2.bookie_id
                                                      ORDER BY lss4.date DESC
                                                      LIMIT ' . DBTools::makeParamSafe($a_iOffset) . ',1) AS date
                            FROM lines_spread_set lss2, bookies bo
                            WHERE lss2.matchup_id = ?
                                AND lss2.bookie_id = bo.id
                            GROUP BY lss2.bookie_id
                            ORDER BY bo.position, lss2.bookie_id, lss2.matchup_id ASC) lss1
                    WHERE lss1.date IS NOT NULL;';

        $rResult = DBTools::doParamQuery($sQuery, array($a_iMatchupID));

        $aSpreadsCol = array();
        while ($aRow = mysql_fetch_array($rResult))
        {
            $aSpreadsCol[] = new SpreadOddsSet($aRow['id'], $aRow['matchup_id'], $aRow['bookie_id'], $aRow['date']);

            $aSpreadsCol[count($aSpreadsCol) - 1]->setSpreadOddsCol(OddsDAO::getSpreadsForSet($aSpreadsCol[count($aSpreadsCol) - 1]));
        }

        return $aSpreadsCol;
    }

    /**
     * Retrieve all latest totals for the specified matchup
     */
    public static function getLatestTotalsForMatchup($a_iMatchupID, $a_iOffset = 0)
    {
        if (!is_numeric($a_iOffset))
        {
            return false;
        }


        $sQuery = 'SELECT lss1.matchup_id, lss1.id, lss1.bookie_id, lss1.date
            FROM (SELECT lss2.matchup_id, (SELECT lss3.id
                                              FROM lines_totals_set lss3
                                              WHERE lss3.matchup_id = lss2.matchup_id
                                                  AND lss3.bookie_id = lss2.bookie_id
                                              ORDER BY date DESC
                                              LIMIT ' . DBTools::makeParamSafe($a_iOffset) . ',1) AS id,
                                            lss2.bookie_id,
                                            (SELECT lss4.date
                                              FROM lines_totals_set lss4
                                              WHERE lss4.matchup_id = lss2.matchup_id
                                                  AND lss4.bookie_id = lss2.bookie_id
                                              ORDER BY lss4.date DESC
                                              LIMIT ' . DBTools::makeParamSafe($a_iOffset) . ',1) AS date
                    FROM lines_totals_set lss2, bookies bo
                    WHERE lss2.matchup_id = ?
                        AND lss2.bookie_id = bo.id
                    GROUP BY lss2.bookie_id
                    ORDER BY bo.position, lss2.bookie_id, lss2.matchup_id ASC) lss1
            WHERE lss1.date IS NOT NULL;';

        $rResult = DBTools::doParamQuery($sQuery, array($a_iMatchupID));

        $aTotalsCol = array();
        while ($aRow = mysql_fetch_array($rResult))
        {
            $aTotalsCol[] = new TotalOddsSet($aRow['id'], $aRow['matchup_id'], $aRow['bookie_id'], $aRow['date']);

            $aTotalsCol[count($aTotalsCol) - 1]->setTotalOddsCol(OddsDAO::getTotalsForSet($aTotalsCol[count($aTotalsCol) - 1]));
        }

        return $aTotalsCol;
    }

    /**
     * Get the latest spreads for the specified matchup and bookie
     *
     * @param int $a_iMatchupID Matchup ID
     * @param int $a_iBookieID Bookie ID
     * @return SpreadOddsSet Spread odds set
     */
    public static function getLatestSpreadsForMatchupAndBookie($a_iMatchupID, $a_iBookieID)
    {
        $sQuery = 'SELECT lss.id, lss.matchup_id, lss.bookie_id, lss.date
                    FROM lines_spread_set lss
                    WHERE lss.matchup_id = ?
                        AND lss.bookie_id = ?
                    ORDER BY lss.date DESC
                    LIMIT 0,1;';

        $aParams = array($a_iMatchupID, $a_iBookieID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        if (($aRow = mysql_fetch_array($rResult)))
        {
            $oSpreadSet = new SpreadOddsSet($aRow['id'], $aRow['matchup_id'], $aRow['bookie_id'], $aRow['date']);

            $oSpreadSet->setSpreadOddsCol(OddsDAO::getSpreadsForSet($oSpreadSet));

            return $oSpreadSet;
        }
        else
        {
            return false;
        }
    }

    private static function getSpreadsForSet($a_oSpreadSet)
    {
        $sQuery = 'SELECT ls.team1_line, ls.team2_line, ls.team1_spread, ls.team2_spread
                    FROM lines_spread ls
                    WHERE ls.set_id = ?
                    ORDER BY ls.team1_line ASC';

        $rResult = DBTools::doParamQuery($sQuery, array($a_oSpreadSet->getID()));

        $aSpreadOdds = array();
        while ($aRow = mysql_fetch_array($rResult))
        {
            $aSpreadOdds[] = new SpreadOdds($a_oSpreadSet->getMatchupID(),
                            $a_oSpreadSet->getBookieID(),
                            $aRow['team1_spread'],
                            $aRow['team2_spread'],
                            $aRow['team1_line'],
                            $aRow['team2_line'],
                            $a_oSpreadSet->getDate());
        }
        return $aSpreadOdds;
    }

    public static function getLatestTotalsForMatchupAndBookie($a_iMatchupID, $a_iBookieID)
    {
        $sQuery = 'SELECT lts.id, lts.matchup_id, lts.bookie_id, lts.date
                    FROM lines_totals_set lts
                    WHERE lts.matchup_id = ?
                        AND lts.bookie_id = ?
                    ORDER BY lts.date DESC
                    LIMIT 0,1;';

        $aParams = array($a_iMatchupID, $a_iBookieID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        if (($aRow = mysql_fetch_array($rResult)))
        {
            $oTotalsSet = new TotalOddsSet($aRow['id'], $aRow['matchup_id'], $aRow['bookie_id'], $aRow['date']);

            $oTotalsSet->setTotalOddsCol(OddsDAO::getTotalsForSet($oTotalsSet));

            return $oTotalsSet;
        }
        else
        {
            return false;
        }
    }

    private static function getTotalsForSet($a_oTotalsSet)
    {
        $sQuery = 'SELECT lt.totalpoints, lt.over_line, lt.under_line 
                    FROM lines_totals lt
                    WHERE lt.set_id = ?
                    ORDER BY lt.totalpoints ASC';

        $rResult = DBTools::doParamQuery($sQuery, array($a_oTotalsSet->getID()));

        $aTotalsOdds = array();
        while ($aRow = mysql_fetch_array($rResult))
        {
            $aTotalsOdds[] = new TotalOdds($a_oTotalsSet->getMatchupID(),
                            $a_oTotalsSet->getBookieID(),
                            $aRow['totalpoints'],
                            $aRow['over_line'],
                            $aRow['under_line'],
                            $a_oTotalsSet->getDate());
        }
        return $aTotalsOdds;
    }

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

        DBTools::doParamQuery($sQuery, $aParams);

        return (DBTools::getAffectedRows() > 0 ? true : false);
    }

    public static function getPropBetsForMatchup($a_iMatchupID)
    {
        $sQuery = 'SELECT lp.bookie_id, lp.prop_odds, lp.negprop_odds, lp.proptype_id, lp.date, pt.prop_desc, pt.negprop_desc, lp.date, lp.team_num
                    FROM lines_props lp, prop_types pt
                    WHERE lp.matchup_id = ?
                        AND lp.proptype_id = pt.id
                        ORDER BY pt.prop_desc ASC';
        $aParams = array($a_iMatchupID);
        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aProps = array();
        while ($aRow = mysql_fetch_array($rResult))
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

    public static function getAllPropTypes()
    {
        $sQuery = 'SELECT pt.id, pt.prop_desc, pt.negprop_desc
                    FROM prop_types pt
                    ORDER BY LEFT(pt.prop_desc,4) = "Over" DESC, id ASC';

        $rResult = DBTools::doQuery($sQuery);

        $aPropTypes = array();
        while ($aRow = mysql_fetch_array($rResult))
        {
            $aPropTypes[] = new PropType($aRow['id'],
                            $aRow['prop_desc'],
                            $aRow['negprop_desc']);
        }

        return $aPropTypes;
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
                    ORDER BY LEFT(pt.prop_desc,4) = "Over" DESC, id ASC';

        $aParams = array($a_iMatchupID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aPropTypes = array();
        while ($aRow = mysql_fetch_array($rResult))
        {
            $aPropTypes[] = new PropType($aRow['id'],
                            $aRow['prop_desc'],
                            $aRow['negprop_desc'],
                            $aRow['team_num']);
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
        while ($aRow = mysql_fetch_array($rResult))
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

        if ($aRow = mysql_fetch_array($rResult))
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
        while ($aRow = mysql_fetch_array($rResult))
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
        while ($aFightOdds = mysql_fetch_array($rResult))
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
        while ($aFightOdds = mysql_fetch_array($rResult))
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
        while ($aRow = mysql_fetch_array($rResult))
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
        while ($aRow = mysql_fetch_array($rResult))
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
        while ($aRow = mysql_fetch_array($rResult))
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
                    WHERE LEFT(e.date, 10) < LEFT((NOW() - INTERVAL 1 HOUR), 10)';
        
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
        while ($aRow = mysql_fetch_array($rResult))
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

}

?>
