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
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "betdsi.json");
            $content = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'betdsi.json');
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
        $oParsedSport = new ParsedSport('MMA');
        $json = json_decode($content, true);

        foreach ($json as $matchup)
        {
            if (($matchup['SportType']['Name'] == 'MMA' || $matchup['Category']['Name'] == 'UFC' || $matchup['Category']['Name'] == 'LFA' || $matchup['Category']['Name'] == 'Bellator' || $matchup['Category']['Name'] == 'Invicta') && $matchup['IsLive'] == false) 
            {
                //Fixes flipped names like Gastelum K. into K Gastelum
                $matchup['HomeTeamName'] = preg_replace('/([a-zA-Z\-\s]+)\s([a-zA-Z])\./', '$2 $1', $matchup['HomeTeamName']);
                $matchup['AwayTeamName'] = preg_replace('/([a-zA-Z\-\s]+)\s([a-zA-Z])\./', '$2 $1', $matchup['AwayTeamName']);

                $oParsedMatchup = new ParsedMatchup(
                    $matchup['HomeTeamName'],
                    $matchup['AwayTeamName'],
                    OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsMoneyLine'][0]['Value']),
                    OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsMoneyLine'][1]['Value'])
                );
                $oParsedMatchup->setCorrelationID((string) $matchup['ID']);
                $oParsedSport->addParsedMatchup($oParsedMatchup);

                //Add total if available
                if (isset($matchup['PreviewOddsTotal']) && count($matchup['PreviewOddsTotal']) >= 2)
                {
                    //Loop through pairs of 1.5, 2.5, ..
                    for ($i = 0; $i < count($matchup['PreviewOddsTotal']); $i += 2)
                    {
                        if ($matchup['PreviewOddsTotal'][$i]['SpecialBetValue'] == $matchup['PreviewOddsTotal'][$i + 1]['SpecialBetValue'])
                        {
                            $oParsedProp = new ParsedProp(
                                $matchup['HomeTeamName'] . ' - ' . $matchup['AwayTeamName'] . ' : ' . $matchup['PreviewOddsTotal'][$i]['Title'] . ' rounds',
                                $matchup['HomeTeamName'] . ' - ' . $matchup['AwayTeamName'] . ' : ' . $matchup['PreviewOddsTotal'][$i + 1]['Title'] . ' rounds',
                                OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsTotal'][$i]['Value']),
                                OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsTotal'][$i + 1]['Value'])
                            );
                            //Add correlation ID
                            $oParsedProp->setCorrelationID((string) $matchup['ID']);
                            $oParsedSport->addFetchedProp($oParsedProp);
                        }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) > 10)
        {
            $this->full_run = true;
            $this->logger->info("Declared authoritive run");
        }
 
        return $oParsedSport;
    }
}

?>