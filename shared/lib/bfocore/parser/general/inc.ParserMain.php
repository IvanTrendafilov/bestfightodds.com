<?php

/**
 * Main include file for parser
 */

//Include global data types
require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/parser/utils/class.ParseTools.php');
require_once('lib/bfocore/parser/utils/class.Logger.php');
require_once('lib/bfocore/parser/general/class.ParsedSport.php');
require_once('lib/bfocore/parser/general/class.ParsedMatchup.php');
require_once('lib/bfocore/parser/general/class.PropParser.php');
require_once('lib/bfocore/parser/general/class.ParsedProp.php');
require_once('lib/bfocore/parser/general/class.CreativeMatcher.php');
require_once('lib/bfocore/parser/general/class.ScheduleChangeTracker.php');

require_once('lib/bfocore/parser/utils/class.ParseRunLogger.php');

?>