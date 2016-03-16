<?php

require_once('lib/bfocore/general/class.BookieHandler.php');

echo date('H:i:s');

?>
<br /><br />
All <a href="index.php?p=resetChangeNum&bookie_id=-1">Reset</a><br /><br />
5Dimes <a href="index.php?p=resetChangeNum&bookie_id=1">Reset</a><br />
SportBet <a href="index.php?p=resetChangeNum&bookie_id=2">Reset</a><br />
Pinnacle <a href="index.php?p=resetChangeNum&bookie_id=9">Reset</a><br /><br />


<?php

if (isset($_GET['bookie_id']))
{
	if ($_GET['bookie_id'] == -1)
	{
		//Reset all bookies
		BookieHandler::resetAllChangeNums();
		echo 'Changenum cleared for all bookies';
	}
	else
	{
		BookieHandler::resetChangeNum($_GET['bookie_id']);
		$oBookie = BookieHandler::getBookieByID($_GET['bookie_id']);
		echo 'Changenum cleared for ' . $oBookie->getName() . '. New value: ' . BookieHandler::getChangeNum($_GET['bookie_id']);
	}
}

?> 