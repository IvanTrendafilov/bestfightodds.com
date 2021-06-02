<?php
/**
 * XML Parser
 *
 * Bookie: Intertops
 * Sport: Boxing
 *
 * Moneylines: Yes
 * Props: Yes
 * 
 * URL: http://xmlfeed.intertops.com/xmloddsfeed/v2/xml/?apikey=860879d0-f4b6-e511-a090-003048dd52d5&sportId=6&includeCent=true
 *
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\Utils\OddsTools;
use BFO\Parser\OddsProcessor;
use BFO\General\BookieHandler;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;

define('BOOKIE_NAME', 'intertops');
define('BOOKIE_ID', '16');

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
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "intertops.xml");
            $content = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'intertops.xml');
        }
        else
        {
            $matchups_url = 'http://xmlfeed.intertops.com/xmloddsfeed/v2/xml/?apikey=860879d0-f4b6-e511-a090-003048dd52d5&sportId=6&includeCent=true';
            $this->change_num = BookieHandler::getChangeNum(BOOKIE_ID);
            if ($this->change_num != -1)
            {
                $this->logger->info("Using changenum: &delta=" . $this->change_num);
                $matchups_url .= '&delta=' . $this->change_num;
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

        $aSports = array();

        $oParsedSport = new ParsedSport('Boxing');

        foreach ($oXML->data->s->cat as $cCategory)
        {
            if ($cCategory['n'] == 'Boxing' 
            || substr($cCategory['n'], 0, strlen('Boxing')) === 'Boxing')
            {
                foreach ($cCategory->m as $cMatchup)
                {
                    foreach ($cMatchup->t as $cBet)
                    {
                        if ($cBet['n'] == 'Moving Line')
                        {
                            //Regular matchup line
                            if (OddsTools::checkCorrectOdds((string) $cBet->l[0]['c']) && OddsTools::checkCorrectOdds((string) $cBet->l[1]['c']))
                            {
                                $oTempMatchup = new ParsedMatchup(
                                    (string) $cBet->l[0],
                                    (string) $cBet->l[1],
                                    (string) $cBet->l[0]['c'],
                                    (string) $cBet->l[1]['c']
                                );

                                $oGameDate = new DateTime($cMatchup['dt']);
                                $oTempMatchup->setMetaData('gametime', $oGameDate->getTimestamp());

                                //Add correlation ID to match matchups to props
                                $oTempMatchup->setCorrelationID((string) $cMatchup['mid']);

                                $oParsedSport->addParsedMatchup($oTempMatchup);
                            }
                        }
                        else if ($cBet['n'] == 'Point Score')
                        {
                            //Point score (totalt rounds)
                            if (OddsTools::checkCorrectOdds((string) $cBet->l[0]['c']) && OddsTools::checkCorrectOdds((string) $cBet->l[1]['c']))
                            {
                                    $oTempProp = new ParsedProp(
                                                    (string) $cMatchup['n'] . ' : ' . $cBet->l[0],
                                                    (string) $cMatchup['n'] . ' : ' . $cBet->l[1],
                                                    (string) $cBet->l[0]['c'],
                                                    (string) $cBet->l[1]['c']
                                    );

                                    //Add correlation ID to match matchups to props
                                    $oTempProp->setCorrelationID((string) $cMatchup['mid']);

                                    $oParsedSport->addFetchedProp($oTempProp);
                            }
                        }
                        else if ($cBet['n'] == 'FreeForm')
                        {
                            //Any other one line prop
                            foreach ($cBet->l as $cLine)
                            {
                                if (OddsTools::checkCorrectOdds((string) $cLine['c']))
                                {
                                        $oTempProp = new ParsedProp(
                                                        (string) $cMatchup['n'] . ' : ' . $cLine,
                                                        '',
                                                        (string) $cLine['c'],
                                                        '-99999'
                                        );

                                        //Add correlation ID to match matchups to props
                                        $oTempProp->setCorrelationID((string) $cMatchup['mid']);

                                        $oParsedSport->addFetchedProp($oTempProp);
                                }
                            }
                        }
                        else
                        {
                            $this->logger->warning("Unhandled category: " . $cBet['n']);
                        }
                    }
                }
            }
        }
        //Declare authorative run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) >= 5 && $this->change_num == '525600')
        {
            $this->full_run = true;
            $this->logger->info("Declared authoritive run");
        }

        //Before finishing up, save the changenum 30 to limit not fetching the entire feed
        if (BookieHandler::saveChangeNum(BOOKIE_ID, '30'))
        {
            $this->logger->info("ChangeNum stored OK: 30");
        }
        else
        {
            $this->logger->error("Error: ChangeNum was not stored");
        }

        return $oParsedSport;
    }
}

?>