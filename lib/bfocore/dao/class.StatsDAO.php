<?php

require_once('lib/bfocore/utils/db/class.DBTools.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/parser/utils/class.ParseTools.php'); //TODO: Try to avoid having dependecies to the parsing component. Move this functionality to another class in the general library

/**
 * Statistics DAO
 *
 * @author Christian
 */
class StatsDAO
{
    /**
     * Top Swings (most movement in terms of line value)
    */
    public static function getSwingForMatchup($a_iMatchupID)
    {
        $sQuery = 'select 
                        sum(allswings)
                    from
                        (select 
                            fo2 . *,
                                @ml1:=MoneylineToDecimal(fo1.fighter1_odds),
                                @ml2:=MoneylineToDecimal(fo2.fighter1_odds),
                                IF(@ml1 > @ml2, @ml1 - @ml2, IF(@ml1 < @ml2, @ml2 - @ml1, 0)) as allswings
                        from
                            (SELECT 
                            *, @curRow1:=@curRow1 + 1 AS row_number
                        FROM
                            fightodds, (SELECT @curRow1:=0) r1
                        WHERE
                            fight_id = ?
                        order by date asc) fo1, (SELECT 
                            *, @curRow2:=@curRow2 + 1 AS row_number
                        FROM
                            fightodds, (SELECT @curRow2:=0) r2
                        WHERE
                            fight_id = ?
                        order by date asc) fo2
                        where
                            fo1.fight_id = ?
                                and fo1.row_number + 1 = fo2.row_number) fo4;';

        $aParams = array($a_iMatchupID, $a_iMatchupID, $a_iMatchupID);
        return DBTools::getSingleValue(DBTools::doParamQuery($sQuery, $aParams));
    }

    public static function getDiffForMatchup($a_iMatchup, $a_iFrom = -1) //-1 Opening, 1 = 1 day ago, 2 = 1 hour ago
    {
        if ($a_iMatchup == null)
        {
            return false;
        }

        //This query gets diff no matter if favourite or underdog:
        /*$sQuery = 'select 
                        opening.fighter1_odds as opf1,
                        opening.fighter2_odds as opf2,
                        latest.fighter1_odds as laf1,
                        latest.fighter2_odds as laf2,
                        @ml11:=MoneylineToDecimal(opening.fighter1_odds),
                        @ml12:=MoneylineToDecimal(opening.fighter2_odds),
                        @ml21:=MoneylineToDecimal(latest.fighter1_odds),
                        @ml22:=MoneylineToDecimal(latest.fighter2_odds),
                                                IF(@ml11 > @ml12,
                            @ml11 - @ml12,
                            IF(@ml11 < @ml12, @ml12 - @ml11, 0)) as f1swing,
                        IF(@ml21 > @ml22,
                            @ml21 - @ml22,
                            IF(@ml21 < @ml22, @ml22 - @ml21, 0)) as f2swing
                    from
                        ((SELECT 
                            fighter1_odds, fighter2_odds
                        FROM
                            fightodds
                        WHERE
                            fight_id = ?
                        ORDER BY DATE ASC
                        LIMIT 0 , 1) opening
                        join (SELECT 
                            fighter1_odds, fighter2_odds
                        FROM
                            fightodds
                        WHERE
                            fight_id = ?
                        ORDER BY DATE DESC
                        LIMIT 0 , 1) latest)';*/

        $sQuery = 'select 
                        opening.fighter1_odds as opf1,
                        opening.fighter2_odds as opf2,
                        latest.fighter1_odds as laf1,
                        latest.fighter2_odds as laf2,
                        @ml11:=MoneylineToDecimal(opening.fighter1_odds),
                        @ml12:=MoneylineToDecimal(opening.fighter2_odds),
                        @ml21:=MoneylineToDecimal(latest.fighter1_odds),
                        @ml22:=MoneylineToDecimal(latest.fighter2_odds),
                        @ml11 - @ml21 as f1swing,
                        @ml12 - @ml22 as f2swing
                    from
                        ((SELECT 
                            fighter1_odds, fighter2_odds
                        FROM
                            fightodds
                        WHERE
                            fight_id = ?
                        ORDER BY DATE ASC
                        LIMIT 0 , 1) opening
                        join (SELECT 
                            fighter1_odds, fighter2_odds
                        FROM
                            fightodds
                        WHERE
                            fight_id = ?
                        ORDER BY DATE DESC
                        LIMIT 0 , 1) latest)';

        $aParams = array($a_iMatchup, $a_iMatchup);
        $rResult = DBTools::doParamQuery($sQuery, $aParams);
        if ($aRow = mysql_fetch_array($rResult))
        {
            return array('f1' => array(
                            'opening' => $aRow['opf1'],
                            'latest' => $aRow['laf1'],
                            'swing' => $aRow['f1swing']), 
                    'f2' => array(
                            'opening' => $aRow['opf2'],
                            'latest' => $aRow['laf2'],
                            'swing' => $aRow['f2swing']
                        ));
        }
        return false;
    }
}
?>
