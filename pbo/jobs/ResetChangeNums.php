<?php

//This job will reset all changenums for all bookies. Used to make sure that we the schedule controller can delete unwanted matchups

require_once __DIR__ . "/../bootstrap.php";

use BFO\General\BookieHandler;

$result = BookieHandler::resetChangeNums();
echo "Result: " . $result;
