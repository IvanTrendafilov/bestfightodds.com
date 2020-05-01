<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');

//Add fighter altname:
echo '

<form method="post" action="logic/logic.php?action=clearOddsForMatchupAndBookie">

		Matchup: <select name="matchup_id">
			<option value="-1">(none)</option>';

			$matchups = EventHandler::getAllUpcomingMatchups(true);
			foreach ($matchups as $matchup)
			{
				echo '<option value="' . $matchup->getID() . '" selected>' . $matchup->getTeamAsString(1) . ' vs ' . $matchup->getTeamAsString(2) . '</option>';
			}

echo '</select>&nbsp;';

echo 'Bookie: <select name="bookieID">';
$bookies = BookieHandler::getAllBookies();
foreach($bookies as $bookie)
{
	echo '<option value="' . $bookie->getID() . '">' . $bookie->getName() . '</option>';
}
echo '</select><br /><br />
<input type="submit" value="Remove">
</form>';

?>
