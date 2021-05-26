<?php

namespace BFO\General;

use BFO\General\EventHandler;
use BFO\General\OddsHandler;

use BFO\DataTypes\FightOdds;
use BFO\DataTypes\PropBet;

class GraphHandler
{
    public static function getMatchupData($matchup_id, $bookie_id)
    {
        return EventHandler::getAllOdds($matchup_id, $bookie_id);
    }

    public static function getPropData($matchup_id, $bookie_id, $proptype_id, $team_num)
    {
        return OddsHandler::getAllPropOddsForMatchupPropType($matchup_id, $bookie_id, $proptype_id, $team_num);
    }

    public static function getEventPropData($event_id, $bookie_id, $proptype_id)
    {
        return OddsHandler::getAllPropOddsForEventPropType($event_id, $bookie_id, $proptype_id);
    }

    public static function getMatchupIndexData($matchup_id, $team_num)
    {
        if ($team_num != 1 && $team_num != 2) {
            return null;
        }

        $bookies = BookieHandler::getAllBookies();

        $aOdds = [];
        $aDates = [];

        $bookie_count = 0;

        foreach ($bookies as $oBookie) {
            $aOdds[$bookie_count] = [];

            $aFightOdds = EventHandler::getAllOdds($matchup_id, $oBookie->getID());
            if ($aFightOdds != null) {
                foreach ($aFightOdds as $oFightOdds) {
                    $aOdds[$bookie_count][] = $oFightOdds;
                    if (!in_array($oFightOdds->getDate(), $aDates)) {
                        $aDates[] = $oFightOdds->getDate();
                    }
                }
            }

            $bookie_count++;
        }

        sort($aDates);

        $aDateOdds = [];

        foreach ($aDates as $sDate) {
            $iCurrentOddsMean = 0;
            $iCurrentOwners = 0;

            for ($iX = 0; $iX < $bookie_count; $iX++) {
                $oCurrentClosestOdds = null;

                foreach ($aOdds[$iX] as $oOdds) {
                    if ($oOdds->getDate() <= $sDate) {
                        if ($oCurrentClosestOdds == null) {
                            $oCurrentClosestOdds = $oOdds;
                        } else {
                            if ($oOdds->getDate() > $oCurrentClosestOdds->getDate()) {
                                $oCurrentClosestOdds = $oOdds;
                            }
                        }
                    }
                }

                if ($oCurrentClosestOdds != null) {
                    if ($iCurrentOddsMean == 0) {
                        $iCurrentOddsMean = $oCurrentClosestOdds->getFighterOddsAsDecimal($team_num, true);
                        $iCurrentOwners = 1;
                    } else {
                        $iCurrentOddsMean = $iCurrentOddsMean + $oCurrentClosestOdds->getFighterOddsAsDecimal($team_num, true);
                        $iCurrentOwners++;
                    }
                }
            }

            $aDateOdds[] = new FightOdds(
                $matchup_id,
                -1,
                ($team_num == 1 ? FightOdds::convertOddsEUToUS($iCurrentOddsMean / $iCurrentOwners) : 0),
                ($team_num == 2 ? FightOdds::convertOddsEUToUS($iCurrentOddsMean / $iCurrentOwners) : 0),
                $sDate
            );
        }

        return $aDateOdds;
    }



    public static function getPropIndexData($matchup_id, $a_iPosProp, $proptype_id, $team_num)
    {
        $bookies = BookieHandler::getAllBookies();

        $aOdds = [];
        $aDates = [];

        $bookie_count = 0;
        $bSkipBookie = false; //Keeps track if bookie does not give odds on the prop and if it is stored as -99999 in the database

        foreach ($bookies as $oBookie) {
            $aOdds[$bookie_count] = [];

            $aPropOdds = OddsHandler::getAllPropOddsForMatchupPropType($matchup_id, $oBookie->getID(), $proptype_id, $team_num);

            if ($aPropOdds != null) {
                foreach ($aPropOdds as $oPropBet) {
                    //Check if prop bet should be skipped, i.e. stored as -99999 in database
                    if (($a_iPosProp == 1 ? $oPropBet->getPropOdds() : $oPropBet->getNegPropOdds()) == -99999) {
                        $bSkipBookie = true;
                    } else {
                        $aOdds[$bookie_count][] = $oPropBet;
                        if (!in_array($oPropBet->getDate(), $aDates)) {
                            $aDates[] = $oPropBet->getDate();
                        }
                    }
                }
            }

            if ($bSkipBookie == false) {
                $bookie_count++;
            }
            $bSkipBookie = false;
        }

        sort($aDates);

        $aDateOdds = [];

        foreach ($aDates as $sDate) {
            $iCurrentOddsMean = 0;
            $iCurrentOwners = 0;

            for ($iX = 0; $iX < $bookie_count; $iX++) {
                $oCurrentClosestOdds = null;

                foreach ($aOdds[$iX] as $oOdds) {
                    if ($oOdds->getDate() <= $sDate) {
                        if ($oCurrentClosestOdds == null) {
                            $oCurrentClosestOdds = $oOdds;
                        } else {
                            if ($oOdds->getDate() > $oCurrentClosestOdds->getDate()) {
                                $oCurrentClosestOdds = $oOdds;
                            }
                        }
                    }
                }

                if ($oCurrentClosestOdds != null) {
                    if ($iCurrentOddsMean == 0) {
                        $iCurrentOddsMean = ($a_iPosProp == 1 ? PropBet::moneylineToDecimal($oCurrentClosestOdds->getPropOdds(), true) : PropBet::moneylineToDecimal($oCurrentClosestOdds->getNegPropOdds(), true));
                        $iCurrentOwners = 1;
                    } else {
                        $iCurrentOddsMean = $iCurrentOddsMean + ($a_iPosProp == 1 ? PropBet::moneylineToDecimal($oCurrentClosestOdds->getPropOdds(), true) : PropBet::moneylineToDecimal($oCurrentClosestOdds->getNegPropOdds(), true));
                        $iCurrentOwners++;
                    }
                }
            }

            $aDateOdds[] = new PropBet($matchup_id, -1, '', ($a_iPosProp == 1 ? PropBet::decimalToMoneyline($iCurrentOddsMean / $iCurrentOwners) : 0), '', ($a_iPosProp == 2 ? PropBet::decimalToMoneyline($iCurrentOddsMean / $iCurrentOwners) : 0), $proptype_id, $sDate, $team_num);
        }

        return $aDateOdds;
    }


