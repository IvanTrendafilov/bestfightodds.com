<?php

/**
 * Parsing configuration
 *
 * Remember to end all dirs with / (unix) or \\ (windows)
 * When defining parsers, seperate these with ;
 *
 */
define('PARSE_LOGDIR', 'G:\\Dev\\www\\bfo\\logs\\'); //Directory where logs should be stored
define('PARSE_GENERATORDIR', 'G:\\Dev\\www\\bfo\\app\\generators\\');  //Directory where page generators are stored
define('PARSE_PAGEDIR', 'G:\\Dev\\www\\bfo\\app\\front\\pages\\');  //Directory where generated pages should be stored

define('PARSE_LOG_LEVEL', 0); //Level of detail in the logs, from -2 to 2 . At the lowest level, only errors are shown
//Current in prod:
//define('PARSE_PARSERS', 'Bovada;BovadaProps;BookMaker;BetUS;BetUSFutures;Sportsbook;SBG;TheGreek');
//Blank:
//define('PARSE_PARSERS', '');
define('PARSE_PARSERS', '');

//v.2
define('PARSE_MOCKFEEDS_ON', false); //Enable/disable mock feed mode parsing from static files instead of real feeds
define('PARSE_MOCKFEEDS_DIR', 'G:\\Dev\\www\\bfo\\app\\parsers\\mockfeeds\\');  //Directory where mock feeds are stored

//v.2.1
define('PARSE_CREATIVEMATCHING', false); //Used to specify if parser should try creative ways to match matchups (use with caution);
define('PARSE_CREATEMATCHUPS', false); //Used to specify if parser should create matchups that was not found (use with caution);

define('PARSE_FUTURESEVENT_ID', 197); //Used to identify the event that holds all future (that cant be linked to a specific event) matchups

?>