<?php
/*
 * BFO Platform main configuration file
 *
 * Remember to end all dirs with / (unix) or \\ (windows)
 */

/* General */
define('GENERAL_BASEDIR', '/var/www/bfo/pbo'); //Used to specify base directory (where repository is located. Used in other paths below)
define('GENERAL_TIMEZONE', 0);	//Timezone for website (if different from system)
define('GENERAL_KLOGDIR', GENERAL_BASEDIR . '/log/'); //Directory where Klogger logs should be stored
define('GENERAL_GRACEPERIOD_SHOW', 8); //Defines how many hours an event should be considered active even if it has passed its event date. Used for example to continue showing the event past its start
define('GENERAL_CNADM_LOGIN', '');
define('GENERAL_CNADM_PWD', '');

/* Mail */
define('MAIL_SMTP_HOST', 'email-smtp.us-east-1.amazonaws.com'); //SMTP Hostname for external e-mail service
define('MAIL_SMTP_PORT', '587'); //SMTP Port for external e-mail service
define('MAIL_SMTP_USERNAME', ''); //Username for external e-mail service
define('MAIL_SMTP_PASSWORD', ''); //Password for external e-mail service

/* Alerts */
define('ALERTER_ENABLED', false);
define('ALERTER_DEV_MODE', true); //Dev mode on or off, if true no e-mails will be sent
define('ALERTER_MAIL_FROM', 'Pro Boxing Odds <no-reply@proboxingodds.com>');
define('ALERTER_MAIL_SENDER_MAIL', 'no-reply@proboxingodds.com');
define('ALERTER_SITE_LINK', 'https://www.proboxingodds.com');
define('ALERTER_SITE_NAME', 'Pro Boxing Odds');
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
define('PARSE_PAGEDIR', GENERAL_BASEDIR . '/app/front/pages/');  //Directory where generated pages should be stored
define('PARSE_MOCKFEEDS_DIR', GENERAL_BASEDIR . '/app/parsers/mockfeeds/');  //Directory where mock feeds are stored
define('PARSE_CREATEMATCHUPS', true); //Used to specify if parser should create matchups that was not found (use with caution);
define('PARSE_MOVEMATCHUPS', true); //Used to specify if odds job should move matchups automatically based on metadata (gametime). Matchups will be moved to generic events (based on date) so use carefully
define('PARSE_USE_DATE_EVENTS', true); //If we are using generic dates as events (e.g. for PBO where no named events are used)
define('PARSE_MATCHUP_TZ_OFFSET', 0);
define('PARSE_FUTURESEVENT_ID', 197); //Used to identify the event that holds all future (that cant be linked to a specific event) matchups
define('PARSE_REMOVE_EMPTY_MATCHUPS', true); //Used to specify if OddsJob should remove empty (no odds) upcoming matchups automatically
define('PARSE_REMOVE_EMPTY_EVENTS', true); //Used to specify if OddsJob should remove empty (no odds) historic events automatically

/* Twitter  */
define('TWITTER_ENABLED', true);
define('TWITTER_DEV_MODE', true); //In dev mode, no actual tweets are created. Instead they are just echo'ed to the prompt
define('TWITTER_CONSUMER_KEY', 'KJPrE1ErwOR2uIOtM9Wkg');
define('TWITTER_CONSUMER_SECRET', 'YQi2zjWz1JmFLQXaIDFC02NLWbuY8lmVqCS27jE4Y');
define('TWITTER_OAUTH_TOKEN', '188451945-lh5CpuL31LJmNvzqkaapq6s9GMCybhh5q4lJdvct');
define('TWITTER_OATUH_TOKEN_SECRET', 'tUsLRF1SASbDTeGeP8SScjC6Bn049LihgfDKtaH7dphp4');
define('TWITTER_GROUP_MATCHUPS', true); //Used to indicate if we group multiple matchups on the same event into one tweet (does not include main events)
define('TWITTER_TEMPLATE_SINGLE', "<E>: <T1> (<T1O>) vs. <T2> (<T2O>) https://proboxingodds.com/events/<EVENT_URL>"); //Template used to tweet one matchup in one tweet. <E> = Event name, <T1> = Team one, <T2> = team two, <T1O> = team one odds, <T2O> = team two odds
define('TWITTER_TEMPLATE_MULTI', "New lines for <E> posted https://proboxingodds.com/events/<EVENT_URL>"); //Template used to tweet multiple matchups in one tweet (only available if TWITTER_GROUP_MATCHUPS is enabled)


?>