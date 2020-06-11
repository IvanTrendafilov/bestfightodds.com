<?php

require_once('lib/bfocore/general/class.BookieHandler.php');


$aBookies = BookieHandler::getAllBookies();

foreach ($aBookies as $oBookie)
{
	echo '<b>' . $oBookie->getName() . '</b><br />';
	echo '<table class="genericTable">';

	
	$aPropTemplates = BookieHandler::getPropTemplatesForBookie($oBookie->getID());
	foreach ($aPropTemplates as $oPropTemplate)
	{
		echo '<tr><td><b>' . $oPropTemplate->getID() . '</b></td><td>' . $oPropTemplate->toString() . '</td><td><b>' . $oPropTemplate->getPropTypeID() . '</b></td><td>e.g: ' . $oPropTemplate->getFieldsTypeAsExample()  . '</td><td>' . $oPropTemplate->getLastUsedDate() . '</td></tr>';
	}
	echo '</table><br />';	
}






?>