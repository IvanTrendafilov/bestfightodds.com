<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.FighterHandler.php');
require_once('lib/bfocore/general/class.TwitterHandler.php');

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

echo '</select>&nbsp; aka &nbsp;';

echo 'Bookie: <select name="bookieID">';
echo '<option value="0" selected>- pick one -</option>';
$aBookies = BookieHandler::getAllBookies();
foreach ($aBookies as $oBookie)
{
    if (isset($_GET['inBookieID']) && $_GET['inBookieID'] == $oBookie->getID())
    {
        echo '<option value="' . $oBookie->getID() . '" selected>' . $oBookie->getName() . '</option>';
    } else
    {
        echo '<option value="' . $oBookie->getID() . '">' . $oBookie->getName() . '</option>';
    }
}
echo '</select><br /><br />
<input type="submit" value="Add">
</form>';

?>
