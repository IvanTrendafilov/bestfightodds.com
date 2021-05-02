<?php

//This job will reset all changenums for all bookies. Used to make sure that we the schedule controller can delete unwanted matchups

require_once('lib/bfocore/general/class.BookieHandler.php');

BookieHandler::resetAllChangeNums();
