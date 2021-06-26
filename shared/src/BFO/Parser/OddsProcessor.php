<?php

namespace BFO\Parser;

use BFO\Parser\Utils\ParseTools;
use BFO\Parser\Utils\ParseRunLogger;
use BFO\General\OddsHandler;
use BFO\General\BookieHandler;
use BFO\General\EventHandler;
use BFO\DataTypes\Fight;
use BFO\DataTypes\FightOdds;
use BFO\Utils\OddsTools;
use Psr\Log\LoggerInterface;
use BFO\Parser\RulesetInterface;

/**
 * OddsProcessor - Goes through parsed matchups and props, matches and stores them in the database. Also keeps track of changes to matchups and more..
 */
class OddsProcessor
{
    private LoggerInterface $logger;
    private int $bookie_id;
    private RulesetInterface $creation_ruleset;

    public function __construct(LoggerInterface $logger, $bookie_id, RulesetInterface $creation_ruleset)
    {
        $this->logger = $logger;
        $this->bookie_id = $bookie_id;
        $this->creation_ruleset = $creation_ruleset;
    }

    /**
     * Processes parsed sport from a bookie parser
     */
    public function processParsedSport(ParsedSport $parsed_sport, bool $full_run): void
    {
        //Check that bookie is valid
        if (!BookieHandler::getBookieByID($this->bookie_id)) {
            $this->logger->error('Bookie does not exist in database: ' . $this->bookie_id . ' Aborting');
            return;
        }
        
        //Pre-populate correlation table with entries from database. These can be overwritten later in the matching matchups method
        $correlations = OddsHandler::getCorrelationsForBookie($this->bookie_id);
        foreach ($correlations as $correlation) {
            ParseTools::saveCorrelation($correlation['correlation'], $correlation['matchup_id']);
        }

        $parsed_sport = $this->removeMoneylineDupes($parsed_sport);
        $parsed_sport = $this->removePropDupes($parsed_sport);

        $matched_matchups = $this->matchMatchups($parsed_sport->getParsedMatchups());

        if (PARSE_CREATEMATCHUPS == true) {
            $matched_matchups = $this->createUnmatchedMatchups($matched_matchups);
        }

        $pp = new PropProcessor($this->logger, $this->bookie_id);
        $matched_props = $pp->matchProps($parsed_sport->getFetchedProps());

        $matched_props = $this->removeMatchedPropDupes($matched_props);

        //If this is a full run, clear the unmatched table for this bookie first
        if ($full_run) {
            EventHandler::clearUnmatched(null, $this->bookie_id);
        }

        $matchup_unmatched_count = $this->logUnmatchedMatchups($matched_matchups);
        $prop_unmatched_count = $pp->logUnmatchedProps($matched_props);

        $this->updateMatchedMatchups($matched_matchups);
        $pp->updateMatchedProps($matched_props);

        if ($full_run) { //If this is a full run we will flag any matchups odds not matched for deletion
            $this->flagMatchupOddsForDeletion($matched_matchups);
            $this->flagPropOddsForDeletion($matched_props);
            $this->flagEventPropOddsForDeletion($matched_props);
        }

        //Store correlations in database for later use
        $temporary_stored_correlations = [];
        foreach ((ParseTools::getAllCorrelations()) as $correlation_key => $correlation_value) {
            $temporary_stored_correlations[] = ['correlation' => $correlation_key, 'matchup_id' => $correlation_value];
        }
        OddsHandler::storeCorrelations($this->bookie_id, $temporary_stored_correlations);

        $this->logger->info('Result - Matchups: ' . (count($matched_matchups) - $matchup_unmatched_count) . '/' . count($parsed_sport->getParsedMatchups())
            . ' Props: ' . (count($matched_props) - $prop_unmatched_count) . '/' . count($parsed_sport->getFetchedProps())
            . ' Full run: ' . ($full_run ? 'Yes' : 'No'));

        //Log this run to the database
        $parse_run_logger = new ParseRunLogger();
        $parse_run_logger->logRun(-1, [
            'bookie_id' => $this->bookie_id,
            'parsed_matchups' => count($parsed_sport->getParsedMatchups()),
            'parsed_props' => count($parsed_sport->getFetchedProps()),
            'matched_matchups' => (count($matched_matchups) - $matchup_unmatched_count),
            'matched_props' => (count($matched_props) - $prop_unmatched_count),
            'status' => 1,
            'authoritative_run' => ($full_run ? 1 : 0)
        ]);
    }

