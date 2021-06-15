<?php

namespace BFO\General;

use BFO\General\OddsHandler;
use BFO\DataTypes\FightOdds;
use BFO\DataTypes\PropBet;

/**
 * Logic to handle retrieval of data for generation of graphs. Include methods for fetching aggregated data
 */
class GraphHandler
{
    public static function getMatchupData(int $matchup_id, int $bookie_id): ?array
    {
        return OddsHandler::getAllOdds($matchup_id, $bookie_id);
    }

    public static function getPropData(int $matchup_id, int $bookie_id, int $proptype_id, int $team_num): ?array
    {
        return OddsHandler::getAllPropOddsForMatchupPropType($matchup_id, $bookie_id, $proptype_id, $team_num);
    }

    public static function getEventPropData(int $event_id, int $bookie_id, int $proptype_id): ?array
    {
        return OddsHandler::getAllPropOddsForEventPropType($event_id, $bookie_id, $proptype_id);
    }

    public static function getMatchupIndexData(int $matchup_id, int $team_num): ?array
    {
        if ($team_num != 1 && $team_num != 2) {
            return null;
        }

        $bookies = BookieHandler::getAllBookies();

        $odds_col = [];
        $dates = [];

        $bookie_count = 0;

        foreach ($bookies as $bookie) {
            $odds_col[$bookie_count] = [];

            $stored_odds_col = OddsHandler::getAllOdds($matchup_id, $bookie->getID());
            if ($stored_odds_col) {
                foreach ($stored_odds_col as $stored_odds) {
                    $odds_col[$bookie_count][] = $stored_odds;
                    if (!in_array($stored_odds->getDate(), $dates)) {
                        $dates[] = $stored_odds->getDate();
                    }
                }
            }

            $bookie_count++;
        }

        sort($dates);
        $return_odds = [];

        foreach ($dates as $date_string) {
            $odds_mean = 0;
            $total_bookies_with_odds = 0;

            for ($i = 0; $i < $bookie_count; $i++) {
                $current_closest_odds = null;

                foreach ($odds_col[$i] as $odds_obj) {
                    if ($odds_obj->getDate() <= $date_string) {
                        if (!$current_closest_odds) {
                            $current_closest_odds = $odds_obj;
                        } else {
                            if ($odds_obj->getDate() > $current_closest_odds->getDate()) {
                                $current_closest_odds = $odds_obj;
                            }
                        }
                    }
                }

                if ($current_closest_odds) {
                    if ($odds_mean == 0) {
                        $odds_mean = $current_closest_odds->getFighterOddsAsDecimal($team_num, true);
                        $total_bookies_with_odds = 1;
                    } else {
                        $odds_mean = $odds_mean + $current_closest_odds->getFighterOddsAsDecimal($team_num, true);
                        $total_bookies_with_odds++;
                    }
                }
            }

            $return_odds[] = new FightOdds(
                (int) $matchup_id,
                -1,
                ($team_num == 1 ? FightOdds::convertOddsEUToUS($odds_mean / $total_bookies_with_odds) : 0),
                ($team_num == 2 ? FightOdds::convertOddsEUToUS($odds_mean / $total_bookies_with_odds) : 0),
                $date_string
            );
        }

        return $return_odds;
    }

    public static function getPropIndexData(int $matchup_id, int $prop_side, int $proptype_id, int $team_num): array
    {
        return self::getPropIndexDataGeneric($matchup_id, -1, $prop_side, $proptype_id, $team_num);
    }

    public static function getEventPropIndexData(int $event_id, int $prop_side, int $proptype_id): array
    {
        return self::getPropIndexDataGeneric(-1, $event_id, $prop_side, $proptype_id, 0);
    }

