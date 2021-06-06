<?php

require_once __DIR__ . "/../../bootstrap.php";

use BFO\General\BookieHandler;

//This script is used to redirect to different urls depending on different variables such as locale

//Bodog/Bovada
if (isset($_GET['b']) && $_GET['b'] == '5') {
	$lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
	$strpos = stripos($lang, ',');
	if ($strpos > 0) {
		$lang = substr($lang, 0, $strpos);
	}

	if (strtolower($lang) == 'en-ca') {
		//Bodog redir (CA)
		$url = 'https://record.revenuenetwork.com/_0cjkasW-cHa_nF6nwlBnq2Nd7ZgqdRLk/0/';
	} else {
		$bookie = BookieHandler::getBookieByID((int) $_GET['b']);
		//Bovada redir (US)
		$url = $bookie->getRefURL();
	}

	header('Location: ' . $url);
} else {
	if (is_numeric($_GET['b'])) {
		$bookie = BookieHandler::getBookieByID((int) $_GET['b']);
		if ($bookie != null) {
			if ($bookie->getRefURL() != '') {
				header('Location: ' . $bookie->getRefURL());
			} else {
				header('Location: https://www.proboxingodds.com');
			}
		} else {
			header('Location: https://www.proboxingodds.com');
		}
	} else {
		header('Location: https://www.proboxingodds.com');
	}
}
