<?php

require_once __DIR__ . "/../../bootstrap.php";

use BFO\General\BookieHandler;

if (is_numeric($_GET['b'])) {
	$bookie = BookieHandler::getBookieByID((int) $_GET['b']);
	if ($bookie != null) {
		if ($bookie->getRefURL() != '') {
			header('Location: ' . $bookie->getRefURL());
		} else {
			header('Location: https://www.bestfightodds.com');
		}
	} else {
		header('Location: https://www.bestfightodds.com');
	}
} else {
	header('Location: https://www.bestfightodds.com');
}
