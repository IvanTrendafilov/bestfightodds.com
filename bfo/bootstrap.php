<?php

require_once __DIR__ . '../../vendor/autoload.php';
require_once __DIR__ . '../config/inc.config.php';

use BFO\DataTypes\Alert;
use BFO\DataTypes\Event;
use BFO\Fight;
use BFO\DataTypes\Fighter;
use BFO\DataTypes\FightOdds;
use BFO\DataTypes\Bookie;
use BFO\DataTypes\PropBet;
use BFO\DataTypes\PropTemplate;
use BFO\DataTypes\PropType;
use BFO\DataTypes\BookieParser;
use BFO\DataTypes\EventPropBet;

/*$path = 'shared/';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
$path = 'bfo/';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);*/

//require_once 'lib/bfocore/general/inc.GlobalTypes.php';
