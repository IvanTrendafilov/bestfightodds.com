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
 */

require_once 'config/inc.config.php';
require_once 'vendor/autoload.php';
require_once 'lib/bfocore/parser/general/inc.ParserMain.php';

use Respect\Validation\Validator as v;

define('BOOKIE_NAME', 'bookmaker');
define('BOOKIE_ID', '3');

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
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "bookmaker.xml");
            $content = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'bookmaker.xml');
        }
        else
        {
            $matchups_url = 'https://lines.bookmaker.eu/';
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

    private function parseContent($source)
    {
        $this->parsed_sport = new ParsedSport('MMA');

        //Store as latest feed available for ProBoxingOdds.com
        $file = fopen(GENERAL_BASEDIR . '/app/front/externalfeeds/bookmaker-latest.xml', 'w');
        fwrite($file, $source);
        fclose($file);

        $xml = simplexml_load_string($source);
        if ($xml == false)
        {
            $this->logger->warning("Warning: XML broke!!");
        }

        foreach ($xml->Leagues->league as $cLeague)
        {
            //Matchups (also contains some props):
            if (substr(trim((string) $cLeague['Description']), 0, 3) == 'MMA')
            {
                foreach ($cLeague->game as $cGame)
                {
                    $skip = false;
                    //Fetch banner for this game using xpath expression. This is done to check if this is a live bet or not. Note that an banner may not always be returned and instead a game is returned
                    $cBanner = $cGame->xpath('preceding::*[1]');
                    //Check if live betting, if so it should be skipped
                    if (substr($cBanner[0]['vtm'], 0, 21) == 'LIVE IN FIGHT BETTING')
                    {
                        $skip = true;
                    }

                    $cLine = $cGame->line;

                    if ($skip != true && ParseTools::checkCorrectOdds((string) $cLine['voddst']) && ParseTools::checkCorrectOdds((string) $cLine['hoddst']))
                    {
                        //Check if bet is a prop or not
                        if (ParseTools::isProp((string) $cGame['vtm']) && ParseTools::isProp((string) $cGame['htm']))
                        {
                            //Prop, add as such
                            $this->parsed_sport->addFetchedProp(new ParsedProp(
                                            (string) $cGame['vtm'],
                                            (string) $cGame['htm'],
                                            (string) $cLine['voddst'],
                                            (string) $cLine['hoddst']
                            ));
                        }
                        else
                        {
                            //Not a prop, add as matchup
                            $new_matchup = new ParsedMatchup(
                                            (string) $cGame['vtm'],
                                            (string) $cGame['htm'],
                                            (string) $cLine['voddst'],
                                            (string) $cLine['hoddst']
                            );

                            //Add game time metadata
                            $date_obj = new DateTime((string) $cGame['gmdt'] . ' ' . $cGame['gmtm']);
                            $new_matchup->setMetaData('gametime', $date_obj->getTimestamp());
                            $new_matchup->setMetaData('event_name', $cBanner[0]['vtm']);

                            $this->parsed_sport->addParsedMatchup($new_matchup);

                            //Check if a total is available, if so, add it as a prop. line[0] is always over and line[1] always under
                            if (isset($cLine['unt']) && 
                                isset($cLine['ovoddst']) && 
                                isset($cLine['unoddst']) && 
                                trim((string) $cLine['ovoddst']) != '' && 
                                trim((string) $cLine['unoddst']) != '')
                            {
                                //Total exists, add it
                                $this->parsed_sport->addFetchedProp(new ParsedProp(
                                            (string) $cGame['vtm'] . ' vs ' . (string) $cGame['htm'] . ' - OVER ' . (string) $cLine['unt'],
                                            (string) $cGame['vtm'] . ' vs ' . (string) $cGame['htm'] . ' - UNDER ' . (string) $cLine['unt'],
                                            (string) $cLine['ovoddst'],
                                            (string) $cLine['unoddst'])
                                );
                            }
                        }
                    }
                }
            }
            //Props:
            else if (substr(trim((string) $cLeague['Description']), 0, 18) == 'MARTIAL ARTS PROPS')
            {
                foreach ($cLeague->game as $cGame)
                {
                    //Check if prop is a Yes/No prop, if so we add both sides as options
                    if (count($cGame->line == 2) && strcasecmp($cGame->line[0]['tmname'], 'Yes') == 0 && strcasecmp($cGame->line[1]['tmname'], 'No') == 0)
                    {
                        //Multi line prop (Yes/No)
                        if (ParseTools::checkCorrectOdds((string) $cGame->line[0]['odds']) && ParseTools::checkCorrectOdds((string) $cGame->line[1]['odds']))
                        {
                            $this->parsed_sport->addFetchedProp(new ParsedProp(
                                            str_replace(' VS.', ' VS. ', (string) (string) trim($cGame['htm'], " -") . ' ' . $cGame->line[0]['tmname']),
                                            str_replace(' VS.', ' VS. ', (string) (string) trim($cGame['htm'], " -") . ' ' . $cGame->line[1]['tmname']),
                                            (string) $cGame->line[0]['odds'],
                                            (string) $cGame->line[1]['odds']
                            ));
                        }
                    }
                    else
                    {
                        //Single line props
                        foreach ($cGame->line as $cLine)
                        {
                            if (ParseTools::checkCorrectOdds((string) $cLine['odds']))
                            {
                                $this->parsed_sport->addFetchedProp(new ParsedProp(
                                                str_replace(' VS.', ' VS. ', (string) (string) trim($cGame['htm'], " -") . ' ' . $cLine['tmname']),
                                                '',
                                                (string) $cLine['odds'],
                                                '-99999'
                                ));
                            }
                        }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($this->parsed_sport->getParsedMatchups()) >= 10 && $this->parsed_sport->getPropCount() >= 2)
        {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $this->parsed_sport;
    }
}

?>