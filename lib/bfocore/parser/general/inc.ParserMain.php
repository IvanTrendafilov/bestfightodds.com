<?php

/**
 * Main include file for parser
 */

//Include global data types
require_once('lib/bfocore/general/inc.GlobalTypes.php');

require_once('lib/bfocore/parser/utils/class.ParseTools.php');
require_once('lib/bfocore/parser/utils/class.Logger.php');
require_once('lib/bfocore/parser/general/class.ParsedSport.php');
require_once('lib/bfocore/parser/general/class.ParsedMatchup.php');


require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');

//2.0:
require_once('lib/bfocore/parser/general/class.PropParser.php');
require_once('lib/bfocore/parser/general/class.ParsedProp.php');

//2.1
require_once('lib/bfocore/parser/general/class.CreativeMatcher.php');


?>