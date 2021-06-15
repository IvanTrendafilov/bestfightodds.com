<?php

/**
 * This job will reset all changenums for all bookies. This is used to force fetch a complete copy of the odds from sportsbooks using changenums
 */

require_once __DIR__ . "/../bootstrap.php";

use BFO\General\BookieHandler;

$result = BookieHandler::resetChangeNums();
echo "Result: " . $result;
