<?php

/**
 * XML Parser
 *
 * Bookie: SportBet
 * Sport: Boxing
 *
 * Moneylines: Yes
 * Props: Yes
 * 
 * URL: http://lines.sportbet.com/linesfeed/getlinefeeds.aspx?UID=bestfightodds5841
 *
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\General\BookieHandler;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;
use BFO\Parser\Jobs\ParserJobBase;

define('BOOKIE_NAME', 'sportbet');
define('BOOKIE_ID', 2);
define(
    'BOOKIE_URLS',
    ['all' => 'http://lines.sportbet.com/linesfeed/getlinefeeds.aspx?UID=bestfightodds5841']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "sportbet.xml"]
);

class ParserJob extends ParserJobBase
{
    public function fetchContent($urls): array
    {
        //Apply changenum
        $this->change_num = BookieHandler::getChangeNum($this->bookie_id);
        if ($this->change_num != -1) {
            $this->logger->info("Using changenum: &changenum=" . $this->change_num);
            $urls['all'] .= '&changenum=' . $this->change_num;
        }
        $this->logger->info("Fetching matchups through URL: " . $urls['all']);
        return ['all' => ParseTools::retrievePageFromURL($urls['all'])];
    }

    public function parseContent(array $content): ParsedSport
    {
        $xml = simplexml_load_string($content['all']);
        if (!$xml) {
            $this->logger->warning("Warning: XML broke!!");
        }

        $parsed_sport = new ParsedSport('Boxing');

        foreach ($xml->NewDataSet->GameLines as $event_node) {
            if ((trim((string) $event_node->SportType) == 'Fighting'
                    && in_array(
                        trim((string) $event_node->SportSubType),
                        [
                            'Boxing',
                            'Olympic Boxing',
                            'Boxing Props'
                        ]
                    )
                    && ((int) $event_node->IsCancelled) != 1
                    && ((int) $event_node->isGraded) != 1)
                && !((trim((string) $event_node->HomeMoneyLine) == '-99999') && (trim((string) $event_node->VisitorMoneyLine) == '-99999'))
                && !strpos(strtolower((string)$event_node->Header), 'mma propositions')
            ) {
                $correlation_id = '';
                if (!empty((string) $event_node->CorrelationId)) {
                    $correlation_id = trim((string) $event_node->CorrelationId);
                }

                //Check if entry is a prop, if so add it as a parsed prop
                if (trim((string) $event_node->SportSubType) == 'Props' || trim((string) $event_node->SportSubType) == 'Boxing Props') {
                    $prop = null;

                    if (!empty(trim((string) $event_node->HomeMoneyLine)) && !empty(trim((string) $event_node->VisitorMoneyLine))) {
                        //Regular prop

                        //Workaround for props that are not sent in the correct order:
                        if (strtoupper(substr(trim((string) $event_node->HomeTeamID), 0, 4)) == 'NOT ' || strtoupper(substr(trim((string) $event_node->HomeTeamID), 0, 4)) == 'ANY ') {
                            //Prop starts with NOT, switch home and visitor fields
                            $prop = new ParsedProp(
                                (string) ':: ' . $event_node->Header . ' : ' . $event_node->VisitorTeamID,
                                (string) ':: ' . $event_node->Header . ' : ' . $event_node->HomeTeamID,
                                (string) $event_node->VisitorMoneyLine,
                                (string) $event_node->HomeMoneyLine
                            );
                        } else {
                            $prop = new ParsedProp(
                                (string) ':: ' . $event_node->Header . ' : ' . $event_node->HomeTeamID,
                                (string) ':: ' . $event_node->Header . ' : ' . $event_node->VisitorTeamID,
                                (string) $event_node->HomeMoneyLine,
                                (string) $event_node->VisitorMoneyLine
                            );
                        }

                        //Add correlation ID if available
                        if ($correlation_id != '') {
                            $prop->setCorrelationID($correlation_id);
                        }

                        $parsed_sport->addFetchedProp($prop);
                    } else if ((trim((string) $event_node->HomeSpreadPrice) != '')
                        && (trim((string) $event_node->VisitorSpreadPrice) != '')
                        && (trim((string) $event_node->HomeSpread) != '')
                        && (trim((string) $event_node->VisitorSpread) != '')
                    ) {

                        //One combined:
                        $prop = new ParsedProp(
                            (string) $event_node->HomeTeamID . ' ' . (string) $event_node->HomeSpread,
                            (string) $event_node->VisitorTeamID . ' ' . (string) $event_node->VisitorSpread,
                            (string) $event_node->HomeSpreadPrice,
                            (string) $event_node->VisitorSpreadPrice
                        );
                        if ($correlation_id != '') {
                            $prop->setCorrelationID($correlation_id);
                        }

                        $parsed_sport->addFetchedProp($prop);
                    } else if (!empty($event_node->TotalPoints) && !empty($event_node->TotalPointsOverPrice) && !empty($event_node->TotalPointsUnderPrice)) {
                        //Custom totals prop bet
                        $prop = new ParsedProp(
                            (string) $event_node->HomeTeamID . ' - OVER ' . (string) $event_node->TotalPoints,
                            (string) $event_node->VisitorTeamID . ' - UNDER ' . (string) $event_node->TotalPoints,
                            (string) $event_node->TotalPointsOverPrice,
                            (string) $event_node->TotalPointsUnderPrice
                        );
                        $prop->setCorrelationID($correlation_id);
                        $parsed_sport->addFetchedProp($prop);
                    } else {
                        //Unhandled prop
                        $this->logger->warning("Unhandled prop: " . (string) $event_node->HomeTeamID . " / " . (string) $event_node->VisitorTeamID . ", check parser");
                    }

                    $prop = null;
                }
                //Entry is a regular matchup, add as one
                else {
                    if ((trim((string) $event_node->HomeMoneyLine) != '')
                        && (trim((string) $event_node->VisitorMoneyLine) != '')
                        && !preg_match("/ DECISION/", strtoupper($event_node->HomeTeamID))
                        && !preg_match("/ DRAW/", strtoupper($event_node->HomeTeamID))
                        && !preg_match("/ DISTANCE/", strtoupper($event_node->HomeTeamID))
                    ) {
                        $parsed_matchup = new ParsedMatchup(
                            (string) $event_node->HomeTeamID,
                            (string) $event_node->VisitorTeamID,
                            (string) $event_node->HomeMoneyLine,
                            (string) $event_node->VisitorMoneyLine
                        );

                        //Add correlation ID to match matchups to props
                        $parsed_matchup->setCorrelationID($correlation_id);

                        //Add time of matchup as metadata
                        if (isset($event_node->GameDateTime)) {
                            $oGameDate = new DateTime($event_node->GameDateTime);
                            $parsed_matchup->setMetaData('gametime', $oGameDate->getTimestamp());
                        }

                        //Add header of matchup as metadata
                        if (isset($event_node->Header)) {
                            $parsed_matchup->setMetaData('event_name', (string) $event_node->Header);
                        }

                        $parsed_sport->addParsedMatchup($parsed_matchup);
                    }

                    //Check if a total is available, if so, add it as a prop
                    if (isset($event_node->TotalPoints) && trim((string) $event_node->TotalPoints) != '') {
                        //Total exists, add it
                        $prop = new ParsedProp(
                            (string) $event_node->HomeTeamID . ' vs ' . (string) $event_node->VisitorTeamID . ' - OVER ' . (string) $event_node->TotalPoints,
                            (string) $event_node->HomeTeamID . ' vs ' . (string) $event_node->VisitorTeamID . ' - UNDER ' . (string) $event_node->TotalPoints,
                            (string) $event_node->TotalPointsOverPrice,
                            (string) $event_node->TotalPointsUnderPrice
                        );
                        $prop->setCorrelationID($correlation_id);

                        $parsed_sport->addFetchedProp($prop);
                    }
                }
            }
        }

        //Declare full run if we fill the criteria
        if (count($parsed_sport->getParsedMatchups()) > 10 && $parsed_sport->getPropCount() > 10 && $this->change_num == -1) {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        //Before finishing up, save the changenum to be able to fetch future feeds
        $new_changenum = trim((string) $xml->NewDataSet->LastChange->ChangeNum);
        
        if (!empty($new_changenum) && $new_changenum != '-1') {
            //Store the changenum
            $new_changenum = ((float) $new_changenum) - 1000;
            if (BookieHandler::saveChangeNum($this->bookie_id, $new_changenum)) {
                $this->logger->info("ChangeNum stored OK: " . $new_changenum);
            } else {
                $this->logger->error("ChangeNum was not stored");
            }
        } else {
            $this->logger->error("Bad ChangeNum in feed. Message: " . $xml->Error->ErrorMessage);
        }

        return $parsed_sport;
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
