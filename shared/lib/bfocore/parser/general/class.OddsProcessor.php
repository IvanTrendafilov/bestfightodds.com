<?php

require_once('lib/bfocore/parser/general/inc.ParserMain.php');

/**
 * OddsProcessor - Goes through parsed matchups and props, matches and stores them in the database. Also keeps track of changes to matchups and more..
 */
class OddsProcessor
{
    private $logger = null;
    private $bookie_id = null;

    public function __construct($logger, $bookie_id)
    {
        $this->logger = $logger;
        $this->bookie_id = $bookie_id;
    }

    /**
     * Processes parsed sport from a bookie parser
     * 
     * Steps:
     * 1. Removes any duplicate moneylines
     * 2. Removes any duplicate props (including event props)
     * 3. Looks up and matches all matchups
     * 4. Looks up and matches all prop bets
     * 4.5 PENDING: Remove prop dupes after matching
     * 5. Updates odds in database based on matches <--- NOT DONE
     * 6. Updates prop odds and event prop odds in database based on matches
     * 7. (IF full run) Flags matchups for deletion that was not found in parsed feed
     * 8. (IF full run) Flags prop odds for deletion that was not found in parsed feed
     * 9. (IF full run) Flags event prop odds for deletion that was not found in parsed feed
     */
    public function processParsedSport($parsed_sport, $full_run)
    {

        $parsed_sport = $this->removeMoneylineDupes($parsed_sport);
        $parsed_sport = $this->removePropDupes($parsed_sport);

        $matched_matchups = $this->matchMatchups($parsed_sport->getParsedMatchups());

        $pp = new PropParserV2($this->logger, $this->bookie_id);
        $matched_props = $pp->matchProps($parsed_sport->getFetchedProps());

        //Pending: Remove prop dupes AFTER MATCH
        //Pending: update Matchup Odds

        $pp->updateMatchedProps($matched_props);

        if ($full_run) //If this is a full run we will flag any matchups odds not matched for deletion
        {
            $this->flagMatchupOddsForDeletion($matched_matchups);
            $this->flagPropOddsForDeletion($matched_props);
            $this->flagEventPropOddsForDeletion($matched_props);
        }
    }

    /**
     * Runs through a set of parsed matchups and finds a matching matchup in the database for them
     */
    private function matchMatchups($parsed_matchups)
    {
        $matched_items = [];
        foreach ($parsed_matchups as $parsed_matchup)
        {
            $match = false;
            $matching_matchup = EventHandler::getMatchingFightV2(['team1_name' => $parsed_matchup->getTeamName(1),
                                            'team2_name' => $parsed_matchup->getTeamName(2),
                                            'future_only' => true]);

            if (!$matching_matchup)
            {
                $this->logger->warning('No matchup found for ' . $parsed_matchup->getTeamName(1) . ' vs ' . $parsed_matchup->getTeamName(2));
            }
            else
            {
                $this->logger->info('Found match for ' . $parsed_matchup->getTeamName(1) . ' vs ' . $parsed_matchup->getTeamName(2));
                $match = true;
            }
            $matched_items[] = ['parsed_matchup' => $parsed_matchup, 'matched_matchup' => $matching_matchup, 'match_status' => ['status' => $match]];
        }
        return $matched_items;
    }

    /**
     * Fetches all upcoming matchups where the bookie has previously had odds and checks if the odds are now removed. If so the odds will be flagged for deletion
     */
    private function flagMatchupOddsForDeletion($matched_matchups)
    {
        $upcoming_matchups = EventHandler::getAllUpcomingMatchups();
        foreach ($upcoming_matchups as $upcoming_matchup)
        {
            $odds = EventHandler::getLatestOddsForFightAndBookie($upcoming_matchup->getID(), $this->bookie_id);
            if ($odds != null)
            {
                $found = false;
                foreach ($matched_matchups as $matched_matchup)
                {
                    if ($matched_matchup['match_status']['status'] == true)
                    {
                        if ($matched_matchup['matched_matchup']->getID() == $upcoming_matchup->getID())
                        {
                            $found = true;
                        }
                    }   
                }
                if (!$found)
                {
                    $this->logger->info('Odds for ' . $upcoming_matchup->getTeamAsString(1) . ' vs ' . $upcoming_matchup->getTeamAsString(2) . ' will be flagged for deletion');
                    OddsHandler::flagMatchupOddsForDeletion($this->bookie_id, $upcoming_matchup->getID());
                }
                else
                {
                    $this->logger->debug('Odds for ' . $upcoming_matchup->getTeamAsString(1) . ' vs ' . $upcoming_matchup->getTeamAsString(2) . ' still relevant. No flagging');
                }
            }
        }
    }

