<?php

require_once('lib/bfocore/general/class.BookieHandler.php');

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

	if ($lang == 'en-US')
	{
		//Bovada redir
		$sURL = 'http://record.bettingpartners.com/_3_I4QQ0O0x7R1HsxxA1_FGNd7ZgqdRLk/1/';	
	}
	else
	{
		//Bodog redir
		$sURL = 'http://record.bettingpartners.com/_3_I4QQ0O0x6cp_Bvs7i_umNd7ZgqdRLk/1/';	
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
				header('Location: https://www.proboxingodds.com');
			}
		}
		else
		{
			header('Location: https://www.proboxingodds.com');
		}
	}
	else
	{
		header('Location: https://www.proboxingodds.com');
	}	
}

?>