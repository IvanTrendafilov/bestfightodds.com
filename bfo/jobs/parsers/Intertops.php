<?php

/**
 * XML Parser
 *
 * Bookie: Intertops
 * Sport: MMA
 *
 * Moneylines: Yes
 * Props: Yes
 * 
 * URL: http://xmlfeed.intertops.com/xmloddsfeed/v2/xml/?apikey=860879d0-f4b6-e511-a090-003048dd52d5&sportId=6&includeCent=true
 *
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\Utils\OddsTools;
use BFO\Parser\OddsProcessor;
use BFO\General\BookieHandler;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;

define('BOOKIE_NAME', 'intertops');
define('BOOKIE_ID', '18');

$options = getopt("", ["mode::"]);

$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob($logger);
$parser->run($options['mode'] ?? '');

class ParserJob
{
    private $full_run = false;
    private $logger = null;
    private $change_num = -1;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function run($mode = 'normal')
    {
        $this->logger->info('Started parser');

        $content = null;
        if ($mode == 'mock') {
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "intertops.xml");
            $content = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'intertops.xml');
        } else {
            $matchups_url = 'http://xmlfeed.intertops.com/xmloddsfeed/v2/xml/?apikey=860879d0-f4b6-e511-a090-003048dd52d5&sportId=6&includeCent=true';
            $this->change_num = BookieHandler::getChangeNum(BOOKIE_ID);
            if ($this->change_num != -1) {
                $this->logger->info("Using changenum: &delta=" . $this->change_num);
                $matchups_url .= '&delta=' . $this->change_num;
            }
            $this->logger->info("Fetching matchups through URL: " . $matchups_url);
            $content = ParseTools::retrievePageFromURL($matchups_url);
        }

        $parsed_sport = $this->parseContent($content);

        try {
            $op = new OddsProcessor($this->logger, BOOKIE_ID, new Ruleset());
            $op->processParsedSport($parsed_sport, $this->full_run);
        } catch (Exception $e) {
            $this->logger->error('Exception: ' . $e->getMessage());
        }

        $this->logger->info('Finished');
    }

    public function parseContent($content)
    {
        $xml = simplexml_load_string($content);

        if (!$xml) {
            $this->logger->warning("Warning: XML broke!!");
        }

        $oParsedSport = new ParsedSport('MMA');

        foreach ($xml->data->s->cat as $category_node) {
            if (
                $category_node['n'] != 'Boxing'
                && substr($category_node['n'], 0, strlen('Boxing')) !== 'Boxing'
                && substr($category_node['n'], 0, strlen('Kickboxing')) !== 'Kickboxing'
                && substr($category_node['n'], 0, strlen('Conor McGregor v Floyd Mayweather Jr')) !== 'Conor McGregor v Floyd Mayweather Jr'
            ) {
                foreach ($category_node->m as $matchup_node) {
                    foreach ($matchup_node->t as $bet_node) {
                        if ($bet_node['n'] == 'Single Match') {
                            //Regular matchup line
                            if (OddsTools::checkCorrectOdds((string) $bet_node->l[0]['c']) && OddsTools::checkCorrectOdds((string) $bet_node->l[1]['c'])) {
                                $oTempMatchup = new ParsedMatchup(
                                    (string) $bet_node->l[0],
                                    (string) $bet_node->l[1],
                                    (string) $bet_node->l[0]['c'],
                                    (string) $bet_node->l[1]['c']
                                );

                                $oGameDate = new DateTime($matchup_node['dt']);
                                $oTempMatchup->setMetaData('gametime', $oGameDate->getTimestamp());

                                //Add correlation ID to match matchups to props
                                $oTempMatchup->setCorrelationID((string) $matchup_node['mid']);

                                $oParsedSport->addParsedMatchup($oTempMatchup);
                            }
                        } else if ($bet_node['n'] == 'Point Score') {
                            //Point score (totalt rounds)
                            if (OddsTools::checkCorrectOdds((string) $bet_node->l[0]['c']) && OddsTools::checkCorrectOdds((string) $bet_node->l[1]['c'])) {
                                $oTempProp = new ParsedProp(
                                    (string) $matchup_node['n'] . ' : ' . $bet_node->l[0],
                                    (string) $matchup_node['n'] . ' : ' . $bet_node->l[1],
                                    (string) $bet_node->l[0]['c'],
                                    (string) $bet_node->l[1]['c']
                                );

                                //Add correlation ID to match matchups to props
                                $oTempProp->setCorrelationID((string) $matchup_node['mid']);

                                $oParsedSport->addFetchedProp($oTempProp);
                            }
                        } else if ($bet_node['n'] == 'FreeForm') {
                            //Any other one line prop
                            foreach ($bet_node->l as $cLine) {
                                if (OddsTools::checkCorrectOdds((string) $cLine['c'])) {
                                    $oTempProp = new ParsedProp(
                                        (string) $matchup_node['n'] . ' : ' . $cLine,
                                        '',
                                        (string) $cLine['c'],
                                        '-99999'
                                    );

                                    //Add correlation ID to match matchups to props
                                    $oTempProp->setCorrelationID((string) $matchup_node['mid']);

                                    $oParsedSport->addFetchedProp($oTempProp);
                                }
                            }
                        } else {
                            $this->logger->warning("Unhandled category: " . $bet_node['n']);
                        }
                    }
                }
            }
        }
        //Declare authorative run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) >= 10 && $this->change_num == '525600') {
            $this->full_run = true;
            $this->logger->info("Declared authoritive run");
        }

        //Before finishing up, save the changenum 30 to limit not fetching the entire feed
        if (BookieHandler::saveChangeNum(BOOKIE_ID, '30')) {
            $this->logger->info("ChangeNum stored OK: 30");
        } else {
            $this->logger->error("Error: ChangeNum was not stored");
        }

        return $oParsedSport;
    }
}