    /**
     * Fetches all upcoming matchups where the bookie has previously had prop odds and checks if the prop odds are now removed. If so the prop odds will be flagged for deletion
     */
    private function flagPropOddsForDeletion($matched_props)
    {
        $upcoming_matchups = EventHandler::getAllUpcomingMatchups();
        foreach ($upcoming_matchups as $upcoming_matchup)
        {
            $stored_props = OddsHandler::getAllLatestPropOddsForMatchupAndBookie($upcoming_matchup->getID(), $this->bookie_id);
            foreach ($stored_props as $stored_prop)
            {
                $found = false;
                foreach ($matched_props as $matched_prop)
                {
                    if ($matched_prop['match_result']['status'] == true && $matched_prop['match_result']['matched_type'] == 'matchup')
                    {
                        if ($matched_prop['match_result']['matchup']['matchup_id'] == $stored_prop->getMatchupID()
                            && $matched_prop['match_result']['template']->getPropTypeID() == $stored_prop->getPropTypeID()
                            && $matched_prop['match_result']['matchup']['team'] == $stored_prop->getTeamNumber()
                            && $this->bookie_id == $stored_prop->getBookieID())
                        {
                            $found = true;
                        }
                    }
                }

                if (!$found)
                {
                    $this->logger->info('Prop odds with type ' . $stored_prop->getPropTypeID() . ' for team num ' . $stored_prop->getTeamNumber() . ' in ' . $upcoming_matchup->getTeamAsString(1) . ' vs ' . $upcoming_matchup->getTeamAsString(2) . ' will be flagged for deletion');
                    OddsHandler::flagPropOddsForDeletion($this->bookie_id, $stored_prop->getMatchupID(), $stored_prop->getPropTypeID(), $stored_prop->getTeamNumber());
                }
                else
                {
                    $this->logger->debug('Prop odds with type ' . $stored_prop->getPropTypeID() . ' for team num ' . $stored_prop->getTeamNumber() . ' in ' . $upcoming_matchup->getTeamAsString(1) . ' vs ' . $upcoming_matchup->getTeamAsString(2) . ' still relevant');
                }
            }
        }
    }
    
    /**
     * Fetches all events where the bookie has previously had event prop odds and checks if the event prop odds are now removed. If so the event prop odds will be flagged for deletion
     */
    private function flagEventPropOddsForDeletion($matched_props)
    {
        $upcoming_events = EventHandler::getAllUpcomingEvents();
        foreach ($upcoming_events as $upcoming_event)
        {
            //Todo: Possible improvement here is that we retrieve odds for all bookies. Could be limited to single bookie
            $stored_props = OddsHandler::getCompletePropsForEvent($upcoming_event->getID());
            if ($stored_props != null)
            {
                foreach ($stored_props as $stored_prop)
                {
                    $found = false;
                    foreach ($matched_props as $matched_prop)
                    {
                        if ($matched_prop['match_result']['status'] == true && $matched_prop['match_result']['matched_type'] == 'event')
                        {
                            if ($matched_prop['match_result']['event']['event_id'] == $stored_prop->getEventID()
                                && $matched_prop['match_result']['template']->getPropTypeID() == $stored_prop->getPropTypeID()
                                && $this->bookie_id == $stored_prop->getBookieID())
                            {
                                $found = true;
                            }
                        }
                    }

                    if (!$found)
                    {
                        $this->logger->info('Prop odds with type ' . $stored_prop->getPropTypeID() . ' for event ' . $upcoming_event->getName() . ' will be flagged for deletion');
                        OddsHandler::flagEventPropOddsForDeletion($this->bookie_id, $stored_prop->getEventID(), $stored_prop->getPropTypeID(), $stored_prop->getTeamNumber());
                    }
                    else
                    {
                        $this->logger->debug('Prop odds with type ' . $stored_prop->getPropTypeID() . ' for event ' . $upcoming_event->getName() . ' still relevant');
                    }
                }
            }
        }
    }

