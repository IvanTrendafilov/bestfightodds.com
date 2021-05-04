<?php
/**
 * XML Parser
 *
 * Bookie: SportBet
 * Sport: MMA
 *
 * Moneylines: Yes
 * Props: Yes
 * 
 * URL: http://lines.sportbet.com/linesfeed/getlinefeeds.aspx?UID=bestfightodds5841
 *
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\General\BookieHandler;
use BFO\Parser\Utils\ParseTools;
use BFO\Parser\OddsProcessor;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;

define('BOOKIE_NAME', 'sportbet');
define('BOOKIE_ID', '2');

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
        if ($mode == 'mock')
        {
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "sportbet.xml");
            $content = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'sportbet.xml');
        }
        else
        {
            $matchups_url = 'http://lines.sportbet.com/linesfeed/getlinefeeds.aspx?UID=bestfightodds5841';
            $this->change_num = BookieHandler::getChangeNum(BOOKIE_ID);
            if ($this->change_num != -1)
            {
                $this->logger->info("Using changenum: &changenum=" . $this->change_num);
                $matchups_url .= '&changenum=' . $this->change_num;
            }
            $this->logger->info("Fetching matchups through URL: " . $matchups_url);
            $content = ParseTools::retrievePageFromURL($matchups_url);
        }

        $parsed_sport = $this->parseContent($content);

        try 
        {
            $op = new OddsProcessor($this->logger, BOOKIE_ID, new Ruleset());
            $op->processParsedSport($parsed_sport, $this->full_run);
        }
        catch (Exception $e)
        {
            $this->logger->error('Exception: ' . $e->getMessage());
        }

        $this->logger->info('Finished');
    }

    public function parseContent($a_sXML)
    {
        $oXML = simplexml_load_string($a_sXML);

        if ($oXML == false)
        {
            $this->logger->warning("Warning: XML broke!!");
        }

        $oParsedSport = new ParsedSport('MMA');

        foreach ($oXML->NewDataSet->GameLines as $cEvent)
        {
            if ((trim((string) $cEvent->SportType) == 'Fighting'
                    && (trim((string) $cEvent->SportSubType) != 'Boxing')
                    && (trim((string) $cEvent->SportSubType) != 'Reduced')
                    && (trim((string) $cEvent->SportSubType) != 'Live In-Play')
                    && (trim((string) $cEvent->SportSubType) != 'Olympic Boxing')
                    && (trim((string) $cEvent->SportSubType) != 'Kickboxing')
                    && (trim((string) $cEvent->SportSubType) != 'Boxing Props')
                    && ((int) $cEvent->IsCancelled) != 1
                    && ((int) $cEvent->isGraded) != 1)
                    && !((trim((string) $cEvent->HomeMoneyLine) == '-99999') && (trim((string) $cEvent->VisitorMoneyLine) == '-99999'))
                    && !strpos(strtolower((string)$cEvent->Header), 'boxing propositions')
            )
            {

                //Check if entry is a prop, if so add it as a parsed prop
                if (trim((string) $cEvent->SportSubType) == 'Props' || trim((string) $cEvent->SportSubType) == 'MMA Props')
                {
                    $oParsedProp = null;

                    if ((trim((string) $cEvent->HomeMoneyLine) != '')
                    && (trim((string) $cEvent->VisitorMoneyLine) != ''))
                    {
                        //Regular prop

                        //Workaround for props that are not sent in the correct order:
                        if (strtoupper(substr(trim((string) $cEvent->HomeTeamID), 0, 4)) == 'NOT ' || strtoupper(substr(trim((string) $cEvent->HomeTeamID), 0, 4)) == 'ANY ')
                        {
                            //Prop starts with NOT, switch home and visitor fields
                            $oParsedProp = new ParsedProp(
                                            (string) ':: ' . $cEvent->Header . ' : ' . $cEvent->VisitorTeamID,
                                            (string) ':: ' . $cEvent->Header . ' : ' .$cEvent->HomeTeamID,
                                            (string) $cEvent->VisitorMoneyLine,
                                            (string) $cEvent->HomeMoneyLine);
                        }
                        else
                        {
                            $oParsedProp = new ParsedProp(
                                            (string) ':: ' . $cEvent->Header . ' : ' . $cEvent->HomeTeamID,
                                            (string) ':: ' . $cEvent->Header . ' : ' . $cEvent->VisitorTeamID,
                                            (string) $cEvent->HomeMoneyLine,
                                            (string) $cEvent->VisitorMoneyLine);
                        }

                        //Add correlation ID if available
                        if (isset($cEvent->CorrelationId) && trim((string) $cEvent->CorrelationId) != '')
                        {
                            $oParsedProp->setCorrelationID((string) $cEvent->CorrelationId);
                        }

                        $oParsedSport->addFetchedProp($oParsedProp);

                    }
                    else if ((trim((string) $cEvent->HomeSpreadPrice) != '')
                    && (trim((string) $cEvent->VisitorSpreadPrice) != '')
                    && (trim((string) $cEvent->HomeSpread) != '')
                    && (trim((string) $cEvent->VisitorSpread) != ''))
                    {

                        //One combined:
                        $oParsedProp = new ParsedProp(
                            (string) $cEvent->HomeTeamID . ' ' . (string) $cEvent->HomeSpread,
                            (string) $cEvent->VisitorTeamID . ' ' . (string) $cEvent->VisitorSpread,
                            (string) $cEvent->HomeSpreadPrice,
                            (string) $cEvent->VisitorSpreadPrice);

                        //Add correlation ID if available
                        if (isset($cEvent->CorrelationId) && trim((string) $cEvent->CorrelationId) != '')
                        {
                            $oParsedProp->setCorrelationID((string) $cEvent->CorrelationId);
                        }
                        $oParsedSport->addFetchedProp($oParsedProp);
                    }
                    else if (!empty($cEvent->TotalPoints) && !empty($cEvent->TotalPointsOverPrice) && !empty($cEvent->TotalPointsUnderPrice))
                    {
                        //Custom totals prop bet
                        $oParsedProp = new ParsedProp(
                                      (string) $cEvent->HomeTeamID . ' - OVER ' . (string) $cEvent->TotalPoints,
                                      (string) $cEvent->VisitorTeamID . ' - UNDER ' . (string) $cEvent->TotalPoints,
                                      (string) $cEvent->TotalPointsOverPrice,
                                      (string) $cEvent->TotalPointsUnderPrice);
                        $oParsedProp->setCorrelationID((string) $cEvent->CorrelationId);
                        $oParsedSport->addFetchedProp($oParsedProp);
                    }
                    else
                    {
                        //Unhandled prop
                        $this->logger->warning("Unhandled prop: " . (string) $cEvent->HomeTeamID . " / " . (string) $cEvent->VisitorTeamID . ", check parser");
                    }

                    $oParsedProp = null;
                    
                }
                //Entry is a regular matchup, add as one
                else
                {
                    if ((trim((string) $cEvent->HomeMoneyLine) != '')
                    && (trim((string) $cEvent->VisitorMoneyLine) != ''))
                    {
                        $oParsedMatchup = new ParsedMatchup(
                                        (string) $cEvent->HomeTeamID,
                                        (string) $cEvent->VisitorTeamID,
                                        (string) $cEvent->HomeMoneyLine,
                                        (string) $cEvent->VisitorMoneyLine
                        );

                        //Add correlation ID to match matchups to props
                        $oParsedMatchup->setCorrelationID((string) $cEvent->CorrelationId);

                        //Add time of matchup as metadata
                        if (isset($cEvent->GameDateTime))
                        {
                            $oGameDate = new DateTime($cEvent->GameDateTime);
                            $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                        }

                        //Add header of matchup as metadata
                        if (isset($cEvent->Header))
                        {
                            $oParsedMatchup->setMetaData('event_name', (string) $cEvent->Header);
                        }
                        
                        $oParsedSport->addParsedMatchup($oParsedMatchup);
                    }

                    //Check if a total is available, if so, add it as a prop
                    if ( isset($cEvent->TotalPoints) && trim((string) $cEvent->TotalPoints) != '')
                    {
                        //Total exists, add it
                        $oParsedProp = new ParsedProp(
                                        (string) $cEvent->HomeTeamID . ' vs ' . (string) $cEvent->VisitorTeamID . ' - OVER ' . (string) $cEvent->TotalPoints,
                                        (string) $cEvent->HomeTeamID . ' vs ' . (string) $cEvent->VisitorTeamID . ' - UNDER ' . (string) $cEvent->TotalPoints,
                                        (string) $cEvent->TotalPointsOverPrice,
                                        (string) $cEvent->TotalPointsUnderPrice);
                        $oParsedProp->setCorrelationID((string) $cEvent->CorrelationId);
                        
                        $oParsedSport->addFetchedProp($oParsedProp);
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) > 10 && $oParsedSport->getPropCount() > 10 && $this->change_num == -1)
        {
            $this->full_run = true;
            $this->logger->info("Declared authoritive run");
        }

        //Before finishing up, save the changenum to be able to fetch future feeds
        $new_changenum = trim((string) $oXML->NewDataSet->LastChange->ChangeNum);
        if ($new_changenum != '-1' && $new_changenum != null && $new_changenum != '')
        {
            //Store the changenum
            $new_changenum = ((float) $new_changenum) - 1000;
            if (BookieHandler::saveChangeNum(BOOKIE_ID, $new_changenum))
            {
                $this->logger->info("ChangeNum stored OK: " . $new_changenum);
            }
            else
            {
                $this->logger->error("Error: ChangeNum was not stored");
            }
        }
        else
        {
            $this->logger->error("Error: Bad ChangeNum in feed. Message: " . $oXML->Error->ErrorMessage);
        }

        return $oParsedSport;

    }
}

?>