    public static function getPropIndexDataGeneric(int $matchup_id, int $event_id, int $prop_side, int $proptype_id, int $team_num = 0): array
    {
        if (($matchup_id != -1 && $event_id != -1)
            || ($matchup_id == -1 && $event_id == -1)
        ) { //Both matchup and event id specified or non at all supplied. Abort
            return [];
        }

        $bookies = BookieHandler::getAllBookies();

        $odds_col = [];
        $dates = [];

        $bookie_count = 0;
        $skip_bookie = false; //Keeps track if bookie does not give odds on the prop and if it is stored as -99999 in the database

        foreach ($bookies as $bookie) {
            $odds_col[$bookie_count] = [];

            $propodds_col = null;
            if ($matchup_id != -1) { //Matchup is specified
                $propodds_col = OddsHandler::getAllPropOddsForMatchupPropType($matchup_id, $bookie->getID(), $proptype_id, $team_num);
            } else { //Event is specified
                $propodds_col = OddsHandler::getAllPropOddsForEventPropType($event_id, $bookie->getID(), $proptype_id);
            }

            if ($propodds_col) {
                foreach ($propodds_col as $prop_odds) {
                    //Check if prop bet should be skipped, i.e. stored as -99999 in database
                    if (($prop_side == 1 ? $prop_odds->getPropOdds() : $prop_odds->getNegPropOdds()) == -99999) {
                        $skip_bookie = true;
                    } else {
                        $odds_col[$bookie_count][] = $prop_odds;
                        if (!in_array($prop_odds->getDate(), $dates)) {
                            $dates[] = $prop_odds->getDate();
                        }
                    }
                }
            }

            if (!$skip_bookie) {
                $bookie_count++;
            }
            $skip_bookie = false;
        }

        sort($dates);

        $return_odds = [];

        foreach ($dates as $date_string) {
            $odds_mean = 0;
            $total_bookies_with_odds = 0;

            for ($i = 0; $i < $bookie_count; $i++) {
                $current_closest_odds = null;

                foreach ($odds_col[$i] as $odds_obj) {
                    if ($odds_obj->getDate() <= $date_string) {
                        if (!$current_closest_odds) {
                            $current_closest_odds = $odds_obj;
                        } else {
                            if ($odds_obj->getDate() > $current_closest_odds->getDate()) {
                                $current_closest_odds = $odds_obj;
                            }
                        }
                    }
                }

                if ($current_closest_odds) {
                    if ($odds_mean == 0) {
                        $odds_mean = ($prop_side == 1 ? PropBet::moneylineToDecimal($current_closest_odds->getPropOdds(), true) : PropBet::moneylineToDecimal($current_closest_odds->getNegPropOdds(), true));
                        $total_bookies_with_odds = 1;
                    } else {
                        $odds_mean = $odds_mean + ($prop_side == 1 ? PropBet::moneylineToDecimal($current_closest_odds->getPropOdds(), true) : PropBet::moneylineToDecimal($current_closest_odds->getNegPropOdds(), true));
                        $total_bookies_with_odds++;
                    }
                }
            }

            $return_odds[] = new PropBet($matchup_id, -1, '', ($prop_side == 1 ? PropBet::decimalToMoneyline($odds_mean / $total_bookies_with_odds) : 0), '', ($prop_side == 2 ? PropBet::decimalToMoneyline($odds_mean / $total_bookies_with_odds) : 0), $proptype_id, $date_string, $team_num);
        }

        return $return_odds;
    }

    public static function getMedianSparkLine(int $matchup_id, int $team_num): ?string
    {
        $sparkline_steps = 10;

        $odds = OddsHandler::getAllOdds($matchup_id);
        if ($odds == null || sizeof($odds) < 1) {
            return null;
        }

        //Determine high/low/step
        $low_date = (new \DateTime($odds[0]->getDate()))->getTimestamp() * 1000;
        $high_date = (new \DateTime($odds[sizeof($odds) - 1]->getDate()))->getTimestamp() * 1000;
        $step = ($high_date - $low_date) / ($sparkline_steps - 1);

        $latest_odds_per_bookie = [];
        $step_counter = 0;
        $return_str = '';

        foreach ($odds as $odds_obj) {
            $odds_date = (new \DateTime($odds_obj->getDate()))->getTimestamp() * 1000;
            $latest_odds_per_bookie[$odds_obj->getBookieID()] = $odds_obj;
            // Once we reach a line that passes the step date, flush the stored ones and create an index for that
            if ($odds_date >= $low_date + ($step * $step_counter)) {
                $total = 0;
                foreach ($latest_odds_per_bookie as $bookieLine) {
                    $total += $bookieLine->getFighterOddsAsDecimal($team_num, true);
                }
                $mean = $total / sizeof($latest_odds_per_bookie);
                //echo 'Step ' . $step_counter . ' mean is: ' . $mean . ' when steps was ' . ($low_date + ($step * $step_counter)) . '<br>';
                $return_str .= $mean . ', ';
                $step_counter++;
            }
        }
        return rtrim($return_str, ', ');
    }
}
