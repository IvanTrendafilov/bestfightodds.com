<?php

/**
 * XML Parser
 *
 * Bookie: Betway
 * Sport: MMA
 *
 * Moneylines: Yes
 * Props: Yes
 *
 * URL: https://feeds.betway.com/sbeventsen?key=1E557772&keywords=ufc---martial-arts
 *
 */

require_once __DIR__ . "/../../../bootstrap.php";
require_once __DIR__ . "/../../../config/Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\Utils\OddsTools;
use BFO\Parser\OddsProcessor;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;

define('BOOKIE_NAME', 'betway');
define('BOOKIE_ID', '20');

$options = getopt("", ["mode::"]);

$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob($logger);
$parser->run($options['mode'] ?? '');

class ParserJob
{
    private $full_run = false;
    private $parsed_sport;
    private $logger = null;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function run($mode = 'normal')
    {
        $this->logger->info('Started parser');

        $content = null;
        if ($mode == 'mock') {
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "betway.xml");
            $content = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'betway.xml');
        } else {
            $matchups_url = 'https://feeds.betway.com/sbeventsen?key=1E557772&keywords=ufc---martial-arts';
            $this->logger->info("Fetching matchups through URL: " . $matchups_url);
            $content = ParseTools::retrievePageFromURL($matchups_url);
        }

        $this->parsed_sport = $this->parseContent($content);

        try {
            $op = new OddsProcessor($this->logger, BOOKIE_ID, new Ruleset());
            $op->processParsedSport($this->parsed_sport, $this->full_run);
        } catch (Exception $e) {
            $this->logger->error('Exception: ' . $e->getMessage());
        }

        $this->logger->info('Finished');
    }

    public function parseContent($xml_content)
    {
        $xml_obj = simplexml_load_string($xml_content);

        if ($xml_obj == false) {
            $this->logger->warning("Warning: XML broke!!");
        }

        $oParsedSport = new ParsedSport('MMA');

        foreach ($xml_obj->Event as $cEvent) {
            if ((string) $cEvent['started'] != 'true') //Disable live odds
            {
                $event_name = '';
                foreach ($cEvent->Keywords->Keyword as $cKeyword) {
                    if ((string) $cKeyword['type_cname'] == 'league') //Indicates event name
                    {
                        $event_name = trim((string) $cKeyword);
                    }
                }
                if ($event_name != 'Submission Underground' && $event_name != 'BKFC') {
                    foreach ($cEvent->Markets->Market as $cMarket) {
                        if (((string) $cMarket['cname'] == 'fight-winner' || (string) $cMarket['cname'] == 'fight-winner-') && count($cMarket->Outcomes->Outcome) == 2) {
                            //Regular matchup
                            $oParsedMatchup = new ParsedMatchup(
                                (string) $cMarket->Outcomes->Outcome[0]->Names->Name,
                                (string) $cMarket->Outcomes->Outcome[1]->Names->Name,
                                OddsTools::convertDecimalToMoneyline((float) $cMarket->Outcomes->Outcome[0]['price_dec']),
                                OddsTools::convertDecimalToMoneyline((float) $cMarket->Outcomes->Outcome[1]['price_dec'])
                            );

                            //Add correlation
                            $oParsedMatchup->setCorrelationID((string) $cEvent['id']);

                            //Add metadata
                            $oGameDate = new DateTime((string) $cEvent['start_at']);
                            $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                            if ($event_name != '') {
                                $oParsedMatchup->setMetaData('event_name', $event_name);
                            }

                            $oParsedSport->addParsedMatchup($oParsedMatchup);
                        } else if (
                            (string) $cMarket['cname'] == 'to-win-by-decision' ||
                            (string) $cMarket['cname'] == 'to-win-by-finish' ||
                            (string) $cMarket['cname'] == 'will-the-fight-go-the-distance' ||
                            (string) $cMarket['cname'] == 'handicap-goals-over' ||
                            substr((string) $cMarket['cname'], 0, strlen('total-rounds')) == 'total-rounds'
                        ) {
                            //Ordered props. These props are typically ordered as positive, negative, positive, negative, etc. Or over, under
                            for ($i = 0; $i < count($cMarket->Outcomes->Outcome); $i += 2) {
                                $node1 = $cMarket->Outcomes->xpath('Outcome[@index="' .  ($i + 1) . '"]');
                                $node2 = $cMarket->Outcomes->xpath('Outcome[@index="' .  ($i + 2) . '"]');

                                if (!$node1 || !$node2) {
                                    $this->logger->warning('Unable to fetch outcome for prop, using index ' . ($i + 1) . ' or ' . ($i + 2) . ': ' . var_export($cMarket->Outcomes, true));
                                } else {
                                    //Add handicap figure if available
                                    $handicap = '';
                                    if ((float) $cMarket['handicap'] != 0) {
                                        $handicap = ' ' . ((float) $cMarket['handicap']);
                                    }

                                    $oParsedProp = new ParsedProp(
                                        (string) $cEvent->Names->Name . ' :: ' . (string) $cMarket->Names->Name . ' : ' . (string) $node1[0]->Names->Name . $handicap,
                                        (string) $cEvent->Names->Name . ' :: ' . (string) $cMarket->Names->Name . ' : ' . (string) $node2[0]->Names->Name . $handicap,
                                        OddsTools::convertDecimalToMoneyline((float) $node1[0]['price_dec']),
                                        OddsTools::convertDecimalToMoneyline((float) $node2[0]['price_dec'])
                                    );

                                    //Add correlation
                                    $oParsedProp->setCorrelationID((string) $cEvent['id']);

                                    //Add metadata
                                    $oGameDate = new DateTime((string) $cEvent['start_at']);
                                    $oParsedProp->setMetaData('gametime', $oGameDate->getTimestamp());
                                    if ($event_name != '') {
                                        $oParsedProp->setMetaData('event_name', $event_name);
                                    }

                                    $oParsedSport->addFetchedProp($oParsedProp);
                                }
                            }
                        } else  if (
                            (string) $cMarket['cname'] == 'round-betting' ||
                            (string) $cMarket['cname'] == 'method-of-victory' ||
                            (string) $cMarket['cname'] == 'decision-victories' ||
                            (string) $cMarket['cname'] == 'when-will-the-fight-end-' ||
                            (string) $cMarket['cname'] == 'method-and-round-betting' ||
                            (string) $cMarket['cname'] == 'gone-in-60-seconds' ||
                            (string) $cMarket['cname'] == 'betyourway'
                        ) {
                            //Single line prop
                            for ($i = 0; $i < count($cMarket->Outcomes->Outcome); $i++) {
                                //Add handicap figure if available
                                $handicap = '';
                                if ((float) $cMarket['handicap'] != 0) {
                                    $handicap = ' ' . ((float) $cMarket['handicap']);
                                }

                                $oParsedProp = new ParsedProp(
                                    (string) $cEvent->Names->Name . ' :: ' . (string) $cMarket->Names->Name . ' : ' . (string) $cMarket->Outcomes->Outcome[$i]->Names->Name . $handicap,
                                    '',
                                    OddsTools::convertDecimalToMoneyline((float) $cMarket->Outcomes->Outcome[$i]['price_dec']),
                                    -99999
                                );

                                //Add correlation
                                $oParsedProp->setCorrelationID((string) $cEvent['id']);

                                //Add metadata
                                $oGameDate = new DateTime((string) $cEvent['start_at']);
                                $oParsedProp->setMetaData('gametime', $oGameDate->getTimestamp());
                                if ($event_name != '') {
                                    $oParsedProp->setMetaData('event_name', $event_name);
                                }

                                $oParsedSport->addFetchedProp($oParsedProp);
                            }
                        } else {
                            $this->logger->warning("Unhandled market name " . (string) $cMarket->Names->Name . " (" . (string) $cMarket['cname'] . "), maybe add to parser?");
                        }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) > 10 && $oParsedSport->getPropCount() > 10) {
            $this->full_run = true;
            $this->logger->info("Declared authoritive run");
        }

        return $oParsedSport;
    }
}
