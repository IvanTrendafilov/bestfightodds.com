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

        //Pre-populate correlation table with entries from database. These can be overwritten later in the matching matchups method
        $correlations = OddsHandler::getCorrelationsForBookie($this->bookie_id);
        foreach ($correlations as $correlation) {
            ParseTools::saveCorrelation($correlation['correlation'], $correlation['matchup_id']);
        }

        $parsed_sport = $this->removeMoneylineDupes($parsed_sport);
        $parsed_sport = $this->removePropDupes($parsed_sport);

        $matched_matchups = $this->matchMatchups($parsed_sport->getParsedMatchups());

        //Pending: For Create mode, create new matchups if no match was found
        if (PARSE_CREATEMATCHUPS == true) {
            $matched_matchups = $this->createUnmatchedMatchups($matched_matchups);
        }

        $pp = new PropParserV2($this->logger, $this->bookie_id);
        $matched_props = $pp->matchProps($parsed_sport->getFetchedProps());

        $matched_props = $this->removeMatchedPropDupes($matched_props);

        $matchup_unmatched_count = $this->logUnmatchedMatchups($matched_matchups);
        $prop_unmatched_count = $pp->logUnmatchedProps($matched_props);

        $this->updateMatchedMatchups($matched_matchups);
        $pp->updateMatchedProps($matched_props);

        if ($full_run) //If this is a full run we will flag any matchups odds not matched for deletion
        {
            $this->flagMatchupOddsForDeletion($matched_matchups);
            $this->flagPropOddsForDeletion($matched_props);
            $this->flagEventPropOddsForDeletion($matched_props);
        }

        $this->logger->info('Result - Matchups: ' . (count($matched_matchups) - $matchup_unmatched_count) . '/' . count($parsed_sport->getParsedMatchups()) . ' Props: ' . (count($matched_props) - $prop_unmatched_count) . '/' . count($parsed_sport->getFetchedProps()) . ' Full run: ' . ($full_run ? 'Yes' : 'No'));

        $parse_run_logger = new ParseRunLogger();
        $parse_run_logger->logRun(-1, [
            'bookie_id' => $this->bookie_id,
            'parsed_matchups' => count($parsed_sport->getParsedMatchups()),
            'parsed_props' => count($parsed_sport->getFetchedProps()),
            'matched_matchups' => (count($matched_matchups) - $matchup_unmatched_count),
            'matched_props' => (count($matched_props) - $prop_unmatched_count),
            'status' => 1
        ]);
    }

    /**
     * Runs through a set of parsed matchups and finds a matching matchup in the database for them
     */
    private function matchMatchups($parsed_matchups)
    {
        $matched_items = [];
        foreach ($parsed_matchups as $parsed_matchup) {
            $match = false;
            $matching_matchup = EventHandler::getMatchingFightV2([
                'team1_name' => $parsed_matchup->getTeamName(1),
                'team2_name' => $parsed_matchup->getTeamName(2),
                'future_only' => true
            ]);

            if (!$matching_matchup) {
                $this->logger->warning('No matchup found for ' . $parsed_matchup->getTeamName(1) . ' vs ' . $parsed_matchup->getTeamName(2));
            } else {
                $this->logger->info('Found match for ' . $parsed_matchup->getTeamName(1) . ' vs ' . $parsed_matchup->getTeamName(2));
                //If parsed matchup contains a correlation ID, we store this in the correlation table. Maybe move this out to other function?
                if ($parsed_matchup->getCorrelationID() != '') {
                    ParseTools::saveCorrelation($parsed_matchup->getCorrelationID(), $matching_matchup->getID());
                    $this->logger->debug("---------- storing correlation ID: " . $parsed_matchup->getCorrelationID() . ' to matchup ' . $matching_matchup->getID());
                }
                $match = true;
            }
            $matched_items[] = ['parsed_matchup' => $parsed_matchup, 'matched_matchup' => $matching_matchup, 'match_result' => ['status' => $match]];
        }
        return $matched_items;
    }

    /**
     * Goes through matched odds for matchups and updates them in the database if anything has changed (through updateOneMatchedMatchup() function)
     */
    private function updateMatchedMatchups($matched_matchups)
    {
        foreach ($matched_matchups as $matched_matchup) {
            if ($matched_matchup['match_result']['status'] == true) {
                $this->updateOneMatchedMatchup($matched_matchup);
            }
        }
    }

    /**
     * Takes one matched odds for matchups and updates them in the database if anything has changed
     * In addition to this, it will also store correlation ID for this matchup (if any other odds will require it) as well as store any meta data for the matchup
     */
    private function updateOneMatchedMatchup($matched_matchup)
    {
        $event = EventHandler::getEvent($matched_matchup['matched_matchup']->getEventID(), true);
        $temp_matchup = new Fight(0, $matched_matchup['parsed_matchup']->getTeamName(1), $matched_matchup['parsed_matchup']->getTeamName(2), -1);

        if ($event != null) {
            //This routine is used to switch the order of odds in a matchup if order has changed through the use of alt names or similar
            if (($matched_matchup['matched_matchup']->getComment() == 'switched' || $temp_matchup->hasOrderChanged()) && !$matched_matchup['parsed_matchup']->isSwitchedFromOutside()) {
                $matched_matchup['parsed_matchup']->switchOdds();
            }

            //Store any metadata for the matchup
            $metadata = $matched_matchup['parsed_matchup']->getAllMetaData();
            foreach ($metadata as $key => $val) {
                if ($this->bookie_id != 12  && $this->bookie_id != 5 && $this->bookie_id != 17 && $this->bookie_id != 4 && $this->bookie_id != 19 && $this->bookie_id != 18 && $this->bookie_id != 13) //TODO: Temporary disable BetOnline, Bovada, William Hill, Sportsbook, Bet365, Intertops, BetDSI from storing metadata
                {
                    EventHandler::setMetaDataForMatchup($matched_matchup['matched_matchup']->getID(), $key, $val, $this->bookie_id);
                }
            }

            if ($matched_matchup['parsed_matchup']->hasMoneyline()) {
                $odds = new FightOdds($matched_matchup['matched_matchup']->getID(), $this->bookie_id, $matched_matchup['parsed_matchup']->getTeamOdds(1), $matched_matchup['parsed_matchup']->getTeamOdds(2), ParseTools::standardizeDate(date('Y-m-d')));

                if (EventHandler::checkMatchingOdds($odds)) {
                    $this->logger->info("- " . $matched_matchup['matched_matchup']->getTeamAsString(1) . " vs " . $matched_matchup['matched_matchup']->getTeamAsString(2) . ": nothing has changed since last odds");
                } else {
                    $this->logger->info("- " . $matched_matchup['matched_matchup']->getTeamAsString(1) . " vs " . $matched_matchup['matched_matchup']->getTeamAsString(2) . ": adding new odds");
                    $result = EventHandler::addNewFightOdds($odds);
                    if (!$result) {
                        $this->logger->error("-- Error adding odds");
                    }
                }
                return true;
            }
        } else {
            //Trying to add odds for a matchup with no upcoming event
            $this->logger->error("--- match wrongfully matched to event OR odds are old");
            return false;
        }
    }

    /**
     * Fetches all upcoming matchups where the bookie has previously had odds and checks if the odds are now removed. If so the odds will be flagged for deletion
     */
    private function flagMatchupOddsForDeletion($matched_matchups)
    {
        $upcoming_matchups = EventHandler::getAllUpcomingMatchups();
        foreach ($upcoming_matchups as $upcoming_matchup) {
            $odds = EventHandler::getLatestOddsForFightAndBookie($upcoming_matchup->getID(), $this->bookie_id);
            if ($odds != null) {
                $this->logger->debug('Bookie has odds for ' . $upcoming_matchup->getTeamAsString(1) . ' vs ' . $upcoming_matchup->getTeamAsString(2) . '. Checking if this should be flagged for deletion');
                $found = false;
                foreach ($matched_matchups as $matched_matchup) {
                    if ($matched_matchup['match_result']['status'] == true) {
                        if ($matched_matchup['matched_matchup']->getID() == $upcoming_matchup->getID()) {
                            $found = true;
                        }
                    }
                }
                if (!$found) {
                    $this->logger->info('Odds for ' . $upcoming_matchup->getTeamAsString(1) . ' vs ' . $upcoming_matchup->getTeamAsString(2) . ' will be flagged for deletion');
                    OddsHandler::flagMatchupOddsForDeletion($this->bookie_id, $upcoming_matchup->getID());
                } else {
                    OddsHandler::removeFlagged($this->bookie_id, $upcoming_matchup->getID()); //Remove any previous flags
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
        foreach ($upcoming_matchups as $upcoming_matchup) {
            $stored_props = OddsHandler::getAllLatestPropOddsForMatchupAndBookie($upcoming_matchup->getID(), $this->bookie_id);
            foreach ($stored_props as $stored_prop) {
                $found = false;
                foreach ($matched_props as $matched_prop) {
                    if ($matched_prop['match_result']['status'] == true && $matched_prop['match_result']['matched_type'] == 'matchup') {
                        if (
                            $matched_prop['match_result']['matchup']['matchup_id'] == $stored_prop->getMatchupID()
                            && $matched_prop['match_result']['template']->getPropTypeID() == $stored_prop->getPropTypeID()
                            && $matched_prop['match_result']['matchup']['team'] == $stored_prop->getTeamNumber()
                            && $this->bookie_id == $stored_prop->getBookieID()
                        ) {
                            $found = true;
                        }
                    }
                }

                if (!$found) {
                    $this->logger->info('Prop odds with type ' . $stored_prop->getPropTypeID() . ' for team num ' . $stored_prop->getTeamNumber() . ' in ' . $upcoming_matchup->getTeamAsString(1) . ' vs ' . $upcoming_matchup->getTeamAsString(2) . ' will be flagged for deletion');
                    OddsHandler::flagPropOddsForDeletion($this->bookie_id, $stored_prop->getMatchupID(), $stored_prop->getPropTypeID(), $stored_prop->getTeamNumber());
                } else {
                    OddsHandler::removeFlagged($this->bookie_id, $stored_prop->getMatchupID(), null, $stored_prop->getPropTypeID(), $stored_prop->getTeamNumber()); //Remove any previous flags
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
        foreach ($upcoming_events as $upcoming_event) {
            //Todo: Possible improvement here is that we retrieve odds for all bookies. Could be limited to single bookie
            $stored_props = OddsHandler::getCompletePropsForEvent($upcoming_event->getID(), 0, $this->bookie_id);
            if ($stored_props != null) {
                foreach ($stored_props as $stored_prop) {
                    $found = false;
                    foreach ($matched_props as $matched_prop) {
                        if ($matched_prop['match_result']['status'] == true && $matched_prop['match_result']['matched_type'] == 'event') {
                            if (
                                $matched_prop['match_result']['event']['event_id'] == $stored_prop->getEventID()
                                && $matched_prop['match_result']['template']->getPropTypeID() == $stored_prop->getPropTypeID()
                                && $this->bookie_id == $stored_prop->getBookieID()
                            ) {
                                $found = true;
                            }
                        }
                    }

                    if (!$found) {
                        $this->logger->info('Prop odds with type ' . $stored_prop->getPropTypeID() . ' for event ' . $upcoming_event->getName() . ' will be flagged for deletion');
                        OddsHandler::flagEventPropOddsForDeletion($this->bookie_id, $stored_prop->getEventID(), $stored_prop->getPropTypeID(), $stored_prop->getTeamNumber());
                    } else {
                        OddsHandler::removeFlagged($this->bookie_id, null, $stored_prop->getEventID(), $stored_prop->getPropTypeID(), $stored_prop->getTeamNumber()); //Remove any previous flags
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
        for ($y = 0; $y < sizeof($matchups); $y++) {
            for ($x = 0; $x < sizeof($matchups); $x++) {
                if (
                    $x != $y
                    && $matchups[$y]->getTeamName(1) == $matchups[$x]->getTeamName(1)
                    && $matchups[$y]->getTeamName(2) == $matchups[$x]->getTeamName(2)
                    && !($matchups[$y]->getTeamOdds(1) == $matchups[$x]->getTeamOdds(1)
                        && $matchups[$y]->getTeamOdds(2) == $matchups[$x]->getTeamOdds(2))
                ) {
                    //Found a match
                    $arbitrage_subject = ParseTools::getArbitrage($matchups[$y]->getTeamOdds(1), $matchups[$y]->getTeamOdds(2));
                    $arbitrage_challenger = ParseTools::getArbitrage($matchups[$x]->getTeamOdds(1), $matchups[$x]->getTeamOdds(2));

                    $this->logger->info('Removing dupe: ' . $matchups[$y]->getTeamName(1) . ' vs ' . $matchups[$y]->getTeamName(2));

                    if ($arbitrage_subject > $arbitrage_challenger) //Challenger won
                    {
                        unset($matchups[$y]);
                    } else if ($arbitrage_subject < $arbitrage_challenger) //Subject won
                    {
                        unset($matchups[$x]);
                    } else //Draw, remove one
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
        for ($y = 0; $y < sizeof($props); $y++) {
            for ($x = 0; $x < sizeof($props); $x++) {
                if (
                    $x != $y
                    && $props[$y]->getCorrelationID() == $props[$x]->getCorrelationID()
                    && $props[$y]->getTeamName(1) == $props[$x]->getTeamName(1)
                    && $props[$y]->getTeamName(2) == $props[$x]->getTeamName(2)
                    && !($props[$y]->getTeamOdds(1) == $props[$x]->getTeamOdds(1)
                        && $props[$y]->getTeamOdds(2) == $props[$x]->getTeamOdds(2))
                ) {
                    //Found a match
                    $arbitrage_subject = ParseTools::getArbitrage($props[$y]->getTeamOdds(1), $props[$y]->getTeamOdds(2));
                    $arbitrage_challenger = ParseTools::getArbitrage($props[$x]->getTeamOdds(1), $props[$x]->getTeamOdds(2));

                    $this->logger->info('Removing dupe: ' . $props[$y]->getTeamName(1) . ' vs ' . $props[$y]->getTeamName(2));

                    if ($arbitrage_subject > $arbitrage_challenger) //Challenger won
                    {
                        unset($props[$y]);
                    } else if ($arbitrage_subject < $arbitrage_challenger) //Subject won
                    {
                        unset($props[$x]);
                    } else //Draw, remove one
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

    /**
     * Loops through all _matched_ props and removes any dupes based on prop_type and matchup
     */
    private function removeMatchedPropDupes($props)
    {
        for ($y = 0; $y < sizeof($props); $y++) {
            if ($props[$y]['match_result']['status'] == true) {
                $matches = [];
                for ($x = 0; $x < sizeof($props); $x++) {
                    if ($x != $y) //Ignore self
                    {
                        if (
                            $props[$x]['match_result']['status'] == true
                            && $props[$x]['match_result']['template']->getPropTypeID() == $props[$y]['match_result']['template']->getPropTypeID()
                            && $props[$x]['match_result']['matchup']['matchup_id'] == $props[$y]['match_result']['matchup']['matchup_id']
                            && $props[$x]['match_result']['matchup']['team'] == $props[$y]['match_result']['matchup']['team']
                        ) {
                            $this->logger->info('Matching dupe for proptype_id: ' . $props[$x]['match_result']['template']->getPropTypeID() . ', matchup_id: ' . $props[$x]['match_result']['matchup']['matchup_id'] .
                                ' ' . $props[$y]['prop']->getTeamOdds(1) . '/' . $props[$y]['prop']->getTeamOdds(2) . ' and ' . $props[$x]['prop']->getTeamOdds(1) . '/' . $props[$x]['prop']->getTeamOdds(2));

                            $arbitrage_subject = ParseTools::getArbitrage($props[$y]['prop']->getTeamOdds(1), $props[$y]['prop']->getTeamOdds(2));
                            $arbitrage_challenger = ParseTools::getArbitrage($props[$x]['prop']->getTeamOdds(1), $props[$x]['prop']->getTeamOdds(2));

                            if ($arbitrage_subject > $arbitrage_challenger) //Challenger won
                            {
                                $this->logger->info('Removing subject dupe: ' . $props[$y]['prop']->getTeamOdds(1) . '/' . $props[$y]['prop']->getTeamOdds(2) . ' for matchup_id: ' . $props[$x]['match_result']['matchup']['matchup_id'] . ' proptype_id: ' . $props[$x]['match_result']['template']->getPropTypeID() . ' team_num: ' . $props[$y]['match_result']['matchup']['team']);
                                unset($props[$y]);
                            } else if ($arbitrage_subject < $arbitrage_challenger) //Subject won
                            {
                                $this->logger->info('Removing challenger dupe: ' . $props[$x]['prop']->getTeamOdds(1) . '/' . $props[$x]['prop']->getTeamOdds(2) . ' for matchup_id: ' . $props[$x]['match_result']['matchup']['matchup_id'] . ' proptype_id: ' . $props[$x]['match_result']['template']->getPropTypeID() . ' team_num: ' . $props[$y]['match_result']['matchup']['team']);
                                unset($props[$x]);
                            } else //Draw, remove one
                            {
                                $this->logger->info('Removing identical dupe: ' . $props[$x]['prop']->getTeamOdds(1) . '/' . $props[$x]['prop']->getTeamOdds(2) . ' for matchup_id: ' . $props[$x]['match_result']['matchup']['matchup_id'] . ' proptype_id: ' . $props[$x]['match_result']['template']->getPropTypeID() . ' team_num: ' . $props[$y]['match_result']['matchup']['team']);
                                unset($props[$x]);
                            }
                            $props = array_values($props);

                            $y = -1;
                            break 1;
                        }
                    }
                }
            }
        }
        return $props;
    }

    private function logUnmatchedMatchups($matched_matchups)
    {
        $counter = 0;
        foreach ($matched_matchups as $matchup) {
            if ($matchup['match_result']['status'] == false) {
                $counter++;
                EventHandler::logUnmatched($matchup['parsed_matchup']->toString(), $this->bookie_id, 0, $matchup['parsed_matchup']->getAllMetaData());
            }
        }
        return $counter;
    }

    private function createUnmatchedMatchups($matched_matchups) 
    {
        $mc = new MatchupCreator($this->logger, $this->bookie_id);
        foreach ($matched_matchups as &$matchup) {
            if ($matchup['match_result']['status'] == false) {

                $metadata = $matchup['parsed_matchup']->getAllMetaData();
                if (!isset($metadata['gametime'])) {
                    $this->logger->debug('Unable to evaluate matchup ' . $matchup['parsed_matchup']->getTeamName(1) . ' vs ' . $matchup['parsed_matchup']->getTeamName(2) . ' for creation due to missing gametime');
                }
                else {
                    $team1 = $matchup['parsed_matchup']->getTeamName(1);
                    $team2 = $matchup['parsed_matchup']->getTeamName(2);
                    $event_name = $metadata['event_name'] ?? '';
                    $matchup_time = $metadata['gametime'];
                    $this->logger->debug('Evaluating matchup ' . $matchup['parsed_matchup']->getTeamName(1) . ' vs ' . $matchup['parsed_matchup']->getTeamName(2) . ' for creation');
                    $created_matchup = $mc->evaluateMatchup($team1, $team2, $event_name, $matchup_time);
                    if ($created_matchup != null) {
                        $matchup['match_result']['status'] = true;
                        $matchup['matched_matchup'] = $created_matchup;
                    }
                }
            }
        }
        return $matched_matchups;
    }
}
