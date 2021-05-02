<?php
/**
 * XML Parser
 *
 * Bookie: Sportsbook
 * Sport: MMA
 *
 * Moneylines: Yes
 * Props: No
 *
 * URL: https://www.gamingsystem.ag/sbk/sportsbook4/www.sportsbook.ag/getodds.xgi?categoryId=*
 *
 */

require_once __DIR__ . "/../../../bootstrap.php";
require_once __DIR__ . "/../../../config/class.Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\Parser\OddsProcessor;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use Symfony\Component\DomCrawler\Crawler;

define('BOOKIE_NAME', 'sportsbook');
define('BOOKIE_ID', '4');

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
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "sportsbook.xml");
            $content['mock'] = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'sportsbook.xml');
        }
        else
        {
            //1. UFC
            //2. MMA
            $urls = ['https://www.gamingsystem.ag/sbk/sportsbook4/www.sportsbook.ag/getodds.xgi?categoryId=92',
                    'https://www.gamingsystem.ag/sbk/sportsbook4/www.sportsbook.ag/getodds.xgi?categoryId=122'];
            ParseTools::retrieveMultiplePagesFromURLs($urls);
            foreach ($urls as $url)
            {
                $this->logger->info("Fetching matchups through URL: " . $url);
                $content[$url] = ParseTools::getStoredContentForURL($url);
            }
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

    public function parseContent($content)
    {
        $parsed_sport = new ParsedSport('MMA');
        $sTimezone = (new DateTime())->setTimezone(new DateTimeZone('America/New_York'))->format('T');
        $failed_once = false;
        foreach ($content as $url => $part)
        {
            $counter = 0;

            if ($part == '') 
            {
                $this->logger->error('Content fail for ' . $url);
                $failed_once = true;
            }

            //Clean up HTML
            $part = strip_tags($part);
            $part = str_replace("\r", " ", $part);
            $part = str_replace("\n", " ", $part);
            $part = str_replace("\t", " ", $part);
            $part = str_replace("&nbsp;", " ", $part);
            while (strpos($part, '  ') !== false)
            {
                $part = str_replace("  ", " ", $part);
            }
            $part = ParseTools::stripForeignChars($part);
        
            //Match fights in single page
            $fight_regexp = '/(\\d{2}\\/\\d{2}\\/\\d{2}) \\d{1,7} ([a-zA-Z]+[a-zA-Z0-9\\s\\-.,\']+?) ([+-]{0,1}[0-9]+|EV|even) OFF OFF ([0-9]{2}:[0-9]{2}) [A-Za-z]{2} ([a-zA-Z]+[a-zA-Z0-9\\s\\-.,\']+?) ([+-]{0,1}[0-9]+|EV|even) OFF OFF/';
    
            $fight_matches = ParseTools::matchBlock($part, $fight_regexp);
    
            foreach ($fight_matches as $fight)
            {
                if (ParseTools::checkCorrectOdds(trim((string) $fight[3]))
                    && ParseTools::checkCorrectOdds(trim((string) $fight[6])))
                {
                    $date_obj = new DateTime($fight[1] . ' ' . $fight[4] . ' ' . $sTimezone);
                    $parsed_matchup = new ParsedMatchup(
                                    (string) $fight[2],
                                    (string) $fight[5],
                                    (string) $fight[3],
                                    (string) $fight[6]
                    );
                    $parsed_matchup->setMetaData('gametime', (string) $date_obj->getTimestamp());
                    $parsed_sport->addParsedMatchup($parsed_matchup);
                }
                $counter++;
            }
    
            $this->logger->info('URL ' . $url . ' provided ' . $counter . ' matchups');
        }

        //Declare authorative run if we fill the criteria
        if (!$failed_once && count($parsed_sport->getParsedMatchups()) >= 10)
        {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $parsed_sport;
    }

    public function parseContentNew($content)
    {
        $parsed_sport = new ParsedSport('MMA');
        $sTimezone = (new DateTime())->setTimezone(new DateTimeZone('America/New_York'))->format('T');
        $failed_once = false;
        foreach ($content as $url => $part)
        {
            $counter = 0;

            if ($part == '') 
            {
                $this->logger->error('Content fail for ' . $url);
                $failed_once = true;
            }

            $crawler = new Crawler($part);

            $crawler->filter('#betOdds > tr')->each(function (\Symfony\Component\DomCrawler\Crawler $event_node) use (&$parsed_sport)
            {
               $event = $event_node->filter('td.oddsTitle');
               echo $event->text();
            });
            exit;


            //Clean up HTML
            $part = strip_tags($part);
            $part = str_replace("\r", " ", $part);
            $part = str_replace("\n", " ", $part);
            $part = str_replace("\t", " ", $part);
            $part = str_replace("&nbsp;", " ", $part);
            while (strpos($part, '  ') !== false)
            {
                $part = str_replace("  ", " ", $part);
            }
            $part = ParseTools::stripForeignChars($part);
        
            //Match fights in single page
            $fight_regexp = '/(\\d{2}\\/\\d{2}\\/\\d{2}) \\d{1,7} ([a-zA-Z]+[a-zA-Z0-9\\s\\-.,\']+?) ([+-]{0,1}[0-9]+|EV|even) OFF OFF ([0-9]{2}:[0-9]{2}) [A-Za-z]{2} ([a-zA-Z]+[a-zA-Z0-9\\s\\-.,\']+?) ([+-]{0,1}[0-9]+|EV|even) OFF OFF/';
    
            $fight_matches = ParseTools::matchBlock($part, $fight_regexp);
    
            foreach ($fight_matches as $fight)
            {
                if (ParseTools::checkCorrectOdds(trim((string) $fight[3]))
                    && ParseTools::checkCorrectOdds(trim((string) $fight[6])))
                {
                    $date_obj = new DateTime($fight[1] . ' ' . $fight[4] . ' ' . $sTimezone);
                    $parsed_matchup = new ParsedMatchup(
                                    (string) $fight[2],
                                    (string) $fight[5],
                                    (string) $fight[3],
                                    (string) $fight[6]
                    );
                    $parsed_matchup->setMetaData('gametime', (string) $date_obj->getTimestamp());
                    $parsed_sport->addParsedMatchup($parsed_matchup);
                }
                $counter++;
            }
    
            $this->logger->info('URL ' . $url . ' provided ' . $counter . ' matchups');
        }

        //Declare authorative run if we fill the criteria
        if (!$failed_once && count($parsed_sport->getParsedMatchups()) >= 10)
        {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $parsed_sport;
    }
}

?>