<?php

/**
 * XML Parser
 *
 * Bookie: Bet365
 * Sport: MMA
 *
 * Moneylines: Yes
 * Props: Yes
 * 
 * Timezone in feed: UTC+1 so assuming Europe/London (during DST, maybe needs to be adjusted once off DST)
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

define('BOOKIE_NAME', 'bet365');
define('BOOKIE_ID', 19);
define(
    'BOOKIE_URLS',
    ['all' => 'http://oddsfeed3.bet365.com/Boxing_v2.asp']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "bet365.xml"]
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

        foreach ($xml->EventGroup as $eventgroup_node) {
            if (
                !str_starts_with((string) $eventgroup_node['name'], 'Boxing')
                && !str_starts_with((string) $eventgroup_node['name'], 'World Super Series')
                && !str_starts_with((string) $eventgroup_node['name'], 'Exhibition Bout')
            ) {
                foreach ($eventgroup_node->Event as $event_node) {
                    foreach ($event_node->Market as $market_node) {
                        if (
                            strtolower((string) $market_node['Name']) == 'to win fight'
                            || strtolower((string) $market_node['Name']) == 'to win match'
                        ) {
                            //Regular matchup
                            $parsed_matchup = new ParsedMatchup(
                                (string) $market_node->Participant[0]['Name'],
                                (string) $market_node->Participant[1]['Name'],
                                OddsTools::convertDecimalToMoneyline($market_node->Participant[0]['OddsDecimal']),
                                OddsTools::convertDecimalToMoneyline($market_node->Participant[1]['OddsDecimal'])
                            );
                            //Add correlation ID to match matchups to props
                            $parsed_matchup->setCorrelationID((string) $event_node['ID']);

                            //Add time of matchup as metadata
                            $date_obj = DateTime::createFromFormat('d/m/y H:i:s', (string) $market_node['StartTime'], new DateTimeZone('Europe/London'));
                            $parsed_matchup->setMetaData('gametime', $date_obj->getTimestamp());

                            $parsed_sport->addParsedMatchup($parsed_matchup);
                        } else if (strtolower((string) $market_node['Name']) == 'total rounds' && count($market_node->Participant) == 2) {
                            $parsed_prop = new ParsedProp(
                                (string) $event_node['Name'] . ' : ' . $market_node['Name'] . ' :: ' . $market_node->Participant[0]['Name'] . ' rounds',
                                (string) $event_node['Name'] . ' : ' . $market_node['Name'] . ' :: ' . $market_node->Participant[1]['Name'] . ' rounds',
                                OddsTools::convertDecimalToMoneyline($market_node->Participant[0]['OddsDecimal']),
                                OddsTools::convertDecimalToMoneyline($market_node->Participant[1]['OddsDecimal'])
                            );

                            //Add correlation ID if available
                            $parsed_prop->setCorrelationID((string) $event_node['ID']);

                            $parsed_sport->addFetchedProp($parsed_prop);
                        } else if (strtolower((string) $market_node['Name']) == 'total rounds' && count($market_node->Participant) > 2) {
                            //TODO: Currently no way to handle multiple over/unders on total rounds. Needs a fix
                        } else if (count($market_node->Participant) == 2 && in_array($market_node->Participant[0]['Name'], array('Yes', 'No')) && in_array($market_node->Participant[1]['Name'], array('Yes', 'No'))) {
                            //Two side prop (Yes/No)
                            $parsed_prop = new ParsedProp(
                                (string) $event_node['Name'] . ' : ' . $market_node['Name'] . ' :: ' . $market_node->Participant[0]['Name'],
                                (string) $event_node['Name'] . ' : ' . $market_node['Name'] . ' :: ' . $market_node->Participant[1]['Name'],
                                OddsTools::convertDecimalToMoneyline($market_node->Participant[0]['OddsDecimal']),
                                OddsTools::convertDecimalToMoneyline($market_node->Participant[1]['OddsDecimal'])
                            );

                            //Add correlation ID if available
                            $parsed_prop->setCorrelationID((string) $event_node['ID']);

                            $parsed_sport->addFetchedProp($parsed_prop);
                        } else {
                            //Probably prop, parse as such. Treat all as one-liners
                            foreach ($market_node->Participant as $cParticipant) {
                                $parsed_prop = new ParsedProp(
                                    (string) $event_node['Name'] . ' : ' . $market_node['Name'] . ' :: ' . $cParticipant['Name'],
                                    '',
                                    OddsTools::convertDecimalToMoneyline($cParticipant['OddsDecimal']),
                                    '-99999'
                                );

                                $parsed_prop->setCorrelationID((string) $event_node['ID']);
                                $parsed_sport->addFetchedProp($parsed_prop);
                            }
                        }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($parsed_sport->getParsedMatchups()) > 8) {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $parsed_sport;
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
