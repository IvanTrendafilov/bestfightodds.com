<?php
/**
 * General configuration
 *
 * Remember to end all directories with / (unix) or \\ (windows)
 *
 */

define('GENERAL_HOSTNAME', 'www.bestfightodds.com'); //Used to specify the hostname where this site is hosted. Used mainly to generate URLs in various contexts

define('GENERAL_PRODUCTION_MODE', false); //Used to specify production mode. In production mode, some features are disabled for security purposes. E.g. some tests cannot be run

define('GENERAL_TIMEZONE', -6);	//Timezone for website (if different from system)

define('GENERAL_IMAGE_DIRECTORY', 'G:\\Dev\\www\\bfo\\app\\front\\img\\'); //Used to specify image directory (required for some functions)


define('GENERAL_KLOGDIR', 'C:\\dev2\\bfo\\log\\'); //Directory where Klogger logs should be stored
?>