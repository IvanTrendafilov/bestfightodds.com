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
 * Timezone: UTC
 * 
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Jobs\ParserJobBase;
use BFO\Parser\Utils\ParseTools;
use BFO\Utils\OddsTools;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;

define('BOOKIE_NAME', 'betway');
define('BOOKIE_ID', 20);
define(
    'BOOKIE_URLS',
    ['all' => 'https://feeds.betway.com/sbeventsen?key=1E557772&keywords=ufc---martial-arts']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "betway.xml"]
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
        $xml = simplexml_load_string($content['all']);

        if (!$xml) {
            $this->logger->warning("Warning: XML broke!!");
        }

        $parsed_sport = new ParsedSport('MMA');

        foreach ($xml->Event as $event_node) {
            if ((string) $event_node['started'] != 'true') //Disable live odds
            {
                //Set event name
                $event_name = '';
                foreach ($event_node->Keywords->Keyword as $keyword_node) {
                    if ((string) $keyword_node['type_cname'] == 'league') {
                        $event_name = trim((string) $keyword_node);
                    }
                }

                if (!in_array($event_name, [
                    'Submission Underground',
                    'BKFC',
                    'HFO'
                ])) {
                    foreach ($event_node->Markets->Market as $market_node) {
                        if (((string) $market_node['cname'] == 'fight-winner'
                                || (string) $market_node['cname'] == 'fight-winner-')
                            && count($market_node->Outcomes->Outcome) == 2
                        ) {

                            //Regular matchup
                            $parsed_matchup = new ParsedMatchup(
                                (string) $market_node->Outcomes->Outcome[0]->Names->Name,
                                (string) $market_node->Outcomes->Outcome[1]->Names->Name,
                                OddsTools::convertDecimalToMoneyline((float) $market_node->Outcomes->Outcome[0]['price_dec']),
                                OddsTools::convertDecimalToMoneyline((float) $market_node->Outcomes->Outcome[1]['price_dec'])
                            );

                            //Add correlation
                            $parsed_matchup->setCorrelationID((string) $event_node['id']);

                            //Add metadata
                            $date_obj = new DateTime((string) $event_node['start_at']);
                            $parsed_matchup->setMetaData('gametime', $date_obj->getTimestamp());
                            if ($event_name != '') {
                                $parsed_matchup->setMetaData('event_name', $event_name);
                            }

                            $parsed_sport->addParsedMatchup($parsed_matchup);
                        } else if (
                            (string) $market_node['cname'] == 'to-win-by-decision' ||
                            (string) $market_node['cname'] == 'to-win-by-finish' ||
                            (string) $market_node['cname'] == 'will-the-fight-go-the-distance' ||
                            (string) $market_node['cname'] == 'handicap-goals-over' ||
                            substr((string) $market_node['cname'], 0, strlen('total-rounds')) == 'total-rounds'
                        ) {
                            //Ordered props. These props are typically ordered as positive, negative, positive, negative, etc. Or over, under
                            for ($i = 0; $i < count($market_node->Outcomes->Outcome); $i += 2) {
                                $node1 = $market_node->Outcomes->xpath('Outcome[@index="' .  ($i + 1) . '"]');
                                $node2 = $market_node->Outcomes->xpath('Outcome[@index="' .  ($i + 2) . '"]');

                                if (!$node1 || !$node2) {
                                    $this->logger->warning('Unable to fetch outcome for prop, using index ' . ($i + 1) . ' or ' . ($i + 2) . ': ' . var_export($market_node->Outcomes, true));
                                } else {
                                    //Add handicap figure if available
                                    $handicap = '';
                                    if ((float) $market_node['handicap'] != 0) {
                                        $handicap = ' ' . ((float) $market_node['handicap']);
                                    }

                                    $parsed_prop = new ParsedProp(
                                        (string) $event_node->Names->Name . ' :: ' . (string) $market_node->Names->Name . ' : ' . (string) $node1[0]->Names->Name . $handicap,
                                        (string) $event_node->Names->Name . ' :: ' . (string) $market_node->Names->Name . ' : ' . (string) $node2[0]->Names->Name . $handicap,
                                        OddsTools::convertDecimalToMoneyline((float) $node1[0]['price_dec']),
                                        OddsTools::convertDecimalToMoneyline((float) $node2[0]['price_dec'])
                                    );

                                    //Add correlation
                                    $parsed_prop->setCorrelationID((string) $event_node['id']);

                                    //Add metadata
                                    $date_obj = new DateTime((string) $event_node['start_at']);
                                    $parsed_prop->setMetaData('gametime', $date_obj->getTimestamp());
                                    if ($event_name != '') {
                                        $parsed_prop->setMetaData('event_name', $event_name);
                                    }

                                    $parsed_sport->addFetchedProp($parsed_prop);
                                }
                            }
                        } else  if (
                            (string) $market_node['cname'] == 'round-betting' ||
                            (string) $market_node['cname'] == 'method-of-victory' ||
                            (string) $market_node['cname'] == 'decision-victories' ||
                            (string) $market_node['cname'] == 'when-will-the-fight-end-' ||
                            (string) $market_node['cname'] == 'method-and-round-betting' ||
                            (string) $market_node['cname'] == 'gone-in-60-seconds' ||
                            (string) $market_node['cname'] == 'betyourway'
                        ) {
                            //Single line prop
                            for ($i = 0; $i < count($market_node->Outcomes->Outcome); $i++) {
                                //Add handicap figure if available
                                $handicap = '';
                                if ((float) $market_node['handicap'] != 0) {
                                    $handicap = ' ' . ((float) $market_node['handicap']);
                                }

                                $parsed_prop = new ParsedProp(
                                    (string) $event_node->Names->Name . ' :: ' . (string) $market_node->Names->Name . ' : ' . (string) $market_node->Outcomes->Outcome[$i]->Names->Name . $handicap,
                                    '',
                                    OddsTools::convertDecimalToMoneyline((float) $market_node->Outcomes->Outcome[$i]['price_dec']),
                                    -99999
                                );

                                //Add correlation
                                $parsed_prop->setCorrelationID((string) $event_node['id']);

                                //Add metadata
                                $date_obj = new DateTime((string) $event_node['start_at']);
                                $parsed_prop->setMetaData('gametime', $date_obj->getTimestamp());
                                if ($event_name != '') {
                                    $parsed_prop->setMetaData('event_name', $event_name);
                                }

                                $parsed_sport->addFetchedProp($parsed_prop);
                            }
                        } else {
                            $this->logger->warning("Unhandled market name " . (string) $market_node->Names->Name . " (" . (string) $market_node['cname'] . "), maybe add to parser?");
                        }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($parsed_sport->getParsedMatchups()) > 10 && $parsed_sport->getPropCount() > 3) {
            $this->full_run = true;
            $this->logger->info("Declared authoritive run");
        }

        return $parsed_sport;
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
