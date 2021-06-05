<?php

/**
 * XML Parser
 *
 * Bookie: BetDSI
 * Sport: Boxing
 *
 * Moneylines: Yes
 * Props: Yes
 *
 * URL: https://modern.betdsi.eu/api/sportmatch/get?sportID=2359
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

define('BOOKIE_NAME', 'betdsi');
define('BOOKIE_ID', 13);
define(
    'BOOKIE_URLS',
    ['all' => 'https://modern.betdsi.eu/api/sportmatch/get?sportID=2359']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "betdsi.xml"]
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
        $parsed_sport = new ParsedSport('Boxing');
        $json = json_decode($content['all'], true);

        foreach ($json as $matchup) {
            if ($matchup['Category']['Name'] == 'Boxing Matches' && $matchup['IsLive'] == false) {
                //Fixes flipped names like Gastelum K. into K Gastelum
                $matchup['HomeTeamName'] = preg_replace('/([a-zA-Z\-\s]+)\s([a-zA-Z])\./', '$2 $1', $matchup['HomeTeamName']);
                $matchup['AwayTeamName'] = preg_replace('/([a-zA-Z\-\s]+)\s([a-zA-Z])\./', '$2 $1', $matchup['AwayTeamName']);

                $parsed_matchup = new ParsedMatchup(
                    $matchup['HomeTeamName'],
                    $matchup['AwayTeamName'],
                    OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsMoneyLine'][0]['Value']),
                    OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsMoneyLine'][1]['Value'])
                );
                $parsed_matchup->setCorrelationID((string) $matchup['ID']);

                //Add game time metadata
                $date_obj = new DateTime((string) $matchup['DateOfMatch']);
                $parsed_matchup->setMetaData('gametime', $date_obj->getTimestamp());

                $parsed_sport->addParsedMatchup($parsed_matchup);

                //Add total if available
                if (isset($matchup['PreviewOddsTotal']) && count($matchup['PreviewOddsTotal']) >= 2) {
                    //Loop through pairs of 1.5, 2.5, ..
                    for ($i = 0; $i < count($matchup['PreviewOddsTotal']); $i += 2) {
                        if ($matchup['PreviewOddsTotal'][$i]['SpecialBetValue'] == $matchup['PreviewOddsTotal'][$i + 1]['SpecialBetValue']) {
                            $parsed_prop = new ParsedProp(
                                $matchup['HomeTeamName'] . ' - ' . $matchup['AwayTeamName'] . ' : ' . $matchup['PreviewOddsTotal'][$i]['Title'] . ' rounds',
                                $matchup['HomeTeamName'] . ' - ' . $matchup['AwayTeamName'] . ' : ' . $matchup['PreviewOddsTotal'][$i + 1]['Title'] . ' rounds',
                                OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsTotal'][$i]['Value']),
                                OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsTotal'][$i + 1]['Value'])
                            );
                            //Add correlation ID
                            $parsed_prop->setCorrelationID((string) $matchup['ID']);
                            $parsed_sport->addFetchedProp($parsed_prop);
                        }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($parsed_sport->getParsedMatchups()) > 3) {
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
