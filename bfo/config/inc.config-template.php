<?php
/*
 * BFO Platform main configuration file
 *
 * Remember to end all dirs with / (unix) or \\ (windows)
 */

/* General */
define('GENERAL_BASEDIR', '/var/www/bfo/bfo'); //Used to specify base directory (where repository is located. Used in other paths below)
define('GENERAL_HOSTNAME', 'www.bestfightodds.com'); //Used to specify the hostname where this site is hosted. Used mainly to generate URLs in various contexts
define('GENERAL_PRODUCTION_MODE', false); //Used to specify production mode. In production mode, some features are disabled for security purposes. E.g. some tests cannot be run
define('GENERAL_TIMEZONE', 0);	//Timezone for website (if different from system)
define('GENERAL_IMAGE_DIRECTORY', GENERAL_BASEDIR . '/app/front/img/'); //Used to specify image directory (required for some functions) @deprecated
define('GENERAL_KLOGDIR', GENERAL_BASEDIR . '/log/'); //Directory where Klogger logs should be stored
define('GENERAL_GRACEPERIOD_SHOW', 8); //Defines how many hours an event should be considered active even if it has passed its event date. Used for example to continue showing the event past its start
define('GENERAL_CNADM_LOGIN', '');
define('GENERAL_CNADM_PWD', '');

/* Mail */
define('MAIL_SMTP_HOST', 'email-smtp.us-east-1.amazonaws.com'); //SMTP Hostname for external e-mail service
define('MAIL_SMTP_PORT', '587'); //SMTP Port for external e-mail service
define('MAIL_SMTP_USERNAME', ''); //Username for external e-mail service
define('MAIL_SMTP_PASSWORD', ''); //Password for external e-mail service
define('MAIL_SENDGRID_KEY', ''); //Sendgrid API key

/* Alerts */
define('ALERTER_ENABLED', false);
define('ALERTER_ADMIN_ALERT','cnordvaller@gmail.com');  //E-mail to alert in case of internal alerts (arbitrage etc.)
define('ALERTER_DEV_MODE', true); //Dev mode on or off, if true no e-mails will be sent
define('ALERTER_MAIL_FROM', 'Best Fight Odds <no-reply@bestfightodds.com>');
define('ALERTER_MAIL_SENDER_MAIL', 'no-reply@bestfightodds.com');
define('ALERTER_SITE_LINK', 'https://www.bestfightodds.com');
define('ALERTER_SITE_NAME', 'Best Fight Odds');
define('ALERTER_TEMPLATE_DIR', GENERAL_BASEDIR . '/../shared/templates/');

/* Cache */
define('IMAGE_CACHE_DIR', GENERAL_BASEDIR . '/app/front/img/cache/');  //Image Cache Directory
define('CACHE_IMAGE_CACHE_ENABLED', false); //Enables/disables cache for graphs. Should only be disabled during development
define('CACHE_PAGE_DIR', GENERAL_BASEDIR . '/app/front/pages/cache/');  //Page Cache Directory
define('CACHE_PAGE_CACHE_ENABLED', false); //Enables/disables cache for pages. Should only be disabled during development

/* Database configuration */
define('DB_HOST', 'localhost'); //Database host
define('DB_USER', 'root');  //Database username
define('DB_PASSWORD', 'root');  //Database password
define('DB_SCHEME', 'bets');  //Database scheme

/* Facebook  */
define('FACEBOOK_ENABLED', true);
define('FACEBOOK_DEV_MODE', true); //In dev mode, no actual posts are created. Instead they are just echo'ed to the prompt
define('FACEBOOK_APP_ID', '1743170102619630');
define('FACEBOOK_APP_SECRET', 'af19c28c37ecbeb4c3db529e293cdb8e');
define('FACEBOOK_ACCESS_TOKEN', 'EAAYxZA2qZC7e4BAOUww5RhqvWr9inuQZCW1koLMhTgVFAA6AYC6DtFHKYZAZCzHJWFYoozarzj3ZC0GZBv8oclgjvkFxBakjZC3X7og7XZCiIMsxohZC1Nr2Agyw37VjJoTpt4heQGcfQJ1oqxDi63nmKC5UCjQKUZB2oANkV319U5ceQZDZD');
define('FACEBOOK_PAGEID', '1759833807635369');

/* Parser */
define('PARSE_LOGDIR', GENERAL_BASEDIR . '/logs/'); //Directory where logs should be stored @deprecated
define('PARSE_GENERATORDIR', GENERAL_BASEDIR . '/app/generators/');  //Directory where page generators are stored
define('PARSE_PAGEDIR', GENERAL_BASEDIR . '/app/front/pages/');  //Directory where generated pages should be stored
define('PARSE_LOG_LEVEL', 2); //Level of detail in the logs, from -2 to 2 . At the lowest level, only errors are shown @deprecated
define('PARSE_PARSERS', 'WilliamHill'); //@deprecated
define('PARSE_MOCKFEEDS_ON', false); //Enable/disable mock feed mode parsing from static files instead of real feeds @deprecated
define('PARSE_MOCKFEEDS_DIR', GENERAL_BASEDIR . '/app/parsers/mockfeeds/');  //Directory where mock feeds are stored
define('PARSE_CREATIVEMATCHING', false); //Used to specify if parser should try creative ways to match matchups (use with caution);
define('PARSE_CREATEMATCHUPS', false); //Used to specify if parser should create matchups that was not found (use with caution);
define('PARSE_FUTURESEVENT_ID', 197); //Used to identify the event that holds all future (that cant be linked to a specific event) matchups

/* Twitter  */
define('TWITTER_ENABLED', true);
define('TWITTER_DEV_MODE', true); //In dev mode, no actual tweets are created. Instead they are just echo'ed to the prompt
define('TWITTER_CONSUMER_KEY', 'KJPrE1ErwOR2uIOtM9Wkg');
define('TWITTER_CONSUMER_SECRET', 'YQi2zjWz1JmFLQXaIDFC02NLWbuY8lmVqCS27jE4Y');
define('TWITTER_OAUTH_TOKEN', '188451945-lh5CpuL31LJmNvzqkaapq6s9GMCybhh5q4lJdvct');
define('TWITTER_OATUH_TOKEN_SECRET', 'tUsLRF1SASbDTeGeP8SScjC6Bn049LihgfDKtaH7dphp4');
define('TWITTER_GROUP_MATCHUPS', true); //Used to indicate if we group multiple matchups on the same event into one tweet (does not include main events or matchups in events that start with UFC)
define('TWITTER_TEMPLATE_SINGLE', "<E>: <T1> (<T1O>) vs. <T2> (<T2O>) https://bestfightodds.com/events/<EVENT_URL>"); //Template used to tweet one matchup in one tweet. <E> = Event name, <T1> = Team one, <T2> = team two, <T1O> = team one odds, <T2O> = team two odds
define('TWITTER_TEMPLATE_MULTI', "New lines for <E> posted https://bestfightodds.com/events/<EVENT_URL>"); //Template used to tweet multiple matchups in one tweet (only available if TWITTER_GROUP_MATCHUPS is enabled)


?>