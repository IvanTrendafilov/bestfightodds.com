<?php

/**
 * XML Parser
 *
 * Bookie: 5Dimes
 * Sport: MMA
 *
 * Moneylines: Yes
 * Props: Yes
 * 
 * URL: http://lines.5dimes.com/linesfeed/getlinefeeds.aspx?uid=bestfightodds5841&Type=ReducedReplace
 *
 * Timezone: EDT (UTC -4) but including timezone offset so properly converted
 * 
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\General\BookieHandler;
use BFO\Parser\OddsProcessor;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;
use BFO\Parser\Jobs\ParserJobBase;
use BFO\Parser\Jobs\ParserJobInterface;

define('BOOKIE_NAME', '5dimes');
define('BOOKIE_ID', '1');

$options = getopt("", ["mode::"]);

$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob($logger);
$parser->run($options['mode'] ?? '');

class ParserJob
{
    private $full_run = false;
    private $parsed_sport;
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
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "5dimes.xml");
            $content = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . '5dimes.xml');
        } else {
            $matchups_url = 'http://lines.5dimes.com/linesfeed/getlinefeeds.aspx?uid=bestfightodds5841&Type=ReducedReplace';
            $this->change_num = BookieHandler::getChangeNum(BOOKIE_ID);
            if ($this->change_num != -1) {
                $this->logger->info("Using changenum: &changenum=" . $this->change_num);
                $matchups_url .= '&changenum=' . $this->change_num;
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

    public function parseContent(string $content): ParsedSport
    {
        $xml = simplexml_load_string($content);
        if (!$xml) {
            $this->logger->warning("Warning: XML broke!!");
        }

        $parsed_sport = new ParsedSport('MMA');

        foreach ($xml->NewDataSet->GameLines as $event_node) {
            if ((trim((string) $event_node->SportType) == 'Fighting'
                    && (trim((string) $event_node->SportSubType) != 'Boxing')
                    && (trim((string) $event_node->SportSubType) != 'Reduced')
                    && (trim((string) $event_node->SportSubType) != 'Live In-Play')
                    && (trim((string) $event_node->SportSubType) != 'Olympic Boxing')
                    && (trim((string) $event_node->SportSubType) != 'Kickboxing')
                    && (trim((string) $event_node->SportSubType) != 'Boxing Props')
                    && ((int) $event_node->IsCancelled) != 1
                    && ((int) $event_node->isGraded) != 1)
                && !((trim((string) $event_node->HomeMoneyLine) == '-99999') && (trim((string) $event_node->VisitorMoneyLine) == '-99999'))
                && !strpos(strtolower((string)$event_node->Header), 'boxing propositions')
            ) {

                //Check if entry is a prop, if so add it as a parsed prop
                if (trim((string) $event_node->SportSubType) == 'Props' || trim((string) $event_node->SportSubType) == 'MMA Props') {
                    //Temporary fix for UFC event name
                    $event_node->Header = str_replace('UFC Vegas 26', 'UFC ON ESPN 24', $event_node->Header);

                    $prop = null;

                    if ((trim((string) $event_node->HomeMoneyLine) != '')
                        && (trim((string) $event_node->VisitorMoneyLine) != '')
                    ) {
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
                        if (isset($event_node->CorrelationID) && trim((string) $event_node->CorrelationID) != '') {
                            $prop->setCorrelationID((string) $event_node->CorrelationID);
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

                        //Add correlation ID if available
                        if (isset($event_node->CorrelationID) && trim((string) $event_node->CorrelationID) != '') {
                            $prop->setCorrelationID((string) $event_node->CorrelationID);
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
                        $prop->setCorrelationID((string) $event_node->CorrelationID);
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
                    ) {
                        $oParsedMatchup = new ParsedMatchup(
                            (string) $event_node->HomeTeamID,
                            (string) $event_node->VisitorTeamID,
                            (string) $event_node->HomeMoneyLine,
                            (string) $event_node->VisitorMoneyLine
                        );

                        //Add correlation ID to match matchups to props
                        $oParsedMatchup->setCorrelationID((string) $event_node->CorrelationID);

                        //Add time of matchup as metadata
                        if (isset($event_node->GameDateTime)) {
                            $oGameDate = new DateTime($event_node->GameDateTime);
                            $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                        }

                        //Add header of matchup as metadata
                        if (isset($event_node->Header)) {
                            $oParsedMatchup->setMetaData('event_name', (string) $event_node->Header);
                        }

                        $parsed_sport->addParsedMatchup($oParsedMatchup);
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
                        $prop->setCorrelationID((string) $event_node->CorrelationID);

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
        if ($new_changenum != '-1' && $new_changenum != null && $new_changenum != '') {
            //Store the changenum
            $new_changenum = ((float) $new_changenum) - 1000;
            if (BookieHandler::saveChangeNum(BOOKIE_ID, $new_changenum)) {
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
