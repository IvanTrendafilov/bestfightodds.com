<?php

require_once __DIR__ . "/../../bootstrap.php";

use BFO\General\BookieHandler;

//This script is used to redirect to different urls depending on different variables such as locale

//Bodog/Bovada
if (isset($_GET['b']) && $_GET['b'] == '5')
{
	$lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
	$strpos = stripos($lang, ',');
	if ($strpos > 0)
	{
		$lang = substr($lang, 0, $strpos);
	}

	if (strtolower($lang) == 'en-ca')
	{
		//Bodog redir (CA)
		$sURL = 'https://record.revenuenetwork.com/_0cjkasW-cHa_nF6nwlBnq2Nd7ZgqdRLk/0/';	
	}
	else
	{
		$oBookie = BookieHandler::getBookieByID($_GET['b']);
		//Bovada redir (US)
		$sURL = $oBookie->getRefURL();
	}
	
	header('Location: ' . $sURL);
}
else
{
	if (is_numeric($_GET['b']))
	{
		$oBookie = BookieHandler::getBookieByID($_GET['b']);
		if ($oBookie != null)
		{
			if ($oBookie->getRefURL() != '')
			{
				header('Location: ' . $oBookie->getRefURL());
			}
			else
			{
				header('Location: https://www.bestfightodds.com');
			}
		}
		else
		{
			header('Location: https://www.bestfightodds.com');
		}
	}
	else
	{
		header('Location: https://www.bestfightodds.com');
	}	
}

?>