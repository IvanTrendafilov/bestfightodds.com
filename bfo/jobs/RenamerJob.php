<?php

require_once __DIR__ . "/../bootstrap.php";

use BFO\Parser\EventRenamer;

$one_logger = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::INFO, ['filename' => 'renamer.log']);

$er = new EventRenamer($one_logger);
$er->evaluteRenamings();