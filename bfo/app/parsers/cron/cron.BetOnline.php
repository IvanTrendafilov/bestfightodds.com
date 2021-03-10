<?php
/**
 * XML Parser
 *
 * Bookie: BetOnline
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: Yes (Only one as specified)
 * Props: No* (*props are currently handled in a separate parser (BetOnlineProps))
 * Authoritative run: Yes* (Won't be usable since we are running props in separate parser. The two should be combined into a standalone cron job later)
 *
 * Comment: Prod version
 * 
 * Pregames URL (this feed): https://api.linesfeed.info/v1/pregames/lines/pu?sport=Martial%20Arts&subSport=MMA
 * Props URL (handled in separate parser): https://api.linesfeed.info/v1/contest/lines/pu?sport=Martial%20Arts&subSport=MMA
 *
 */

require_once 'config/inc.config.php';
require_once 'vendor/autoload.php';
require_once 'lib/bfocore/parser/general/inc.ParserMain.php';

define('BOOKIE_NAME', 'betonline');
define('BOOKIE_ID', '12');

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

        $matchups = null;

        $content = [];

        if ($mode == 'mock')
        {
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "betonline.json");
            $content['matchups'] = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'betonline.json');
            $this->logger->info("Note: Using props mock file at " . PARSE_MOCKFEEDS_DIR . "betonlineprops.json");
            $content['props'] = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'betonlineprops.json');
        }
        else
        {
            $matchups_url = 'https://api.linesfeed.info/v1/pregames/lines/pu?sport=Martial%20Arts&subSport=MMA';
            $props_url = 'https://api.linesfeed.info/v1/contest/lines/pu?sport=Martial%20Arts&subSport=MMA';

            $this->logger->info("Fetching matchups through URL: " . $matchups_url);
            $this->logger->info("Fetching props through URL: " . $props_url);
            ParseTools::retrieveMultiplePagesFromURLs([$matchups_url, $props_url]);

            $content['matchups'] = ParseTools::getStoredContentForURL($matchups_url);
            $content['props'] = ParseTools::getStoredContentForURL($props_url);;
        }

        $parsed_sport = $this->parseContent($content);

        $op = new OddsProcessor($this->logger, BOOKIE_ID);
        $op->processParsedSport($parsed_sport, $this->full_run);

        $this->logger->info('Finished');
    }

    private function parseContent($content)
    {
        $this->parsed_sport = new ParsedSport('MMA');

        $this->parseMatchups($content['matchups']);
        $this->parseProps($content['props']);

        $missing_content = false;
        if ($content['matchups'] == '')
        {
            $this->logger->error('Retrieving matchups failed');
            $missing_content = true;
        }
        if ($content['props'] == '')
        {
            $this->logger->error('Retrieving props failed');
            $missing_content = true;
        }

        if (!$missing_content && count($this->parsed_sport->getParsedMatchups()) >= 5 && count($this->parsed_sport->getFetchedProps()) >= 5)
        {
            $this->full_run = true;
            $this->logger->info("Declared authoritive run");
        }

        return $this->parsed_sport;
    }

    private function parseMatchups($content)
    {
        $json = json_decode($content);
        if ($json == false)
        {
            $this->logger->error("Unable to decode JSON: " . substr($content, 0,50) . "...");
            return false;
        }

        foreach ($json->preGameEvents as $matchup)
        {
            //Ignore certain events (e.g. non-MMA)
            if (isset($matchup->scheduleText) 
                && (substr(trim((string) $matchup->scheduleText),0,4) == 'BKFC'
                || substr(trim((string) $matchup->scheduleText),0,9) == 'Fight2Win'))
            {
                $this->logger->info('Skipping matchup for event ' . $matchup->scheduleText);
            }
            else
            {
                $this->parseMatchup($matchup);
            }

            
        }
    }

    private function parseMatchup($matchup)
    {
        //Check for metadata
        if (!isset($matchup->gameId, $matchup->event_DateTimeGMT))
        {
            $this->logger->warning('Missing metadata (game ID and/or DateTimeGMT) for matchup');
            return false;
        }
        $event_name = $matchup->scheduleText == null ? '' : trim((string) $matchup->scheduleText);

        $gd = new DateTime($matchup->event_DateTimeGMT);
        $event_timestamp = $gd->getTimestamp();

        //Validate existance participants fields and odds
        if (!@isset($matchup->participants[0]->participantName,
                    $matchup->participants[1]->odds->moneyLine,
                    $matchup->participants[0]->participantName,
                    $matchup->participants[1]->odds->moneyLine))
        {
            $this->logger->warning('Missing participant and odds fields for matchup ' + trim((string) $matchup->gameId) + ' at ' + $event_name);
            return false;
        }

        //Validate format of participants and odds
        $team_1 = ParseTools::formatName((string) $matchup->participants[0]->participantName);
        $team_2 = ParseTools::formatName((string) $matchup->participants[1]->participantName);
        if (!OddsTools::checkCorrectOdds((string) $matchup->participants[0]->odds->moneyLine) || 
            !OddsTools::checkCorrectOdds((string) $matchup->participants[1]->odds->moneyLine) ||
            $team_1 == '' ||
            $team_2 == '')
        {
            $this->logger->warning('Invalid formatting for participant and odds fields for matchup ' + trim((string) $matchup->gameId) + ' at ' + $event_name);
            return false;
        }

        //All ok, add matchup

        //Logic to determine correlation ID: We check who is home and visiting team and construct this into a string that matches what can be found in the prop parser:
        $corr_index = trim((string) strtolower($matchup->participants[0]->visitingHomeDraw)) == 'home' ? 0 : 1;
        $correlation_id = strtolower($matchup->participants[$corr_index]->participantName . ' vs ' . $matchup->participants[!$corr_index]->participantName);

        $parsed_matchup = new ParsedMatchup(
            $team_1,
            $team_2,
            (string) $matchup->participants[0]->odds->moneyLine,
            (string) $matchup->participants[1]->odds->moneyLine
        );
        if (!empty($event_name))
        {
            $parsed_matchup->setMetaData('event_name', $event_name);
        }
        $parsed_matchup->setMetaData('gametime', $event_timestamp);
        $parsed_matchup->setCorrelationID($correlation_id);
        $this->parsed_sport->addParsedMatchup($parsed_matchup);

        //If existant, also add total rounds (e.g. over/under 4.5 rounds)
        if (@!empty($matchup->period->total->totalPoints) && @!empty($matchup->period->total->overAdjust) && @!empty($matchup->period->total->underAdjust)
            && OddsTools::checkCorrectOdds((string) $matchup->period->total->overAdjust) && OddsTools::checkCorrectOdds((string) $matchup->period->total->underAdjust))
        {
            $parsed_prop = new ParsedProp(
                $team_1 . ' VS ' . $team_2 . ' OVER ' . $matchup->period->total->totalPoints . ' ROUNDS',
                $team_1 . ' VS ' . $team_2 . ' UNDER ' . $matchup->period->total->totalPoints . ' ROUNDS',
                (string) $matchup->period->total->overAdjust,
                (string) $matchup->period->total->underAdjust);
            $parsed_prop->setCorrelationID($correlation_id);
            $this->parsed_sport->addFetchedProp($parsed_prop);
        } 
            
        return true;
    }

    private function parseProps($content) 
    {
        $json = json_decode($content);
        if ($json == false)
        {
            $this->logger->error("Unable to decode JSON: " . substr($content, 0,50) . "...");
            return false;
        }

        foreach ($json->events as $prop)
        {
            if (trim((string) $prop->sport) == "MMA Props")
            {
                $this->parseProp($prop);
            }
        }
    }

    private function parseProp($prop)
    {
        $correlation_id = trim(strtolower((string) $prop->league));

        if (count($prop->participants) == 2 
            && (trim(strtolower((string) $prop->participants[0]->name)) == 'yes' && trim(strtolower((string) $prop->participants[1]->name)) == 'no') 
            || (trim(strtolower((string) $prop->participants[0]->name)) == 'no' && trim(strtolower((string) $prop->participants[1]->name)) == 'yes'))
        {
            //Validate existance participants fields and odds
            if (!@isset($prop->participants[0]->name,
                        $prop->participants[0]->odds->moneyLine,
                        $prop->participants[1]->name,
                        $prop->participants[1]->odds->moneyLine)
                        || !OddsTools::checkCorrectOdds($prop->participants[0]->odds->moneyLine)
                        || !OddsTools::checkCorrectOdds($prop->participants[1]->odds->moneyLine))
            {
                $this->logger->warning('Missing/invalid options and odds fields for prop ' + trim((string) $prop->description) + ' at ' + $prop->league);
                return false;
            }

            //Two way prop
            $prop_obj = new ParsedProp(
                trim((string) $prop->league) . ' : ' . trim((string) $prop->description) . ' - ' . trim((string) $prop->participants[0]->name),
                trim((string) $prop->league) . ' : ' . trim((string) $prop->description) . ' - ' . trim((string) $prop->participants[1]->name),
                trim((string) $prop->participants[0]->odds->moneyLine),
                trim((string) $prop->participants[1]->odds->moneyLine) 
            );
            $prop_obj->setCorrelationID($correlation_id);
            $this->parsed_sport->addFetchedProp($prop_obj);
        }
        else
        {
            //Multiple one way props
            foreach ($prop->participants as $prop_line)
            {
                //Validate existance participants fields and odds
                if (!@isset($prop_line->name,
                            $prop_line->odds->moneyLine) 
                            || !OddsTools::checkCorrectOdds($prop_line->odds->moneyLine))
                {
                    $this->logger->warning('Missing/invalid options and odds fields for prop ' + trim((string) $prop->description) + ' at ' + $prop->league);
                }
                else
                {
                    $prop_obj = new ParsedProp(
                        trim((string) $prop->league) . ' : ' . trim((string) $prop->description) . ' - ' . trim((string) $prop_line->name),
                        '',
                        trim((string) $prop_line->odds->moneyLine),
                        '-99999'
                    );
                    $prop_obj->setCorrelationID($correlation_id);
                    $this->parsed_sport->addFetchedProp($prop_obj);
                }
            }
        }

        return true;
    }
}

?>