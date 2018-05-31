<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.FighterHandler.php');
require_once('lib/bfocore/general/class.TwitterHandler.php');

//Add fighter altname:
echo '

<form method="post" action="logic/addFighterAltName.php">

		Fighter: <select name="fighter_id">
			<option value="-1">(none)</option>';

			$aFighters = FighterHandler::getAllFighters();
			foreach ($aFighters as $oFighter)
			{
				if (isset($_GET['fighterName']) && $oFighter->getName() == $_GET['fighterName'])
				{
					$iFighterID = $oFighter->getID();
					$sFighterName = $oFighter->getNameAsString();
					echo '<option value="' . $oFighter->getID() . '" selected>' . $oFighter->getNameAsString() . '</option>';
				}
				else
				{
					echo '<option value="' . $oFighter->getID() . '">' . $oFighter->getNameAsString() . '</option>';
				}
			}

echo '</select>&nbsp; aka &nbsp;
	<input type="text" name="alt_name" style="width: 200px;"/>
	<input type="submit" value="Add">
</form>';

$sTwitterHandle = TwitterHandler::getTwitterHandle($iFighterID);

//Add twitter handle:
echo '
<br />
<form method="post" action="logic/logic.php?action=addTeamTwitterHandle">

		<input type="hidden" name="teamID" value="' .  $iFighterID . '">Twitter handle 
		[<a href="http://www.google.se/search?q=site:twitter.com ' . $sFighterName . '">google</a>]
		 &nbsp;
	<input type="text" name="twitterHandle" style="width: 200px;" value="' . $sTwitterHandle . '"/>
	<input type="submit" value="Add">
</form>';


?>
