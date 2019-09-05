<?php

require_once('lib/bfocore/utils/db/class.DBTools.php');
require_once('config/inc.dbConfig.php');
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
    /*public static function getSwingForMatchup($a_iMatchupID)
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
    }*/

    public static function getDiffForMatchup($a_iMatchup, $a_iFrom = 0) //0 Opening, 1 = 1 day ago, 2 = 1 hour ago
    {
        if ($a_iMatchup == null)
        {
            return false;
        }

        //Gets the last update date (or matchup date) for past events:
        //select FROM_UNIXTIME (mvalue) - INTERVAL 6 HOUR AS lasttime from matchups_metadata where matchup_id = 12155 UNION SELECT max(date) as lasttime from fightodds where fight_id = 12155 order by lasttime desc;


        $aParams = [];
        //This logic checks if we are requesting odds from the last hour or last 24 hours. If so, we check if the event is in the past or not. If in the past we want to use either the last odds date or the fight time (from metadata) as the equivalent of now()

        /*
Gets matchup time and last updated odds time
select FROM_UNIXTIME (mvalue) - INTERVAL 6 HOUR AS lasttime from matchups_metadata where matchup_id = 12155 UNION SELECT max(date) as lasttime from fightodds where fight_id = 12155 order by lasttime desc;


Checks if event is future
SELECT EXISTS (select 1 from fights f inner join events e on f.event_id = e.id where f.id = 12057 AND LEFT(date, 10) >= LEFT((NOW() - INTERVAL 2 HOUR), 10)) exi;

Can we combine now(), matchup time and last update odds and get the truth somehow? In combination with the check event is future ofc
SELECT * FROM (
SELECT FROM_UNIXTIME (mvalue) - INTERVAL 6 HOUR AS lasttime
    FROM matchups_metadata
    WHERE matchup_id = 12155 UNION
SELECT MAX(DATE) AS lasttime
    FROM fightodds
    WHERE fight_id = 12155
    ORDER BY storedtime DESC) f1 UNION
SELECT NOW() AS lasttime, 'NULL' as storedtime
ORDER BY storedtime, lasttime DESC;




        */

        $sExtraWhere = '';
        $sDateCompare = '>';
        if ($a_iFrom != 0)
        {
            //This query is used when a time slice is used, for example last 24 hours or last hour. We need to check if the event is in the past so that past events dont utilize NOW() - 1 HOUR as last hour. The check is as follows: 
            //NOW() < METADATA = NOW()
            //NOW() > METADATA = METADATA UNLESS LAST ODDS > METADATA
            //!METADATA, IS_PAST( YES = LAST ODDS, NO = NOW() )

            $sExtraWhere = " AND fo1.date <= (IF ((SELECT 1 FROM matchups_metadata mm WHERE matchup_id = ? AND mm.mattribute = 'gametime' ), 
                                                    /*Metadata exists*/
                                                    IF ((SELECT FROM_UNIXTIME(mm.mvalue) + INTERVAL " . DB_TIMEZONE . " HOUR FROM matchups_metadata mm WHERE mm.matchup_id = ? AND mm.mattribute = 'gametime' ) > NOW(), 
                                                        /*Metadata > NOW()*/
                                                        NOW(), 
                                                        /*Metadata < NOW()*/
                                                        (SELECT FROM_UNIXTIME(mm.mvalue) + INTERVAL " . DB_TIMEZONE . " HOUR FROM matchups_metadata mm WHERE mm.matchup_id = ? AND mm.mattribute = 'gametime')),
                                                    /*Metadata does not exist*/
                                                    IF ((SELECT 1 FROM fights f INNER JOIN events e ON f.event_id = e.id WHERE f.id = ? AND LEFT(e.date, 10) < LEFT((NOW() - INTERVAL 2 HOUR), 10)), 
                                                        /*Event is in past*/
                                                        (SELECT MAX(fo.date) FROM fightodds fo WHERE fo.fight_id = ?), 
                                                        /*Event is upcoming*/
                                                        NOW())
                                                ))";
            $aParams[] = $a_iMatchup;
            $aParams[] = $a_iMatchup;
            $aParams[] = $a_iMatchup;
            $aParams[] = $a_iMatchup;
            $aParams[] = $a_iMatchup;
            $aParams[] = $a_iMatchup;
            $aParams[] = $a_iMatchup;
            $aParams[] = $a_iMatchup;
                        $aParams[] = $a_iMatchup;
            $aParams[] = $a_iMatchup;
            $sDateCompare = '<';

            if ($a_iFrom == 1) //24 hour
            {
                $sExtraWhere .= " - INTERVAL 1 DAY ";
            }
            else if ($a_iFrom == 2) //1 Hour
            {
                $sExtraWhere .= " - INTERVAL 1 HOUR ";
            }

        }

        $sQuery = 'SELECT 
                        opening.avg_f1odds as opf1,
                        opening.avg_f2odds as opf2,
                        latest.avg_f1odds as laf1,
                        latest.avg_f2odds as laf2,
                        (opening.avg_f1odds - latest.avg_f1odds)/(opening.avg_f1odds - 1) as f1swing,           
                        (opening.avg_f2odds - latest.avg_f2odds)/(opening.avg_f2odds - 1) as f2swing
                    from
                        ((SELECT AVG(f1.dec_f1odds) AS avg_f1odds, AVG(f1.dec_f2odds) AS avg_f2odds 
                                FROM (SELECT MoneylineToDecimal(m1.fighter1_odds) AS dec_f1odds, MoneylineToDecimal(m1.fighter2_odds) AS dec_f2odds, m1.*
                                    FROM (SELECT fo1.* FROM fightodds fo1 WHERE fight_id = ? ' . $sExtraWhere . ') m1 LEFT JOIN (SELECT fo1.* FROM fightodds fo1 WHERE fight_id = ? ' . $sExtraWhere . ') m2
                                        ON (m1.fight_id = m2.fight_id AND m1.bookie_id = m2.bookie_id AND m1.date ' . $sDateCompare . ' m2.date)
                                        WHERE m2.date IS NULL AND m1.fight_id = ?) f1) opening
                        JOIN 
                            (SELECT AVG(f1.dec_f1odds) AS avg_f1odds, AVG(f1.dec_f2odds) AS avg_f2odds 
                                FROM (SELECT MoneylineToDecimal(m1.fighter1_odds) AS dec_f1odds, MoneylineToDecimal(m1.fighter2_odds) AS dec_f2odds, m1.*
                                    FROM fightodds m1 LEFT JOIN fightodds m2
                                        ON (m1.fight_id = m2.fight_id AND m1.bookie_id = m2.bookie_id AND m1.date < m2.date)
                                        WHERE m2.date IS NULL AND m1.fight_id = ?) f1) latest)';

        $aParams[] = $a_iMatchup;
        $aParams[] = $a_iMatchup;
        $aParams[] = $a_iMatchup;
        $aParams[] = $a_iMatchup;
        $rResult = DBTools::doParamQuery($sQuery, $aParams);



        if ($aRow = mysqli_fetch_array($rResult))
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
