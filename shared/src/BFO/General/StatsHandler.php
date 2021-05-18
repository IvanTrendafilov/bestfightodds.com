<?php

namespace BFO\General;

use BFO\DB\StatsDB;
use BFO\General\EventHandler;
use BFO\Utils\OddsTools;
use BFO\General\PropTypeHandler;
use BFO\Utils\LinkTools;

class StatsHandler
{
    public static function getAllDiffsForEvent($event_id, $from = 0) //0 Opening, 1 = 1 day ago, 2 = 1 hour ago
    {
        $matchups = EventHandler::getAllFightsForEvent($event_id, true);
        $swings_col = [];
        foreach ($matchups as $matchup) {
            $aStats = StatsHandler::getDiffForMatchup($matchup->getID(), $from);

            $swings_col[] = array($matchup, 1, $aStats['f1']);
            $swings_col[] = array($matchup, 2, $aStats['f2']);
        }

        if (!function_exists('BFO\General\cmpdiff')) {
            function cmpdiff($a, $b)
            {
                return $b[2]['swing'] <=> $a[2]['swing'];
            }
        }
        usort($swings_col, "BFO\General\cmpdiff");

        return $swings_col;
    }

    public static function getDiffForMatchup($matchup_id, $from = 0)
    {
        if (!is_numeric($matchup_id)) {
            return null;
        }
        return StatsDB::getDiffForMatchup($matchup_id, $from);
    }


    public static function getExpectedOutcomesForEvent($a_iEventID)
    {
        $matchups = EventHandler::getAllFightsForEvent($a_iEventID, true);
        $outcomes = [];
        foreach ($matchups as $matchup) {
            $matchup_outcomes = self::getExpectedOutcomesForMatchup($matchup);
            if ($matchup_outcomes != null) {
                $outcomes[] = array($matchup, self::getExpectedOutcomesForMatchup($matchup));
            }
        }
        return $outcomes;
    }

    public static function getExpectedOutcomesForMatchup($matchup)
    {
        //Hardcoded proptype IDs here.. might wanna fix this
        $odds_team1_insidedistance = OddsHandler::getCurrentPropIndex($matchup->getID(), 1, 10, ($matchup->hasOrderChanged() ? 2 : 1)); //Proptype 10: wins inside distance
        $odds_team1_decision = OddsHandler::getCurrentPropIndex($matchup->getID(), 1, 11, ($matchup->hasOrderChanged() ? 2 : 1)); //Proptype 11: wins by decision
        $odds_team2_insidedistance = OddsHandler::getCurrentPropIndex($matchup->getID(), 1, 10, ($matchup->hasOrderChanged() ? 1 : 2)); //Proptype 10: wins inside distance
        $odds_team2_decision = OddsHandler::getCurrentPropIndex($matchup->getID(), 1, 11, ($matchup->hasOrderChanged() ? 1 : 2)); //Proptype 11: wins by decision
        $odds_draw = OddsHandler::getCurrentPropIndex($matchup->getID(), 1, 6, 0); //Proptype 6: fight is a draw

        //Odds for all prop types in category is required to be able to draw some conclusion
        if ($odds_team1_insidedistance == null || $odds_team1_decision == null || $odds_team2_insidedistance == null || $odds_team2_decision == null || $odds_draw == null) {
            return ['team1_itd' => 0,
                'team1_dec' => 0,
                'team2_itd' => 0,
                'team2_dec' => 0,
                'draw' => 0];
        }

        $sum = OddsTools::convertMoneylineToDecimal($odds_team1_insidedistance->getPropOdds(1)) - 1
            + OddsTools::convertMoneylineToDecimal($odds_team1_decision->getPropOdds(1)) - 1
            + OddsTools::convertMoneylineToDecimal($odds_team2_insidedistance->getPropOdds(1)) - 1
            + OddsTools::convertMoneylineToDecimal($odds_team2_decision->getPropOdds(1)) - 1
            + OddsTools::convertMoneylineToDecimal($odds_draw->getPropOdds(1)) - 1;

        $ret = ['team1_itd' => round($sum / (OddsTools::convertMoneylineToDecimal($odds_team1_insidedistance->getPropOdds(1)) - 1)),
                'team1_dec' => round($sum / (OddsTools::convertMoneylineToDecimal($odds_team1_decision->getPropOdds(1)) - 1)),
                'team2_itd' => round($sum / (OddsTools::convertMoneylineToDecimal($odds_team2_insidedistance->getPropOdds(1)) - 1)),
                'team2_dec' => round($sum / (OddsTools::convertMoneylineToDecimal($odds_team2_decision->getPropOdds(1)) - 1)),
                'draw' => round($sum / (OddsTools::convertMoneylineToDecimal($odds_draw->getPropOdds(1)) - 1))];

        return $ret;
    }

