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

    public function processParsedSport($parsed_sport, $full_run)
    {

        $parsed_sport = $this->removeMoneylineDupes($parsed_sport);
        $parsed_sport = $this->removePropDupes($parsed_sport);


        //First we match, then we add new odds
        $matchups = $parsed_sport->getParsedMatchups();
        $matched_items = [];
        foreach ($matchups as $matchup)
        {
            $matching_matchup = EventHandler::getMatchingFightV2(['team1_name' => $matchup->getTeamName(1),
                                            'team2_name' => $matchup->getTeamName(2),
                                            'future_only' => true]);

            if (!$matching_matchup)
            {
                $this->logger->warning('No matchup found for ' . $matchup->getTeamName(1) . ' vs ' . $matchup->getTeamName(2));
            }
            else
            {
                $this->logger->info('Found match for ' . $matchup->getTeamName(1) . ' vs ' . $matchup->getTeamName(2));
                //TODO: Update odds if changed

                $matched_matchups[] = $matching_matchup->getID();

            }


        }


        //Remove prop dupes AFTER MATCH


        if ($full_run) //If this is a full run we will flag any matchups odds not matched for deletion
        {
            $this->checkOddsForDeletion($matched_matchups);
            //TODO: Add deletion of props in the same way
        }
    }

    /**
     * Fetches all upcoming matchups where the bookie has previously had odds and checks if the odds are now removed. If so the odds will be flagged for deletion
     */
    private function checkOddsForDeletion($matched_fights)
    {
        $upcoming_matchups = EventHandler::getAllUpcomingMatchups();
        foreach ($upcoming_matchups as $upcoming_matchup)
        {
            $odds = EventHandler::getLatestOddsForFightAndBookie($upcoming_matchup->getID(), $this->bookie_id);
            if ($odds != null && !in_array($upcoming_matchup->getID(), $matched_fights))
            {
                $this->logger->info('Odds for ' . $upcoming_matchup->getTeamAsString(1) . ' vs ' . $upcoming_matchup->getTeamAsString(2) . ' will be flagged for deletion');
                //TODO: Implement flagging for deletion
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
