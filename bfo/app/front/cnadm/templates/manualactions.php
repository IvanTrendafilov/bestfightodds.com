<?php $this->layout('template', ['title' => 'Admin']) ?>

Manual actions: <a href="#" onclick="$('input[onclick^=\'maAdd\']').click();" >Accept all <b>Create</b> actions below</a>
<br /><br />

<table class="genericTable">

<?php if ($actions != null && count($actions) > 0): ?>

	
	<?php foreach($actions as $action): ?>

		<tr id="ma<?=$action['id']?>"><td>

		<?php if ($action['type'] == 1): ?>
			Create new event: </td><td><?=$action['action_obj']->eventTitle?></td><td> at </td><td><?=$action['action_obj']->eventDate?> with matchups: <br>
			<?php foreach ($action['action_obj']->matchups as $aMatchup): ?>
				&nbsp;<?=$aMatchup[0]?> vs <?=$aMatchup[1]?><br>
			<?php endforeach ?>
			</td><td><input type="submit" value="Accept" onclick="maAddEventWithMatchups(<?=$action['id']?>, \'<?=htmlspecialchars($action['description'])?>\')">

		<?php elseif ($action['type'] == 2): ?>
			Rename event </td><td><?=$action['view_extra']['new_event']->getName()?></td><td> to </td><td><?=$action['action_obj']->eventTitle?>
                        </td><td><input type="submit" value="Accept" onclick="maRenameEvent(<?=$action['id']?>, \'<?=htmlspecialchars($action['description'])?>\')">

		<?php elseif ($action['type'] == 3): ?>
			
                        Change date of </td><td><?=$action['view_extra']['new_event']->getName()?></td><td> from </td><td><?=$action['view_extra']['new_event']->getDate()?></td><td> to </td><td><?=$action['action_obj']->eventDate?>
                        </td><td><input type="submit" value="Accept" onclick="maRedateEvent(<?=$action['id']?>, \'<?=htmlspecialchars($action['description'])?>\')">

		<?php elseif ($action['type'] == 4): ?>

                        Delete event </td><td><?=$action['view_extra']['new_event']->getName()?> - <?=$action['view_extra']['new_event']->getDate()?>
                        </td><td><input type="submit" value="Accept" onclick="maDeleteEvent(' . $action['id'] . ', \'' . htmlspecialchars($action['description']). '\')">

		<?php elseif ($action['type'] == 5): ?>

                        Create matchup </td><td><?=$action['action_obj']->matchups[0]->team1?> vs. <?=$action['action_obj']->matchups[0]->team2?></td><td> at </td><td><?=$action['view_extra']['new_event']->getName()?>
                        </td><td><input type="submit" value="Accept" onclick="maAddMatchup(<?=$action['id']?>, \'<?=htmlspecialchars($action['description'])?>\')">

		<?php endif ?>

                switch ((int) $action['type'])
                {

                    case 6:
                    //Move matchup
                        $oMatchup = EventHandler::getFightByID($action['action_obj']->matchupID);
                        $oOldEvent = EventHandler::getEvent($oMatchup->getEventID());
                        $oNewEvent = EventHandler::getEvent($action['action_obj']->eventID);
                        echo 'Move </td><td><a href="http://www.google.com/search?q=tapology ' . urlencode($oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2)) . '">' . $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2) . '</a></td><td> from </td><td>' . $oOldEvent->getName() . ' (' . $oOldEvent->getDate() . ')</td><td> to </td><td>' . $oNewEvent->getName() . ' (' . $oNewEvent->getDate() . ')';
                        echo '</td><td><input type="submit" value="Accept" onclick="maMoveMatchup(' . $action['id'] . ', \'' . htmlspecialchars($action['description']). '\')" />
                        ';
                    break;
                    case 7:
                    //Delete matchup
                        $oMatchup = EventHandler::getFightByID($action['action_obj']->matchupID);
                        //Check if matchup has odds and the indicate that 
                        $odds = OddsHandler::getOpeningOddsForMatchup($action['action_obj']->matchupID);
        
                        //Check if either fighter has another matchup scheduled and indicate that
                        $matchups1 = EventHandler::getAllFightsForFighter($oMatchup->getFighterID(1));
                        $found1 = false;
                        foreach ($matchups1 as $matchup)
                        {
                            if ($matchup->isFuture() && ($matchup->getFighterID(1) != $oMatchup->getFighterID(1) || $matchup->getFighterID(2) != $oMatchup->getFighterID(2)))
                            {
                                $found1 = true;
                            }
                        }
                        $matchups2 = EventHandler::getAllFightsForFighter($oMatchup->getFighterID(2));
                        $found2 = false;
                        foreach ($matchups2 as $matchup)
                        {
                            if ($matchup->isFuture() && ($matchup->getFighterID(1) != $oMatchup->getFighterID(1) || $matchup->getFighterID(2) != $oMatchup->getFighterID(2)))
                            {
                                $found2 = true;
                            }
                        }
        
                        $oEvent = EventHandler::getEvent($oMatchup->getEventID());
                        echo 'Delete </td><td><a href="http://www.google.com/search?q=tapology ' . urlencode($oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2)) . '">' . $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2) . '</a> ' . ($odds == null ? ' (no odds)' : ' (has odds) ') . ' ' . ($found1 == false ? '' : ' (' . $oMatchup->getTeamAsString(1) . ' has other matchup)') . ' ' . ($found2 == false ? '' : ' (' .  $oMatchup->getTeamAsString(2) . ' has other matchup)') . '</td><td> from </td><td>' . $oEvent->getName() . ' (' . $oEvent->getDate() .')';
                        echo '</td><td><input type="submit" value="Accept" onclick="maDeleteMatchup(' . $action['id'] . ', \'' . htmlspecialchars($action['description']). '\')" />
                        ';
                    break;
                    case 8:
                    //Move matchup to a non-existant event
                    
                        $oNewEvent = EventHandler::getEventByName($action['action_obj']->eventTitle);
                        echo 'Move the following matchups</td><td> to </td><td>' . ($oNewEvent != null ? $oNewEvent->getName() : 'TBD') . '<br />
        ';
                        foreach ($action['action_obj']->matchupIDs as $sMatchup)
                        {
                            $oMatchup = EventHandler::getFightByID($sMatchup);
                            echo '&nbsp;' . $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2). '';
                            $newMA = json_encode(array('matchupID' => $sMatchup, 'eventID' => ($oNewEvent != null ? $oNewEvent->getID() : '-9999')), JSON_HEX_APOS | JSON_HEX_QUOT);
                            echo '</td><td><input type="submit" value="Accept" ' .  ($oNewEvent == null ? ' disabled=true ' : '') . ' onclick="maMoveMatchup(' . $action['id'] . ', \'' . htmlspecialchars($newMA). '\')" /><br/>
        ';					
                        }
        
                    break;
                    default:
                        echo $action['description'];
                }
        
                 echo '</td></tr>';

	<?php endforeach ?>
	
<?php endif ?>

        {
            
            {
                
            }
        }

?>

</table>