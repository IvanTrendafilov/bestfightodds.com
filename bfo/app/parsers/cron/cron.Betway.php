<?php

require_once 'config/inc.config.php';
require_once 'vendor/autoload.php';
require_once 'lib/bfocore/parser/general/inc.ParserMain.php';

/**
 *  BetWay parser
 * 
 *  Changenum in use: No
 *  IP restriction: No
 *  Feed contact: Ben.Maxton@betway.com
 * 
 */

$config = [
    'feed_url' = 'tbd',
    'feed_key' = '1E557772',
    'bookie_id' = -1,
    'bookie_name' = 'BetWay',
    'custom_curlopts' = []
    ]
$test_mode = 0; //0 = disabled, 1 = test (use real url), 2 = test (use mockfeed)

//TODO: Initatie Klogger logger
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::DEBUG, ['prefix' => 'parser_' . $config['bookie_name']]);
$contents = fetchContents();








function fetchContents()
{
    $base_content = '';
    if ($test_mode == 2) //Use mock feed instead of real URL
    {
        //TODO: Fetch mock feed from file
        $base_content = '';
    }
    else
    {
        $base_content = ParseTools::retrievePageFromURL($config['feed_url'], $config['custom_curlopts']);
    }
}











?>