<?php $this->layout('base/layout', ['title' => 'Admin']) ?>

Manual actions: <button class="px-4 py-2 bg-gray-800 text-gray-200 rounded-md hover:bg-gray-700 focus:outline-none focus:bg-gray-700" onclick="$('input[onclick^=\'maAdd\']').click();" >Accept all <b>Create</b> actions below</button>
<br /><br />

<?php if ($actions != null && count($actions) > 0): ?>
	

	<div class="flex flex-col mt-8">
    <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="align-middle inline-block min-w-full shadow overflow-hidden sm:rounded-lg border-b border-gray-200">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Event/Matchup</th>
                        <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">From</th>
						<th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider"></th>
                        <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">To</th>
						<th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider"></th>
                        <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Accept</th>
                    </tr>
                </thead>

                <tbody class="bg-white">
				<?php foreach($actions as $action): ?>



	

		<tr id="ma<?=$action['id']?>">

			<?php if ($action['type'] == 1): ?>

				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500">Create new event: </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><?=$action['action_obj']->eventTitle?></td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"> at </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><?=$action['action_obj']->eventDate?> with matchups: <br>
				<?php foreach ($action['action_obj']->matchups as $aMatchup): ?>
					&nbsp;<?=$aMatchup[0]?> vs <?=$aMatchup[1]?><br>
				<?php endforeach ?>
				</td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><input type="submit" value="Accept" onclick="maAddEventWithMatchups(<?=$action['id']?>, '<?=htmlspecialchars($action['description'])?>')">

			<?php elseif ($action['type'] == 2): ?>
				
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500">Rename event </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><?=$action['view_extra']['new_event']->getName()?></td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"> to </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><?=$action['action_obj']->eventTitle?></td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><input type="submit" value="Accept" onclick="maRenameEvent(<?=$action['id']?>, '<?=htmlspecialchars($action['description'])?>')">

			<?php elseif ($action['type'] == 3): ?>
			
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500">Change date of </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><?=$action['view_extra']['new_event']->getName()?></td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"> from </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><?=$action['view_extra']['new_event']->getDate()?></td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"> to </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><?=$action['action_obj']->eventDate?></td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><input type="submit" value="Accept" onclick="maRedateEvent(<?=$action['id']?>, '<?=htmlspecialchars($action['description'])?>')">

			<?php elseif ($action['type'] == 4): ?>
				
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500">Delete event </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><?=$action['view_extra']['new_event']->getName()?> - <?=$action['view_extra']['new_event']->getDate()?></td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><input type="submit" value="Accept" onclick="maDeleteEvent(<?=$action['id']?>, '<?=htmlspecialchars($action['description'])?>')">

			<?php elseif ($action['type'] == 5): ?>
				
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500">Create matchup </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><?=$action['action_obj']->matchups[0]->team1?> vs. <?=$action['action_obj']->matchups[0]->team2?></td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"> at </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><?=$action['view_extra']['new_event']->getName()?></td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><input type="submit" value="Accept" onclick="maAddMatchup(<?=$action['id']?>, '<?=htmlspecialchars($action['description'])?>')">

			<?php elseif ($action['type'] == 6): ?>

				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500">Move </td><td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><a href="http://www.google.com/search?q=tapology <?=urlencode($action['view_extra']['matchup']->getTeamAsString(1) . ' vs. ' . $action['view_extra']['matchup']->getTeamAsString(2))?>"><?=$action['view_extra']['matchup']->getTeamAsString(1)?> vs. <?=$action['view_extra']['matchup']->getTeamAsString(2)?></a></td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"> from </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><?=$action['view_extra']['old_event']->getName()?> (<?=$action['view_extra']['old_event']->getDate()?>)</td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"> to </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><?=$action['view_extra']['new_event']->getName()?> (<?=$action['view_extra']['new_event']->getDate()?>)</td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><input type="submit" value="Accept" onclick="maMoveMatchup(<?=$action['id']?>, '<?=htmlspecialchars($action['description'])?>')">

			<?php elseif ($action['type'] == 7): //Delete matchup ?>

				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500">Delete </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><a href="http://www.google.com/search?q=tapology <?=urlencode($action['view_extra']['matchup']->getTeamAsString(1) . ' vs. ' . $action['view_extra']['matchup']->getTeamAsString(2))?>"><?=$action['view_extra']['matchup']->getTeamAsString(1)?> vs. <?=$action['view_extra']['matchup']->getTeamAsString(2)?></a> 
					<?=$action['view_extra']['odds'] == null ? ' (no odds)' : ' (has odds)'?>
					<?=$action['view_extra']['found1'] ? $action['view_extra']['matchup']->getTeamAsString(1) . ' has other matchup' : ''?>
					<?=$action['view_extra']['found2'] ? $action['view_extra']['matchup']->getTeamAsString(2) . ' has other matchup' : ''?>
				</td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"> from </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><?=$action['view_extra']['old_event']->getName()?> (<?=$action['view_extra']['old_event']->getDate()?>)</td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><input type="submit" value="Accept" onclick="maDeleteMatchup(<?=$action['id']?>, '<?=htmlspecialchars($action['description'])?>')">

			<?php elseif ($action['type'] == 8): //Move matchup to a non-existant event ?>

				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500">Move the following matchups:<br>
					<?php foreach ($action['view_extra']['matchups'] as $key => $matchup): ?>
						&nbsp;<?=$matchup->getTeamAsString(1)?> vs. <?=$matchup->getTeamAsString(2)?><br>
					<?php endforeach ?>
				</td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"> to </td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><?=$action['view_extra']['new_event'] != null ? $action['view_extra']['new_event']->getName() : 'TBD'?></td>
				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><input type="submit" value="Accept" ' .  <?=$action['view_extra']['new_event'] == null ? ' disabled=true ' : ''?> onclick="maMoveMatchup(<?=$action['id']?>, '<?=htmlspecialchars($action['view_extra']['newma'][$key])?>')"><br>

			<?php else: ?>

				<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500">Unknown action: <?=$action['description']?></td>

			<?php endif ?>

		</tr>

		<?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
	
<?php endif ?>