    /* TODO: Merge this into the one above at some point. Some redundency here */
    public static function getEventPropIndexData($a_iEventID, $a_iPosProp, $proptype_id)
    {
        $bookies = BookieHandler::getAllBookies();

        $aOdds = [];
        $aDates = [];

        $bookie_count = 0;
        $bSkipBookie = false; //Keeps track if bookie does not give odds on the prop and if it is stored as -99999 in the database

        foreach ($bookies as $oBookie) {
            $aOdds[$bookie_count] = [];

            $aPropOdds = OddsHandler::getAllPropOddsForEventPropType($a_iEventID, $oBookie->getID(), $proptype_id);

            if ($aPropOdds != null) {
                foreach ($aPropOdds as $oPropBet) {
                    //Check if prop bet should be skipped, i.e. stored as -99999 in database
                    if (($a_iPosProp == 1 ? $oPropBet->getPropOdds() : $oPropBet->getNegPropOdds()) == -99999) {
                        $bSkipBookie = true;
                    } else {
                        $aOdds[$bookie_count][] = $oPropBet;
                        if (!in_array($oPropBet->getDate(), $aDates)) {
                            $aDates[] = $oPropBet->getDate();
                        }
                    }
                }
            }

            if ($bSkipBookie == false) {
                $bookie_count++;
            }
            $bSkipBookie = false;
        }

        sort($aDates);

        $aDateOdds = [];

        foreach ($aDates as $sDate) {
            $iCurrentOddsMean = 0;
            $iCurrentOwners = 0;

            for ($iX = 0; $iX < $bookie_count; $iX++) {
                $oCurrentClosestOdds = null;

                foreach ($aOdds[$iX] as $oOdds) {
                    if ($oOdds->getDate() <= $sDate) {
                        if ($oCurrentClosestOdds == null) {
                            $oCurrentClosestOdds = $oOdds;
                        } else {
                            if ($oOdds->getDate() > $oCurrentClosestOdds->getDate()) {
                                $oCurrentClosestOdds = $oOdds;
                            }
                        }
                    }
                }

                if ($oCurrentClosestOdds != null) {
                    if ($iCurrentOddsMean == 0) {
                        $iCurrentOddsMean = ($a_iPosProp == 1 ? PropBet::moneylineToDecimal($oCurrentClosestOdds->getPropOdds(), true) : PropBet::moneylineToDecimal($oCurrentClosestOdds->getNegPropOdds(), true));
                        $iCurrentOwners = 1;
                    } else {
                        $iCurrentOddsMean = $iCurrentOddsMean + ($a_iPosProp == 1 ? PropBet::moneylineToDecimal($oCurrentClosestOdds->getPropOdds(), true) : PropBet::moneylineToDecimal($oCurrentClosestOdds->getNegPropOdds(), true));
                        $iCurrentOwners++;
                    }
                }
            }

            $aDateOdds[] = new PropBet($a_iEventID, -1, '', ($a_iPosProp == 1 ? PropBet::decimalToMoneyline($iCurrentOddsMean / $iCurrentOwners) : 0), '', ($a_iPosProp == 2 ? PropBet::decimalToMoneyline($iCurrentOddsMean / $iCurrentOwners) : 0), $proptype_id, $sDate);
        }

        return $aDateOdds;
    }

    public static function getMedianSparkLine($matchup_id, $team_num)
    {
        $sparkline_steps = 10;

        $odds = EventHandler::getAllOdds($matchup_id);
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
                foreach ($latest_odds_per_bookie as $oBookieLine) {
                    $total += $oBookieLine->getFighterOddsAsDecimal($team_num, true);
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
