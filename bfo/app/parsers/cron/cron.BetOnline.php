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

$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::DEBUG, ['filename' => 'cron.' . BOOKIE_NAME . '.log']);
$parser = new ParserJob($logger);
$parser->run('mock');

class ParserJob
{
    private $full_run = false;
    private $parsed_sport;
    private $logger = null;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function run($mode)
    {

        $content = null;
        if ($mode == 'mock')
        {
            $this->logger->info("Note: Using mock file at " . PARSE_MOCKFEEDS_DIR . "betonline.json");
            $content = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'betonline.json');
        }
        //Fetch page(s)
        //ParseContent
        $parsed_sport = $this->parseContent($content);

        $this->full_run = true;

        $op = new OddsProcessor($this->logger, BOOKIE_ID);
        $op->processParsedSport($parsed_sport, $this->full_run);
    




    }

    private function parseContent($source)
    {
        $json = json_decode($source);
        if ($json == false)
        {
            $this->logger->warning("Warning: JSON broke!!");
        }

        $this->parsed_sport = new ParsedSport('MMA');

        foreach ($json->preGameEvents as $matchup)
        {
            $this->parseMatchup($matchup);
        }

        if (false && $json != false && count($this->parsed_sport->getParsedMatchups()) >= 5) //Currently disabled since matchup and prop are in separate parsers
        {
            $this->full_run = true;
            $this->logger->info("Declared authoritive run");
        }

        return $this->parsed_sport;
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
        $event_correlation_id = trim((string) $matchup->gameId);
        $gd = new DateTime($matchup->event_DateTimeGMT);
        $event_timestamp = $gd->getTimestamp();

        //Validate existance participants fields and odds
        if (!@isset($matchup->participants[0]->participantName,
                    $matchup->participants[1]->odds->moneyLine,
                    $matchup->participants[0]->participantName,
                    $matchup->participants[1]->odds->moneyLine))
        {
            $this->logger->warning('Missing participant and odds fields for matchup ' + $event_correlation_id + ' at ' + $event_name);
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
            $this->logger->warning('Invalid formatting for participant and odds fields for matchup ' + $event_correlation_id + ' at ' + $event_name);
            return false;
        }

        //All ok, add matchup
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
        $parsed_matchup->setCorrelationID($event_correlation_id);
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
            $parsed_prop->setCorrelationID($event_correlation_id);
            $this->parsed_sport->addFetchedProp($parsed_prop);
        } 
            
        return true;
    }

    private function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->full_run;
    }
}

?>