<html>
<head><title>BFO.com</title></head>
<body>
	<div class="tablesDiv">

<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');

$aBookies = BookieHandler::getAllBookies();
if (sizeof($aBookies) == 0)
{
	echo 'No bookies found';
	exit();
}
$aEvent = EventHandler::getAllUpcomingEvents();


$iCellCounter = 0;
$bAdAdded = false;

//List all events
foreach ($aEvent as $oEvent)
{
	$aFights = EventHandler::getAllFightsForEvent($oEvent->getID(), true);
	if (sizeof($aFights) > 0)
	{
		echo '<div class="tableDiv">';
		echo '<table class="oddsTable" cellspacing="0" summary="' . $oEvent->getName() . ' Odds" style="border: 1px solid black;">';
		echo '<caption>' . $oEvent->getName() . ' - ' . date('M jS' ,strtotime($oEvent->getDate())) . '</caption>';

		echo '<thead>';
		echo '<tr><th scope="col"></th>';

		//List all bookies, save a reference list for later use in table	
		$aBookieRefList = array();
		foreach ($aBookies as $oBookie)
		{
			$aBookieRefList[] = $oBookie->getID();
			echo '<th scope="col">' . (strlen($oBookie->getName()) > 10  ? (substr($oBookie->getName(), 0, 9) . '.') : $oBookie->getName()) . '</th>';
		}
		echo '<th scope="col" colspan="2"></th></tr></thead>
		<tfoot>
			<tr><td colspan="' . ((sizeof($aBookieRefList)) + 3) . '"></td></tr>
		</tfoot>
		<tbody>';
	
		foreach ($aFights as $oFight)
		{
			//List all odds for the fight
			$aFightOdds = EventHandler::getAllLatestOddsForFight($oFight->getID());
			$aOldFightOdds = EventHandler::getAllLatestOddsForFight($oFight->getID(), 1);
			$oBestOdds = EventHandler::getBestOddsForFight($oFight->getID());
	
			$iProcessed = 0;
			$iCurrentOperatorColumn = 0;
			for ($iX = 1; $iX <= 2; $iX++)
			{
				echo '<tr ' . (($iX % 2) == 1 ? '' : 'class="odd"') . '>';
				echo '<th scope="row">' . $oFight->getFighterAsString($iX) . '</th>';
				
				$iProcessed = 0;
				$bEverFoundOldOdds = false;
				
				foreach ($aFightOdds as $oFightOdds)
				{
					$iCurrentOperatorColumn = $iProcessed;	
					while (isset($aBookieRefList[$iCurrentOperatorColumn]) && $aBookieRefList[$iCurrentOperatorColumn] != $oFightOdds->getBookieID())
					{
						echo '<td align="right" class="moneyline"></td>';
						$iCurrentOperatorColumn++;
						$iProcessed++;	
					}
					
					$sClassName = 'normalbet';
					if ($oFightOdds->getFighterOdds($iX) == $oBestOdds->getFighterOdds($iX))
					{
						$sClassName = 'bestbet';
					}
					
					//Loop through the previous odds and check if odds is higher or lower or non-existant (kinda ugly, needs a fix)
					$iCurrentOperatorID = $oFightOdds->getBookieID();
					$bFoundOldOdds = false;
	
					foreach ($aOldFightOdds as $oOldFightOdds)
					{
						if ($oOldFightOdds->getBookieID() == $iCurrentOperatorID)
						{
							if ($oFightOdds->getFighterOdds($iX) > $oOldFightOdds->getFighterOdds($iX))
							{
								echo '<td align="center" class="moneyline">' . $oFightOdds->getFighterOddsAsString($iX) . '</td>'; //up
							}
							else if ($oFightOdds->getFighterOdds($iX) < $oOldFightOdds->getFighterOdds($iX))
							{
								echo '<td align="center" class="moneyline">' . $oFightOdds->getFighterOddsAsString($iX) . '</td>'; //down
							}
							else
							{
								echo '<td align="center" class="moneyline"><span id="oddsID' . $iCellCounter++ . '" class="' . $sClassName . '" style="padding: 1px;">' . $oFightOdds->getFighterOddsAsString($iX) . '</span></td>';
							}
							$bFoundOldOdds = true;
							$bEverFoundOldOdds = true;
						}
					}
					if (!$bFoundOldOdds)
					{
						echo '<td class="moneyline" align="center">' . $oFightOdds->getFighterOddsAsString($iX) . '</td>';
					}
				
					$iProcessed++;	
				}
				
				//Fill empty cells
				for ($iY = $iCurrentOperatorColumn; $iY < (sizeof($aBookieRefList) - 1); $iY++)
				{
					echo '<td class="moneyline"></td>';
				}
			
				echo '</tr>';
			}
		}
		echo '</tbody>';
		echo '</table>';
		echo '</div>';
		
	}
}

?>
	</div>
</body>
</html>