    /**
     * Loops through all parsed matchups and removes any dupes
     */
    private function removeMoneylineDupes($parsed_sport)
    {
        $matchups = $parsed_sport->getParsedMatchups();
        for ($y = 0; $y < sizeof($matchups); $y++)
        {
            for ($x = 0; $x < sizeof($matchups); $x++)
            {
                if ($x != $y
                        && $matchups[$y]->getTeamName(1) == $matchups[$x]->getTeamName(1)
                        && $matchups[$y]->getTeamName(2) == $matchups[$x]->getTeamName(2)
                        && !($matchups[$y]->getTeamOdds(1) == $matchups[$x]->getTeamOdds(1)
                        && $matchups[$y]->getTeamOdds(2) == $matchups[$x]->getTeamOdds(2)))
                {
                    //Found a match
                    $arbitrage_subject = ParseTools::getArbitrage($matchups[$y]->getTeamOdds(1), $matchups[$y]->getTeamOdds(2));
                    $arbitrage_challenger = ParseTools::getArbitrage($matchups[$x]->getTeamOdds(1), $matchups[$x]->getTeamOdds(2));

                    $this->logger->info('Removing dupe: ' . $matchups[$y]->getTeamName(1) . ' vs ' . $matchups[$y]->getTeamName(2));

                    if ($arbitrage_subject > $arbitrage_challenger) //Challenger won
                    {
                        unset($matchups[$y]);
                    }
                    else if ($arbitrage_subject < $arbitrage_challenger) //Subject won
                    {
                        unset($matchups[$x]);
                    }
                    else //Draw, remove one
                    {
                        unset($matchups[$x]);
                    }
                    $matchups = array_values($matchups);

                    $y = 0;
                    break 1;
                }
            }
        }

        $parsed_sport->setMatchupList($matchups);
        return $parsed_sport;
    }

    /**
     * Loops through all parsed props and removes any dupes (matching on name of the prop, not prop_type)
     */
    private function removePropDupes($parsed_sport)
    {
        $props = $parsed_sport->getFetchedProps();
        for ($y = 0; $y < sizeof($props); $y++)
        {
            for ($x = 0; $x < sizeof($props); $x++)
            {
                if ($x != $y
                        && $props[$y]->getCorrelationID() == $props[$x]->getCorrelationID()
                        && $props[$y]->getTeamName(1) == $props[$x]->getTeamName(1)
                        && $props[$y]->getTeamName(2) == $props[$x]->getTeamName(2)
                        && !($props[$y]->getTeamOdds(1) == $props[$x]->getTeamOdds(1)
                        && $props[$y]->getTeamOdds(2) == $props[$x]->getTeamOdds(2)))
                {
                    //Found a match
                    $arbitrage_subject = ParseTools::getArbitrage($props[$y]->getTeamOdds(1), $props[$y]->getTeamOdds(2));
                    $arbitrage_challenger = ParseTools::getArbitrage($props[$x]->getTeamOdds(1), $props[$x]->getTeamOdds(2));

                    $this->logger->info('Removing dupe: ' . $props[$y]->getTeamName(1) . ' vs ' . $props[$y]->getTeamName(2));

                    if ($arbitrage_subject > $arbitrage_challenger) //Challenger won
                    {
                        unset($props[$y]);
                    }
                    else if ($arbitrage_subject < $arbitrage_challenger) //Subject won
                    {
                        unset($props[$x]);
                    }
                    else //Draw, remove one
                    {
                        unset($props[$x]);
                    }
                    $props = array_values($props);

                    $y = 0;
                    break 1;
                }
            }
        }

        $parsed_sport->setPropList($props);
        return $parsed_sport;
    }


}
