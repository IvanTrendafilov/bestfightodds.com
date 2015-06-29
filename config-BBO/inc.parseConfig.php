<?php

/**
 * Parsing configuration
 *
 * Remember to end all dirs with / (unix) or \\ (windows)
 * When defining parsers, seperate these with ;
 *
 */
define('PARSE_LOGDIR', 'C:\\Dev\\www\\bfo\\logs\\'); //Directory where logs should be stored
define('PARSE_GENERATORDIR', 'C:\\Dev\\www\\bfo\\app\\generators\\');  //Directory where page generators are stored
define('PARSE_PAGEDIR', 'C:\\Dev\\www\\bfo\\app\\front\\pages\\');  //Directory where generated pages should be stored

define('PARSE_LOG_LEVEL', 2); //Level of detail in the logs, from -2 to 2 . At the lowest level, only errors are shown
//Current in prod:
//define('PARSE_PARSERS', '5Dimes;Bovada;BovadaProps;BookMaker;BetUS;BetUSFutures;Sportsbook;SportsInteraction;Pinnacle;SBG;SportBet;TheGreek');
//Blank:
//define('PARSE_PARSERS', '');
define('PARSE_PARSERS', '5Dimes_boxing;BetDSI_boxing;BookMaker_boxing;Bovada_boxing;SportBet_boxing');

//v.2
define('PARSE_MOCKFEEDS_ON', true); //Enable/disable mock feed mode parsing from static files instead of real feeds
define('PARSE_MOCKFEEDS_DIR', 'C:\\Dev\\www\\bfo\\app\\parsers\\mockfeeds\\');  //Directory where mock feeds are stored

//v.2.1
define('PARSE_CREATIVEMATCHING', false); //Used to specify if parser should try creative ways to match matchups (use with caution);
define('PARSE_CREATEMATCHUPS', true); //Used to specify if parser should create matchups that was not found (use with caution);


?>