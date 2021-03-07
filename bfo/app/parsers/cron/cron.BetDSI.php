<?php
/**
 * XML Parser
 *
 * Bookie: BetDSI
 * Sport: MMA
 *
 * Moneylines: Yes
 * Props: Yes
 *
 * URL: https://modern.betdsi.eu/api/sportmatch/get?sportID=2359
 *
 */

require_once 'config/inc.config.php';
require_once 'vendor/autoload.php';
require_once 'lib/bfocore/parser/general/inc.ParserMain.php';

use Respect\Validation\Validator as v;

define('BOOKIE_NAME', 'betdsi');
define('BOOKIE_ID', '13');

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

        $content = null;
        if ($mode == 'mock')
        {
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "betdsi.xml");
            $content = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'betdsi.xml');
        }
        else
        {
            $matchups_url = 'https://modern.betdsi.eu/api/sportmatch/get?sportID=2359';
            $this->logger->info("Fetching matchups through URL: " . $matchups_url);
            $content = ParseTools::retrievePageFromURL($matchups_url);
        }

        $parsed_sport = $this->parseContent($content);

        try 
        {
            $op = new OddsProcessor($this->logger, BOOKIE_ID);
            $op->processParsedSport($parsed_sport, $this->full_run);
        }
        catch (Exception $e)
        {
            $this->logger->error('Exception: ' . $e->getMessage());
        }

        $this->logger->info('Finished');
    }

    public function parseContent($content)
    {
        $oXML = simplexml_load_string($content);
        if ($oXML == false)
        {
            $this->logger->warning("Warning: XML broke!!");
        }

        //Custom sort function to arrange bet nodes
        function odd_node_sort($a, $b)
        {
            if ((int) $a['Name'] == (int) $b['Name']) return 0;
            return ((int)$a['Name'] < (int)$b['Name'])?-1:1;
        }

        $oParsedSport = new ParsedSport('MMA');

        $feedtime = new DateTime(trim($oXML->Date));
        $nowtime = new DateTime('now');
        $interval = date_diff($feedtime, $nowtime);
        $this->logger->info("Feed date: " . trim($oXML->Date) . " , " . $interval->days . " old");

        if (trim($oXML->Date) == 'Fri May  1 13:50:02 CST 2020' || trim($oXML->Date) == 'Fri May  8 04:25:01 CST 2020' || trim($oXML->Date) == 'Sat May 30 15:40:01 CST 2020')
        {
            $this->logger->warning("Old feed detected. (" . trim($oXML->Date) . ") Bailing");
            return $oParsedSport;
        }

        //Store as latest feed available for ProBoxingOdds.com
        $file = fopen(GENERAL_BASEDIR . '/app/front/externalfeeds/betdsi-latest.xml', 'w');
        fwrite($file, $content);
        fclose($file);
        
        foreach ($oXML->Sport as $sport_node)
        {
            if ($sport_node['Name'] == 'MMA')
            {
                foreach ($sport_node->Event as $event_node)
                {
                    foreach ($event_node->Match as $match_node)
                    {
                        if ($match_node['MatchType'] != 'Live')
                        {

                            $competitors = explode(' - ', $match_node['Name']);
                            $odds = [];
                            foreach ($match_node->Bet as $bet_node)
                            {
                                if ($bet_node['Name'] == 'Bout Odds' || $bet_node['Name'] == 'Match Winner')
                                {
                                    //Sort Odd nodes
                                    $bet_node->Odd = usort($bet_node->Odd,"odd_node_sort");

                                    //Regular matchup odds
                                    foreach($bet_node->Odd as $odd_node)
                                    {
                                        $odds[((int) $odd_node['Name']) - 1] = OddsTools::convertDecimalToMoneyline($odd_node['Value']);
                                    }
                                    if (ParseTools::checkCorrectOdds((string) $odds[0]) && ParseTools::checkCorrectOdds((string) $odds[1]))
                                    {
                                        $oParsedMatchup = new ParsedMatchup(
                                            (string) $competitors[0],
                                            (string) $competitors[1],
                                            (string) $odds[0],
                                            (string) $odds[1]
                                        );
                                        $oParsedMatchup->setCorrelationID((string) $match_node['Name']);

                                        //Add time of matchup as metadata
                                        if (isset($match_node['StartDate']))
                                        {
                                            $oGameDate = new DateTime($match_node['StartDate']);
                                            $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                                        }

                                        //Add header of matchup as metadata
                                        if (isset($event_node['Name']))
                                        {
                                            $oParsedMatchup->setMetaData('event_name', (string) $event_node['Name']);
                                        }

                                        $oParsedSport->addParsedMatchup($oParsedMatchup);    
                                    }
                                }
                                else
                                {
                                    //Prop bet
                                    if (count($bet_node->Odd) == 2)
                                    {
                                        //Probably two way bet (e.g. Yes/No, Over/Under)
                                        $oParsedProp = new ParsedProp(
                                            (string) $competitors[0] . ' vs ' . $competitors[1] . ' :: ' . $bet_node->Odd[0]['Name'] . (isset($bet_node->Odd[0]['SpecialBetValue']) ? ' ' . $bet_node->Odd[0]['SpecialBetValue'] : ''),
                                            (string) $competitors[0] . ' vs ' . $competitors[1] . ' :: ' . $bet_node->Odd[1]['Name'] . (isset($bet_node->Odd[1]['SpecialBetValue']) ? ' ' . $bet_node->Odd[1]['SpecialBetValue'] : ''),
                                            OddsTools::convertDecimalToMoneyline($bet_node->Odd[0]['Value']),
                                            OddsTools::convertDecimalToMoneyline($bet_node->Odd[1]['Value'])
                                        );
                                        $oParsedProp->setCorrelationID((string) $match_node['Name']);
                                        $oParsedSport->addFetchedProp($oParsedProp);
                                    }
                                    else
                                    {
                                        foreach ($bet_node->Odd as $odd_node)
                                        {
                                            $oParsedProp = new ParsedProp(
                                                (string) $competitors[0] . ' vs ' . $competitors[1] . ' :: ' . $odd_node['Name'],
                                                '',
                                                OddsTools::convertDecimalToMoneyline($odd_node['Value']),
                                                '-99999');
                                            $oParsedProp->setCorrelationID((string) $match_node['Name']);
                                            $oParsedSport->addFetchedProp($oParsedProp);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //Declare full run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) >= 5 && $oParsedSport->getPropCount() >= 2)
        {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $oParsedSport;
    }
}

?>