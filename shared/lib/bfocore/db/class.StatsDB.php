<?php

require_once('lib/bfocore/utils/db/class.DBTools.php');
require_once('config/inc.config.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/parser/utils/class.ParseTools.php'); //TODO: Try to avoid having dependecies to the parsing component. Move this functionality to another class in the general library

/**
 * Statistics DB
 */
class StatsDB
{
    public static function getDiffForMatchup($matchup_id, $from = 0) //0 Opening, 1 = 1 day ago, 2 = 1 hour ago
    {
        if ($matchup_id == null) {
            return false;
        }
 
        $query = '';
        $params = [];

        if ($from == 0) {
            //From opening
            $query = 'SELECT 
                opening.avg_f1odds as opf1,
                opening.avg_f2odds as opf2,
                latest.avg_f1odds as laf1,
                latest.avg_f2odds as laf2,
                (opening.avg_f1odds - latest.avg_f1odds)/(opening.avg_f1odds - 1) as f1swing,           
                (opening.avg_f2odds - latest.avg_f2odds)/(opening.avg_f2odds - 1) as f2swing
            FROM
                ((SELECT MoneylineToDecimal(fo1.fighter1_odds) AS avg_f1odds, MoneylineToDecimal(fo1.fighter2_odds) AS avg_f2odds FROM fightodds fo1 WHERE fight_id = ? ORDER BY DATE ASC LIMIT 1) opening
                JOIN 
                    (SELECT AVG(f1.dec_f1odds) AS avg_f1odds, AVG(f1.dec_f2odds) AS avg_f2odds 
                        FROM (SELECT MoneylineToDecimal(m1.fighter1_odds) AS dec_f1odds, MoneylineToDecimal(m1.fighter2_odds) AS dec_f2odds, m1.*
                            FROM fightodds m1 LEFT JOIN fightodds m2
                                ON (m1.fight_id = m2.fight_id AND m1.bookie_id = m2.bookie_id AND m1.date < m2.date)
                                WHERE m2.date IS NULL AND m1.fight_id = ?) f1) latest)';
                                
            $params[] = $matchup_id;
            $params[] = $matchup_id;
        } else {
            //Last day or hour

            //This query is used when a time slice is used, for example last 24 hours or last hour. We need to check if the event is in the past so that past events dont utilize NOW() - 1 HOUR as last hour. The check is as follows:
            //NOW() < METADATA = NOW()
            //NOW() > METADATA = METADATA UNLESS LAST ODDS > METADATA
            //!METADATA, IS_PAST( YES = LAST ODDS, NO = NOW() )

            $extra_where = " AND fo1.date <= (IF ((SELECT 1 FROM matchups_metadata mm WHERE matchup_id = ? AND mm.mattribute = 'gametime' ), 
                                                    /*Metadata exists*/
                                                    IF ((SELECT FROM_UNIXTIME(mm.mvalue) FROM matchups_metadata mm WHERE mm.matchup_id = ? AND mm.mattribute = 'gametime' ) > NOW(), 
                                                        /*Metadata > NOW()*/
                                                        NOW(), 
                                                        /*Metadata < NOW()*/
                                                        (SELECT FROM_UNIXTIME(mm.mvalue) FROM matchups_metadata mm WHERE mm.matchup_id = ? AND mm.mattribute = 'gametime')),
                                                    /*Metadata does not exist*/
                                                    IF ((SELECT 1 FROM fights f INNER JOIN events e ON f.event_id = e.id WHERE f.id = ? AND LEFT(e.date, 10) < LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)), 
                                                        /*Event is in past*/
                                                        (SELECT MAX(fo.date) FROM fightodds fo WHERE fo.fight_id = ?), 
                                                        /*Event is upcoming*/
                                                        NOW())
                                                ))";
            $params[] = $matchup_id;
            $params[] = $matchup_id;
            $params[] = $matchup_id;
            $params[] = $matchup_id;
            $params[] = $matchup_id;
            $params[] = $matchup_id;
            $params[] = $matchup_id;
            $params[] = $matchup_id;
            $params[] = $matchup_id;
            $params[] = $matchup_id;
            $sDateCompare = '<';

            if ($from == 1) { //24 hour
                $extra_where .= " - INTERVAL 1 DAY ";
            } elseif ($from == 2) { //1 Hour
                $extra_where .= " - INTERVAL 1 HOUR ";
            }

            $query = 'SELECT 
                opening.avg_f1odds as opf1,
                opening.avg_f2odds as opf2,
                latest.avg_f1odds as laf1,
                latest.avg_f2odds as laf2,
                (opening.avg_f1odds - latest.avg_f1odds)/(opening.avg_f1odds - 1) as f1swing,           
                (opening.avg_f2odds - latest.avg_f2odds)/(opening.avg_f2odds - 1) as f2swing
            FROM
                ((SELECT AVG(f1.dec_f1odds) AS avg_f1odds, AVG(f1.dec_f2odds) AS avg_f2odds 
                        FROM (SELECT MoneylineToDecimal(m1.fighter1_odds) AS dec_f1odds, MoneylineToDecimal(m1.fighter2_odds) AS dec_f2odds, m1.*
                            FROM (SELECT fo1.* FROM fightodds fo1 WHERE fight_id = ? ' . $extra_where . ') m1 LEFT JOIN (SELECT fo1.* FROM fightodds fo1 WHERE fight_id = ? ' . $extra_where . ') m2
                                ON (m1.fight_id = m2.fight_id AND m1.bookie_id = m2.bookie_id AND m1.date < m2.date)
                                WHERE m2.date IS NULL AND m1.fight_id = ?) f1) opening
                JOIN 
                    (SELECT AVG(f1.dec_f1odds) AS avg_f1odds, AVG(f1.dec_f2odds) AS avg_f2odds 
                        FROM (SELECT MoneylineToDecimal(m1.fighter1_odds) AS dec_f1odds, MoneylineToDecimal(m1.fighter2_odds) AS dec_f2odds, m1.*
                            FROM fightodds m1 LEFT JOIN fightodds m2
                                ON (m1.fight_id = m2.fight_id AND m1.bookie_id = m2.bookie_id AND m1.date < m2.date)
                                WHERE m2.date IS NULL AND m1.fight_id = ?) f1) latest)';
                                
            $params[] = $matchup_id;
            $params[] = $matchup_id;
            $params[] = $matchup_id;
            $params[] = $matchup_id;
        }

        $row = PDOTools::findOne($query, $params);
        /* var_dump($result);
         exit;

         $result = DBTools::doParamQuery($query, $params);

         if ($row = mysqli_fetch_array($result))
         {*/
        if ($row) {
            return array('f1' => array(
                            'opening' => $row->opf1,
                            'latest' => $row->laf1,
                            'swing' => $row->f1swing),
                    'f2' => array(
                            'opening' => $row->opf2,
                            'latest' => $row->laf2,
                            'swing' => $row->f2swing
                        ));
        }
        return false;
    }
}
