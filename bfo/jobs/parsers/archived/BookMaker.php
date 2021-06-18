<?php

/**
 * XML Parser
 *
 * Bookie: BookMaker
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: Yes
 * Totals: Yes
 * Props: Yes
 * Authoritative run: Yes
 *
 * Comment: Dev version
 * 
 * URL: http://lines.bookmaker.eu
 * 
 * Timezone in feed: PST (no daylight savings change)
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

define('BOOKIE_NAME', 'bookmaker');
define('BOOKIE_ID', 3);
define(
    'BOOKIE_URLS',
    ['all' => 'https://lines.bookmaker.eu/']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "bookmaker.xml"]
);

class ParserJob extends ParserJobBase
{
    private $parsed_sport;

    public function fetchContent(array $content_urls): array
    {
        $this->logger->info("Fetching matchups through URL: " . $content_urls['all']);
        $content = ParseTools::retrievePageFromURL($content_urls['all']);

        //Store as latest feed available for ProBoxingOdds.com
        $file = fopen(GENERAL_BASEDIR . '/app/front/externalfeeds/bookmaker-latest.xml', 'w');
        fwrite($file, $content);
        fclose($file);

        return ['all' => $content];
    }

    public function parseContent(array $content): ParsedSport
    {
        $this->parsed_sport = new ParsedSport('MMA');

        $xml = simplexml_load_string($content['all']);
        if (!$xml) {
            $this->logger->warning("Warning: XML broke!!");
        }

        foreach ($xml->Leagues->league as $league_node) {
            //Matchups (also contains some props):
            if (substr(trim((string) $league_node['Description']), 0, 3) == 'MMA') {
                foreach ($league_node->game as $game_node) {
                    $skip = false;
                    //Fetch banner for this game using xpath expression. This is done to check if this is a live bet or not. Note that an banner may not always be returned and instead a game is returned
                    $banner_node = $game_node->xpath('preceding::*[1]');
                    //Check if live betting, if so it should be skipped
                    if (substr($banner_node[0]['vtm'], 0, 21) == 'LIVE IN FIGHT BETTING') {
                        $skip = true;
                    }

                    $line_node = $game_node->line;

                    if ($skip != true && OddsTools::checkCorrectOdds((string) $line_node['voddst']) && OddsTools::checkCorrectOdds((string) $line_node['hoddst'])) {
                        //Check if bet is a prop or not
                        if (ParseTools::isProp((string) $game_node['vtm']) && ParseTools::isProp((string) $game_node['htm'])) {
                            //Prop, add as such
                            $this->parsed_sport->addFetchedProp(new ParsedProp(
                                (string) $game_node['vtm'],
                                (string) $game_node['htm'],
                                (string) $line_node['voddst'],
                                (string) $line_node['hoddst']
                            ));
                        } else {
                            //Not a prop, add as matchup
                            $new_matchup = new ParsedMatchup(
                                (string) $game_node['vtm'],
                                (string) $game_node['htm'],
                                (string) $line_node['voddst'],
                                (string) $line_node['hoddst']
                            );

                            //Add game time metadata
                            $date_obj = new DateTime((string) $game_node['gmdt'] . ' ' . $game_node['gmtm']); //Timezone is PST (no daylight savings change)
                            $date_obj->add(new \DateInterval('PT7H')); //Offset +8 hours to UTC

                            $new_matchup->setMetaData('gametime', $date_obj->getTimestamp());
                            $new_matchup->setMetaData('event_name', $banner_node[0]['vtm']);

                            $this->parsed_sport->addParsedMatchup($new_matchup);

                            //Check if a total is available, if so, add it as a prop. line[0] is always over and line[1] always under
                            if (
                                isset($line_node['unt']) &&
                                isset($line_node['ovoddst']) &&
                                isset($line_node['unoddst']) &&
                                trim((string) $line_node['ovoddst']) != '' &&
                                trim((string) $line_node['unoddst']) != ''
                            ) {
                                //Total exists, add it
                                $this->parsed_sport->addFetchedProp(
                                    new ParsedProp(
                                        (string) $game_node['vtm'] . ' vs ' . (string) $game_node['htm'] . ' - OVER ' . (string) $line_node['unt'],
                                        (string) $game_node['vtm'] . ' vs ' . (string) $game_node['htm'] . ' - UNDER ' . (string) $line_node['unt'],
                                        (string) $line_node['ovoddst'],
                                        (string) $line_node['unoddst']
                                    )
                                );
                            }
                        }
                    }
                }
            }
            //Props:
            else if (substr(trim((string) $league_node['Description']), 0, 18) == 'MARTIAL ARTS PROPS') {
                foreach ($league_node->game as $game_node) {
                    //Check if prop is a Yes/No prop, if so we add both sides as options
                    if (count($game_node->line) == 2 && strcasecmp($game_node->line[0]['tmname'], 'Yes') == 0 && strcasecmp($game_node->line[1]['tmname'], 'No') == 0) {
                        //Multi line prop (Yes/No)
                        if (OddsTools::checkCorrectOdds((string) $game_node->line[0]['odds']) && OddsTools::checkCorrectOdds((string) $game_node->line[1]['odds'])) {
                            $this->parsed_sport->addFetchedProp(new ParsedProp(
                                str_replace(' VS.', ' VS. ', (string) (string) trim($game_node['htm'], " -") . ' ' . $game_node->line[0]['tmname']),
                                str_replace(' VS.', ' VS. ', (string) (string) trim($game_node['htm'], " -") . ' ' . $game_node->line[1]['tmname']),
                                (string) $game_node->line[0]['odds'],
                                (string) $game_node->line[1]['odds']
                            ));
                        }
                    } else {
                        //Single line props
                        foreach ($game_node->line as $line_node) {
                            if (OddsTools::checkCorrectOdds((string) $line_node['odds'])) {
                                $this->parsed_sport->addFetchedProp(new ParsedProp(
                                    str_replace(' VS.', ' VS. ', (string) (string) trim($game_node['htm'], " -") . ' ' . $line_node['tmname']),
                                    '',
                                    (string) $line_node['odds'],
                                    '-99999'
                                ));
                            }
                        }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($this->parsed_sport->getParsedMatchups()) >= 10) {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $this->parsed_sport;
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
