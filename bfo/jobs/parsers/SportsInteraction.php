<?php

/**
 * XML Parser
 *
 * Bookie: SportsInteraction
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: Yes
 * Totals: Yes
 * Props: Yes
 * Authoritative run: Yes
 * 
 * URL: https://www.sportsinteraction.com/odds_feeds/30/?consumer_name=bfodds&password=bfodds3145&format_id=4
 *
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Jobs\ParserJobBase;
use BFO\Parser\Utils\ParseTools;
use BFO\Utils\OddsTools;
use BFO\Parser\OddsProcessor;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;

define('BOOKIE_NAME', 'sportsinteraction');
define('BOOKIE_ID', 8);
define(
    'BOOKIE_URLS',
    ['all' => 'https://www.sportsinteraction.com/odds_feeds/30/?consumer_name=bfodds&password=bfodds3145&format_id=4']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "sportsint.xml"]
);

class ParserJob extends ParserJobBase
{
    public function fetchContent(array $content_urls): array
    {
        $this->logger->info("Fetching matchups through URL: " . $content_urls['all']);
        return ['all' => ParseTools::retrievePageFromURL($content_urls['all'])];
    }

    public function parseContent(array $content): ParsedSport
    {
        $content['all'] = preg_replace("<SportsInteractionLines>", "<SportsInteractionLines>\n", $content['all']);
        $content['all'] = preg_replace("</SportsInteractionLines>", "\n</SportsInteractionLines>", $content['all']);

        $xml = simplexml_load_string($content['all']);

        if (!$xml) {
            $this->logger->warning("Warning: XML broke!!");
        }
        if (isset($xml['reason'])) {
            $this->logger->error("Error: " . $xml['reason']);
        }
        if ($xml->getName() == 'feed-unchanged') {
            $this->logger->info("Feed reported no changes");
        }

        $parsed_sport = new ParsedSport('MMA');

        if (isset($xml->EventType)) {
            foreach ($xml->EventType as $eventtype_node) {
                if (trim((string) $eventtype_node['NAME']) == 'MMA') {
                    foreach ($eventtype_node->Event as $event_node) {
                        if (strpos(strtoupper($event_node->Name), 'FIGHT OF THE NIGHT') !== false) {
                            //Fight of the night prop
                            foreach ($this->parseFOTN($event_node) as $parsed_prop) {
                                $parsed_sport->addFetchedProp($parsed_prop);
                            }
                        } else if ($event_node->Bet[0]['TYPE'] == "" && !(strpos($event_node->Name, 'Total Event') !== false)) {
                            //Regular matchup
                            $parsed_matchup = null;
                            if (isset($event_node->Bet[2])) {
                                //Three way
                                if (
                                    OddsTools::checkCorrectOdds((string) $event_node->Bet[0]->Price)
                                    && OddsTools::checkCorrectOdds((string) $event_node->Bet[2]->Price)
                                    && !isset($event_node->Bet[3]) //Temporary fix to remove props such as FOTN
                                    && !isset($event_node->Bet[4]) //Temporary fix to remove props such as FOTN
                                    && !((string) $event_node->Bet[0]->Price == '-10000' && (string) $event_node->Bet[2]->Price == '-10000')
                                ) {
                                    $parsed_matchup = new ParsedMatchup(
                                        (string) $event_node->Bet[0]->Runner,
                                        (string) $event_node->Bet[2]->Runner,
                                        (string) $event_node->Bet[0]->Price,
                                        (string) $event_node->Bet[2]->Price
                                    );
                                }
                            } else {
                                if (
                                    OddsTools::checkCorrectOdds((string) $event_node->Bet[0]->Price)
                                    && OddsTools::checkCorrectOdds((string) $event_node->Bet[1]->Price)
                                    && !isset($event_node->Bet[3]) //Temporary fix to remove props such as FOTN
                                    && !isset($event_node->Bet[4]) //Temporary fix to remove props such as FOTN
                                    && !((string) $event_node->Bet[0]->Price == '-10000' && (string) $event_node->Bet[1]->Price == '-10000')
                                ) {
                                    //Two way
                                    $parsed_matchup = new ParsedMatchup(
                                        (string) $event_node->Bet[0]->Runner,
                                        (string) $event_node->Bet[1]->Runner,
                                        (string) $event_node->Bet[0]->Price,
                                        (string) $event_node->Bet[1]->Price
                                    );
                                }
                            }
                            if ($parsed_matchup != null) {
                                //Add time of matchup as metadata
                                if (isset($event_node->Date)) {
                                    $oGameDate = new DateTime($event_node->Date);
                                    $parsed_matchup->setMetaData('gametime', $oGameDate->getTimestamp());
                                }

                                //Add event name as metadata
                                if (isset($event_node->Name)) {
                                    $event_pieces = explode(' - ', (string) $event_node->Name);
                                    if ($event_pieces[0] != '') {
                                        $parsed_matchup->setMetaData('event_name', trim($event_pieces[0]));
                                    }
                                }

                                $parsed_matchup->setCorrelationID(trim($event_node->Name));
                                $parsed_sport->addParsedMatchup($parsed_matchup);
                            }
                        } else if ($event_node->Bet[0]['TYPE'] != "" && (count(array_intersect(
                            ['yes', 'no', 'over', 'under'],
                            [strtolower($event_node->Bet[0]->Runner), strtolower($event_node->Bet[1]->Runner)]
                        )) > 0)) {
                            //Two side prop bet since bet 1 or 2 contains the words yes,no,over or under
                            $parsed_prop = $this->parseTwoSideProp($event_node);
                            if ($parsed_prop != null) {
                                $parsed_sport->addFetchedProp($parsed_prop);
                            }
                        } else {
                            //Prop - All other
                            foreach ($event_node->Bet as $bet_node) {
                                if (
                                    OddsTools::checkCorrectOdds((string) $bet_node->Price)
                                    && !(intval($bet_node->Price) < -9000)
                                ) {
                                    $parsed_prop = new ParsedProp(
                                        (string) $event_node->Name . ' ::: ' . $bet_node['TYPE'] . ' :: ' . $bet_node->BetTypeExtraInfo . ' : ' . $bet_node->Runner,
                                        '',
                                        (string) $bet_node->Price,
                                        '-99999'
                                    );
                                    $parsed_prop->setCorrelationID(trim($event_node->Name));
                                    $parsed_sport->addFetchedProp($parsed_prop);
                                }
                            }
                        }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($parsed_sport->getParsedMatchups()) >= 10 && $xml->getName() != 'feed-unchanged') {
            $this->full_run = true;
            $this->logger->info("Declared authoritive run");
        }

        return $parsed_sport;
    }

    private function parseFOTN($event_node)
    {
        $props = [];
        foreach ($event_node->Bet as $bet_node) {
            $prop = new ParsedProp(
                (string) $event_node->Name . ' - ' . $bet_node->Runner,
                '',
                (string) $bet_node->Price,
                '-99999'
            );

            $prop->setCorrelationID(trim($event_node->Name));
            $props[] = $prop;
        }
        return $props;
    }

    private function parseTwoSideProp($event_node)
    {
        //Find tie or draw and exclude it
        $bet_nodes = [];
        foreach ($event_node->Bet as $key => $bet_node) {
            if ($bet_node->Runner != 'Tie' && $bet_node->Runner != 'Draw') {
                $bet_nodes[] = $bet_node;
            }
        }

        if (count($bet_nodes) == 2) {
            $parsed_prop = new ParsedProp(
                (string) $event_node->Name . ' ::: ' . $bet_nodes[0]['TYPE'] . ' :: ' . $bet_nodes[0]->Handicap . ' ' . $bet_nodes[0]->BetTypeExtraInfo . ' : ' . $bet_nodes[0]->Runner,
                (string) $event_node->Name . ' ::: ' . $bet_nodes[1]['TYPE'] . ' :: ' . $bet_nodes[1]->Handicap . ' ' . $bet_nodes[1]->BetTypeExtraInfo . ' : ' . $bet_nodes[1]->Runner,
                (string) $bet_nodes[0]->Price,
                (string) $bet_nodes[1]->Price
            );
            $parsed_prop->setCorrelationID(trim($event_node->Name));
            return $parsed_prop;
        }
        $this->logger->warning("Invalid special two side prop: " . $event_node->Name . ' ::: ' . $bet_nodes[0]['TYPE'] . ' :: ' .  $bet_nodes[0]->Handicap . ' ' . $bet_nodes[0]->BetTypeExtraInfo . ' : ' . $bet_nodes[0]->Runner);
        return null;
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