    /* Generalized function based on getExpectedOutcomeForMatchup above

    - This works by combining the avg of each prop in category and checking how big part of the sum that single prop makes up
    - Will not make sense for certain categories and may need to be restricted to a specific team in order to give a usable split

    Open point, currently assumes posprop = looking only at the positive part of the prop. Should this be parameterized or should we combine the two somehow?
    */
    public static function getExpectedOutcome($matchup_id, $prop_category_id, $team = null)
    {
        $matchup = EventHandler::getFightByID($matchup_id);
        $sum = 0;
        $posprop = 1; //See restriction on using only posprop above
        $prop_odds_store = []; //Used to temporary store prop odds for later reference when summarizing

        $ret = [];

        //Get all props related to the prop_category
        $proptypes = PropTypeHandler::getPropTypes($prop_category_id);
        //Loop through and get index
        foreach ($proptypes as $proptype) {
            $i = 0;
            $i_limit = 1;
            //If prop is team based we need to get both sides so we will run it twice
            if ($proptype->isTeamSpecific() == true) {
                $i_limit = 2;
            }
            $prop_odds_store[$proptype->getID()] = [];
            for ($x = 1; $x <= $i_limit; $x++) {
                //Flip if internal order of matchup has changed or if we are in the second iteration
                $matchup_side = ($matchup->hasOrderChanged() ? 2 : 1);
                if ($x == 2) {
                    $matchup_side = $matchup_side % 2 + 1;
                }

                $prop_avg_odds = OddsHandler::getCurrentPropIndex($matchup->getID(), $posprop, $proptype->getID(), $matchup_side);
                //Odds for all prop types in category is required to be able to draw some conclusion. Return if one is missing
                
                if ($prop_avg_odds == null) {
                    return null;
                }
                //Summarize to later determine fraction for each
                $sum += OddsTools::convertMoneylineToDecimal($prop_avg_odds->getPropOdds($posprop)) - 1;

                //Store for later use
                $prop_odds_store[$proptype->getID()][$x] = $prop_avg_odds;
            }
        }

        foreach ($proptypes as $proptype) {
            $i = 0;
            $i_limit = 1;
            //If prop is team based we need to get both sides so we will run it twice
            if ($proptype->isTeamSpecific() == true) {
                $i_limit = 2;
            }

            for ($x = 1; $x <= $i_limit; $x++) {
                //Cannonnicalize the name of the prop
                //If team specific, replace <T> with <T1> and <T2>
                $prop_desc = str_replace('<T>', '<T' . $x . '>', $proptype->getPropDesc($posprop));
                $proptype_technicalname = LinkTools::slugString($prop_desc);
                //Calculate part for each
                $ret[$proptype_technicalname]['score'] = round($sum / (OddsTools::convertMoneylineToDecimal($prop_odds_store[$proptype->getID()][$x]->getPropOdds($posprop)) - 1));
                $ret[$proptype_technicalname]['odds_ml'] = $prop_odds_store[$proptype->getID()][$x]->getPropOdds($posprop);
            }
        }

        return $ret;
    }
}