    /**
     * Runs through a set of parsed matchups and finds a matching matchup in the database for them
     */
    public function matchMatchups(array $parsed_matchups): array
    {
        $matched_items = [];
        foreach ($parsed_matchups as $parsed_matchup) {
            $match = false;
            $matching_matchup = EventHandler::getMatchingMatchup(
                team1_name: $parsed_matchup->getTeamName(1),
                team2_name: $parsed_matchup->getTeamName(2),
                future_only: true
            );

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
    public function updateMatchedMatchups(array $matched_matchups): void
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
    private function updateOneMatchedMatchup(array $matched_matchup): bool
    {
        $event = EventHandler::getEvent($matched_matchup['matched_matchup']->getEventID(), true);
        $temp_matchup = new Fight(0, $matched_matchup['parsed_matchup']->getTeamName(1), $matched_matchup['parsed_matchup']->getTeamName(2), -1);

        if ($event != null) {
            //This routine is used to switch the order of odds in a matchup if order has changed through the use of alt names or similar
            if (($matched_matchup['matched_matchup']->hasExternalOrderChanged() || $temp_matchup->hasOrderChanged()) && !$matched_matchup['parsed_matchup']->isSwitchedFromOutside()) {
                $matched_matchup['parsed_matchup']->switchOdds();
            }

            //Store any metadata for the matchup
            $metadata = $matched_matchup['parsed_matchup']->getAllMetaData();
            foreach ($metadata as $key => $val) {
                EventHandler::setMetaDataForMatchup($matched_matchup['matched_matchup']->getID(), $key, $val, $this->bookie_id);
            }

            if ($matched_matchup['parsed_matchup']->hasMoneyline()) {
                $odds = new FightOdds(
                    (int) $matched_matchup['matched_matchup']->getID(),
                    $this->bookie_id,
                    $matched_matchup['parsed_matchup']->getMoneyLine(1),
                    $matched_matchup['parsed_matchup']->getMoneyLine(2),
                    OddsTools::standardizeDate(date('Y-m-d'))
                );

                if (OddsHandler::checkMatchingOdds($odds)) {
                    $this->logger->info("- " . $matched_matchup['matched_matchup']->getTeamAsString(1) . " vs " . $matched_matchup['matched_matchup']->getTeamAsString(2) . ": nothing has changed since last odds");
                } else {
                    $this->logger->info("- " . $matched_matchup['matched_matchup']->getTeamAsString(1) . " vs " . $matched_matchup['matched_matchup']->getTeamAsString(2) . ": adding new odds");
                    $result = OddsHandler::addNewFightOdds($odds);
                    if (!$result) {
                        $this->logger->error("-- Error adding odds");
                        return false;
                    } else {
                        //When odds are added, if the matchup was added manually or through scheduler (!= 1) we change the createaudit for this matchup so that it can be removed automatically if all odds are removed.
                        if ($matched_matchup['matched_matchup']->getCreateSource() != 1) {
                            if (EventHandler::addCreateAudit($matched_matchup['matched_matchup']->getID(), 1)) {
                                $this->logger->debug("-- Updated change audit to 1 (sportsbook created) for " . $matched_matchup['matched_matchup']->getID());
                            } else {
                                $this->logger->error("-- Couldn't change create audit to 1 (sportsbook created) for matchup " . $matched_matchup['matched_matchup']->getID());
                            }
                        }
                    }
                }
                return true;
            }
        } else {
            //Trying to add odds for a matchup with no upcoming event
            $this->logger->error("--- match wrongfully matched to event OR odds are old");
        }
        return false;
    }

    /**
     * Fetches all upcoming matchups where the bookie has previously had odds and checks if the odds are now removed. If so the odds will be flagged for deletion
     */
    private function flagMatchupOddsForDeletion(array $matched_matchups): void
    {
        $upcoming_matchups = EventHandler::getMatchups(future_matchups_only: true);
        foreach ($upcoming_matchups as $upcoming_matchup) {
            $matchup_date_obj = new \DateTime();
            $matchup_date_obj->setTimestamp($upcoming_matchup->getMetadata('min_gametime'));
            if ($matchup_date_obj > new \DateTime() || !$upcoming_matchup->getMetadata('min_gametime')) { //Only flag if this matchup is in the future as specified in metadata min_gametime
                $odds = OddsHandler::getLatestOddsForFightAndBookie($upcoming_matchup->getID(), $this->bookie_id);
                if ($odds != null) {
                    $this->logger->debug('Bookie has odds for ' . $upcoming_matchup->getTeamAsString(1) . ' vs ' . $upcoming_matchup->getTeamAsString(2)
                        . '. Checking if this should be flagged for deletion');
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
    }

    /**
     * Fetches all upcoming matchups where the bookie has previously had prop odds and checks if the prop odds are now removed. If so the prop odds will be flagged for deletion
     */
    private function flagPropOddsForDeletion(array $matched_props): void
    {
        $upcoming_matchups = EventHandler::getMatchups(future_matchups_only: true);
        foreach ($upcoming_matchups as $upcoming_matchup) {
            $matchup_date_obj = new \DateTime();
            $matchup_date_obj->setTimestamp($upcoming_matchup->getMetadata('min_gametime'));
            if ($matchup_date_obj > new \DateTime() || !$upcoming_matchup->getMetadata('min_gametime')) { //Only flag if this matchup is in the future as specified in metadata min_gametime
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
                        $this->logger->info('Prop odds with type ' . $stored_prop->getPropTypeID() . ' for team num ' . $stored_prop->getTeamNumber()
                            . ' in ' . $upcoming_matchup->getTeamAsString(1) . ' vs ' . $upcoming_matchup->getTeamAsString(2) . ' will be flagged for deletion');
                        OddsHandler::flagPropOddsForDeletion($this->bookie_id, $stored_prop->getMatchupID(), $stored_prop->getPropTypeID(), $stored_prop->getTeamNumber());
                    } else {
                        OddsHandler::removeFlagged($this->bookie_id, $stored_prop->getMatchupID(), null, $stored_prop->getPropTypeID(), $stored_prop->getTeamNumber()); //Remove any previous flags
                        $this->logger->debug('Prop odds with type ' . $stored_prop->getPropTypeID() . ' for team num ' . $stored_prop->getTeamNumber()
                            . ' in ' . $upcoming_matchup->getTeamAsString(1) . ' vs ' . $upcoming_matchup->getTeamAsString(2) . ' still relevant');
                    }
                }
            }
        }
    }

    /**
     * Fetches all events where the bookie has previously had event prop odds and checks if the event prop odds are now removed. If so the event prop odds will be flagged for deletion
     */
    private function flagEventPropOddsForDeletion(array $matched_props): void
    {
        $upcoming_events = EventHandler::getEvents(future_events_only: true);
        foreach ($upcoming_events as $upcoming_event) {
            $stored_props = OddsHandler::getCompletePropsForEvent($upcoming_event->getID(), 0, $this->bookie_id);
            if ($stored_props) {
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
    public function removeMoneylineDupes(ParsedSport $parsed_sport): ParsedSport
    {
        $matchups = $parsed_sport->getParsedMatchups();
        for ($y = 0; $y < sizeof($matchups); $y++) {
            for ($x = 0; $x < sizeof($matchups); $x++) {
                if (
                    $x != $y
                    && $matchups[$y]->getTeamName(1) == $matchups[$x]->getTeamName(1)
                    && $matchups[$y]->getTeamName(2) == $matchups[$x]->getTeamName(2)
                ) {
                    //Found a match
                    $arbitrage_subject = ParseTools::getArbitrage($matchups[$y]->getMoneyLine(1), $matchups[$y]->getMoneyLine(2));
                    $arbitrage_challenger = ParseTools::getArbitrage($matchups[$x]->getMoneyLine(1), $matchups[$x]->getMoneyLine(2));

                    $this->logger->info('Removing dupe: ' . $matchups[$y]->getTeamName(1) . ' vs ' . $matchups[$y]->getTeamName(2));

                    if ($arbitrage_subject > $arbitrage_challenger) { //Challenger won
                        unset($matchups[$y]);
                    } elseif ($arbitrage_subject < $arbitrage_challenger) { //Subject won
                        unset($matchups[$x]);
                    } else { //Draw, remove one
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
    public function removePropDupes(ParsedSport $parsed_sport): ParsedSport
    {
        $props = $parsed_sport->getFetchedProps();
        for ($y = 0; $y < sizeof($props); $y++) {
            for ($x = 0; $x < sizeof($props); $x++) {
                if (
                    $x != $y
                    && $props[$y]->getCorrelationID() == $props[$x]->getCorrelationID()
                    && $props[$y]->getTeamName(1) == $props[$x]->getTeamName(1)
                    && $props[$y]->getTeamName(2) == $props[$x]->getTeamName(2)
                ) {
                    //Found a match
                    $arbitrage_subject = ParseTools::getArbitrage($props[$y]->getMoneyLine(1), $props[$y]->getMoneyLine(2));
                    $arbitrage_challenger = ParseTools::getArbitrage($props[$x]->getMoneyLine(1), $props[$x]->getMoneyLine(2));

                    $this->logger->info('Removing dupe: ' . $props[$y]->getTeamName(1) . ' vs ' . $props[$y]->getTeamName(2));

                    if ($arbitrage_subject > $arbitrage_challenger) { //Challenger won
                        unset($props[$y]);
                    } elseif ($arbitrage_subject < $arbitrage_challenger) { //Subject won
                        unset($props[$x]);
                    } else { //Draw, remove one
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
    private function removeMatchedPropDupes(array $props): array
    {
        for ($y = 0; $y < sizeof($props); $y++) {
            if ($props[$y]['match_result']['status'] == true) {
                for ($x = 0; $x < sizeof($props); $x++) {
                    if ($x != $y) { //Ignore self
                        if (
                            $props[$x]['match_result']['status'] == true
                            && $props[$x]['match_result']['template']->getPropTypeID() == $props[$y]['match_result']['template']->getPropTypeID()
                            && $props[$x]['match_result']['matchup']['matchup_id'] == $props[$y]['match_result']['matchup']['matchup_id']
                            && $props[$x]['match_result']['matchup']['team'] == $props[$y]['match_result']['matchup']['team']
                        ) {
                            $this->logger->debug('Matching dupe for proptype_id: ' . $props[$x]['match_result']['template']->getPropTypeID() . ', matchup_id: ' . $props[$x]['match_result']['matchup']['matchup_id'] .
                                ' ' . $props[$y]['prop']->getMoneyLine(1) . '/' . $props[$y]['prop']->getMoneyLine(2) . ' and ' . $props[$x]['prop']->getMoneyLine(1) . '/' . $props[$x]['prop']->getMoneyLine(2));

                            $arbitrage_subject = ParseTools::getArbitrage($props[$y]['prop']->getMoneyLine(1), $props[$y]['prop']->getMoneyLine(2));
                            $arbitrage_challenger = ParseTools::getArbitrage($props[$x]['prop']->getMoneyLine(1), $props[$x]['prop']->getMoneyLine(2));

                            if ($arbitrage_subject > $arbitrage_challenger) { //Challenger won
                                $this->logger->info('Removing subject dupe: ' . $props[$y]['prop']->getMoneyLine(1) . '/' . $props[$y]['prop']->getMoneyLine(2) . ' for matchup_id: ' . $props[$x]['match_result']['matchup']['matchup_id'] . ' proptype_id: ' . $props[$x]['match_result']['template']->getPropTypeID() . ' team_num: ' . $props[$y]['match_result']['matchup']['team']);
                                unset($props[$y]);
                            } elseif ($arbitrage_subject < $arbitrage_challenger) { //Subject won
                                $this->logger->info('Removing challenger dupe: ' . $props[$x]['prop']->getMoneyLine(1) . '/' . $props[$x]['prop']->getMoneyLine(2) . ' for matchup_id: ' . $props[$x]['match_result']['matchup']['matchup_id'] . ' proptype_id: ' . $props[$x]['match_result']['template']->getPropTypeID() . ' team_num: ' . $props[$y]['match_result']['matchup']['team']);
                                unset($props[$x]);
                            } else { //Draw, remove one
                                $this->logger->info('Removing identical dupe: ' . $props[$x]['prop']->getMoneyLine(1) . '/' . $props[$x]['prop']->getMoneyLine(2) . ' for matchup_id: ' . $props[$x]['match_result']['matchup']['matchup_id'] . ' proptype_id: ' . $props[$x]['match_result']['template']->getPropTypeID() . ' team_num: ' . $props[$y]['match_result']['matchup']['team']);
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

    /**
     * If a matchup cannot be matched and parsed properly this function will call EventHandler to log a record of it to the database
     */
    private function logUnmatchedMatchups(array $matched_matchups): int
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

    private function createUnmatchedMatchups(array $matched_matchups): array
    {
        $mc = new MatchupCreator($this->logger, $this->bookie_id, $this->creation_ruleset);
        foreach ($matched_matchups as &$matchup) {
            if ($matchup['match_result']['status'] == false) {
                $metadata = $matchup['parsed_matchup']->getAllMetaData();
                if (!isset($metadata['gametime'])) {
                    $this->logger->debug('Unable to evaluate matchup ' . $matchup['parsed_matchup']->getTeamName(1) . ' vs ' . $matchup['parsed_matchup']->getTeamName(2) . ' for creation due to missing gametime');
                } else {
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
