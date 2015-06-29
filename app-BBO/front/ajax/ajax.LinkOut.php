<?php

/**
 * Saves a linkout (log of user clicking an affiliate link)
 *
 * TODO: Maybe move to ajax.Interface.php? Any reason not to?
 */
require_once('lib/bfocore/general/class.BookieHandler.php');

if (isset($_GET['operator']) && strlen($_GET['operator']) < 3 && isset($_GET['event']) && strlen($_GET['event']) < 8)
{
    BookieHandler::saveLinkout($_GET['operator'], $_GET['event'], $_SERVER['REMOTE_ADDR']);
}
?>