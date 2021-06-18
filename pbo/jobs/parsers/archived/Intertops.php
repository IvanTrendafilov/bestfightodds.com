<?php

/**
 * XML Parser
 *
 * Bookie: Intertops
 * Sport: Boxing
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
use BFO\General\BookieHandler;
use BFO\Parser\Jobs\ParserJobBase;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;

define('BOOKIE_NAME', 'intertops');
define('BOOKIE_ID', 16);
define(
    'BOOKIE_URLS',
    ['all' => 'http://xmlfeed.intertops.com/xmloddsfeed/v2/xml/?apikey=860879d0-f4b6-e511-a090-003048dd52d5&sportId=6&includeCent=true']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "intertops.xml"]
);

class ParserJob extends ParserJobBase
{
    public function fetchContent(array $content_urls): array
    {
        //Apply changenum
        $this->change_num = BookieHandler::getChangeNum($this->bookie_id);
        if ($this->change_num != -1) {
            $this->logger->info("Using changenum: &delta=" . $this->change_num);
            $content_urls['all'] .= '&delta=' . $this->change_num;
        }
        $this->logger->info("Fetching matchups through URL: " . $content_urls['all']);
        return ['all' => ParseTools::retrievePageFromURL($content_urls['all'])];
    }

    public function parseContent(array $content): ParsedSport
    {
        $xml = simplexml_load_string($content['all']);

        if (!$xml) {
            $this->logger->warning("Warning: XML broke!!");
        }

        $parsed_sport = new ParsedSport('Boxing');

        foreach ($xml->data->s->cat as $category_node) {
            if (
                $category_node['n'] == 'Boxing'
                || substr($category_node['n'], 0, strlen('Boxing')) === 'Boxing'
            ) {
                foreach ($category_node->m as $matchup_node) {
                    foreach ($matchup_node->t as $bet_node) {
                        if ($bet_node['n'] == 'Moving Line') {
                            //Regular matchup line
                            if (OddsTools::checkCorrectOdds((string) $bet_node->l[0]['c']) && OddsTools::checkCorrectOdds((string) $bet_node->l[1]['c'])) {
                                $parsed_matchup = new ParsedMatchup(
                                    (string) $bet_node->l[0],
                                    (string) $bet_node->l[1],
                                    (string) $bet_node->l[0]['c'],
                                    (string) $bet_node->l[1]['c']
                                );

                                $oGameDate = new DateTime($matchup_node['dt']);
                                $parsed_matchup->setMetaData('gametime', $oGameDate->getTimestamp());

                                //Add correlation ID to match matchups to props
                                $parsed_matchup->setCorrelationID((string) $matchup_node['mid']);

                                $parsed_sport->addParsedMatchup($parsed_matchup);
                            }
                        } else if ($bet_node['n'] == 'Point Score') {
                            //Point score (totalt rounds)
                            if (OddsTools::checkCorrectOdds((string) $bet_node->l[0]['c']) && OddsTools::checkCorrectOdds((string) $bet_node->l[1]['c'])) {
                                $parsed_prop = new ParsedProp(
                                    (string) $matchup_node['n'] . ' : ' . $bet_node->l[0],
                                    (string) $matchup_node['n'] . ' : ' . $bet_node->l[1],
                                    (string) $bet_node->l[0]['c'],
                                    (string) $bet_node->l[1]['c']
                                );

                                //Add correlation ID to match matchups to props
                                $parsed_prop->setCorrelationID((string) $matchup_node['mid']);

                                $parsed_sport->addFetchedProp($parsed_prop);
                            }
                        } else if ($bet_node['n'] == 'FreeForm') {
                            //Any other one line prop
                            foreach ($bet_node->l as $line_node) {
                                if (OddsTools::checkCorrectOdds((string) $line_node['c'])) {
                                    $parsed_prop = new ParsedProp(
                                        (string) $matchup_node['n'] . ' : ' . $line_node,
                                        '',
                                        (string) $line_node['c'],
                                        '-99999'
                                    );

                                    //Add correlation ID to match matchups to props
                                    $parsed_prop->setCorrelationID((string) $matchup_node['mid']);

                                    $parsed_sport->addFetchedProp($parsed_prop);
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
        if (count($parsed_sport->getParsedMatchups()) >= 2 && $this->change_num == '525600') {
            $this->full_run = true;
            $this->logger->info("Declared authoritive run");
        }

        //Before finishing up, save the changenum 30 to limit not fetching the entire feed
        if (BookieHandler::saveChangeNum(BOOKIE_ID, '30')) {
            $this->logger->info("ChangeNum stored OK: 30");
        } else {
            $this->logger->error("Error: ChangeNum was not stored");
        }

        return $parsed_sport;
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
