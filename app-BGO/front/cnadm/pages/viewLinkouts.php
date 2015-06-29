<?php

require_once('lib/bfocore/general/class.BookieHandler.php');

$aLinkouts = BookieHandler::getAllDetailedLinkouts(15); 

echo '<table class="genericTable">';

$iCounter = 0;

$aLinkouts = array_reverse($aLinkouts);

foreach($aLinkouts as $aLinkout)
{
  $sModifiedDate = date("Y-m-d H:i:s", strtotime("+6 hours", strtotime($aLinkout->getDate()))); //Add 6 hours to date for admin timezone
	echo '<tr><td>' . $sModifiedDate . '</td><td><b>' . $aLinkout->getBookieName() . '</b></td><td>' . $aLinkout->getEventName() . '</td><td><i>' . $aLinkout->getVisitorIP() . '</i></td></tr>';
}

echo '</table>';

?>