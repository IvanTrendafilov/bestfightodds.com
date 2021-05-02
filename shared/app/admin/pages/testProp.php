<?php

//require_once('lib/bfocore/general/class.EventHandler.php');
//require_once('lib/bfocore/general/class.TeamHandler.php');


require_once('lib/bfocore/parser/general/class.PropParser.php');

echo '

<form method="post" action="logic/addFighterAltName.php">

		Fighter: <select name="fighter_id">
			<option value="-1">(none)</option>';

            $aFighters = TeamHandler::getAllFighters();
            foreach ($aFighters as $oFighter) {
                if (isset($_GET['fighterName']) && $oFighter->getName() == $_GET['fighterName']) {
                    echo '<option value="' . $oFighter->getID() . '" selected>' . $oFighter->getNameAsString() . '</option>';
                } else {
                    echo '<option value="' . $oFighter->getID() . '">' . $oFighter->getNameAsString() . '</option>';
                }
            }

echo '</select>&nbsp; aka &nbsp;
	<input type="text" name="alt_name" style="width: 200px;"/>
	<input type="submit" value="Add">
</form>



';
