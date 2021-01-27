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

require_once 'lib/bfocore/parser/general/inc.ParserMain.php';

class XMLParserBetOnline
{
    private $full_run = false;
    private $parsed_sport;

    public function parseXML($a_sJSON)
    {
        $json = json_decode($a_sJSON);
        if ($json == false)
        {
            Logger::getInstance()->log("Warning: JSON broke!!", -1);
        }

        $this->parsed_sport = new ParsedSport('MMA');

        foreach ($json->preGameEvents as $matchup)
        {
            $this->parseMatchup($matchup);
        }

        if (false && $json != false && count($this->parsed_sport->getParsedMatchups()) >= 5) //Currently disabled since matchup and prop are in separate parsers
        {
            $this->full_run = true;
            Logger::getInstance()->log("Declared authoritive run", 0);
        }

        return [$this->parsed_sport];
    }

    private function parseMatchup($matchup)
    {
        //Check for metadata
        if (!isset($matchup->gameId, $matchup->event_DateTimeGMT))
        {
            Logger::getInstance()->log('Missing metadata (game ID and/or DateTimeGMT) for matchup', -1);
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
            Logger::getInstance()->log('Missing participant and odds fields for matchup ' + $event_correlation_id + ' at ' + $event_name, -1);
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
            Logger::getInstance()->log('Invalid formatting for participant and odds fields for matchup ' + $event_correlation_id + ' at ' + $event_name, -1);
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

    public function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->full_run;
    }
}

?>