<?php $this->layout('base/layout', ['title' => 'Admin']) ?>

Manual actions: <button class="btn btn-primary" onclick="$('input[onclick^=\'maAdd\']').click();">Accept all <b>Create</b> actions below</button>
<br /><br />

<?php if ($actions != null && count($actions) > 0) : ?>

	<div class="card">
		<div class="table-responsive">
			<table class="table">
				<thead>
					<tr>
						<th>Action</th>
						<th>Event/Matchup</th>
						<th>From</th>
						<th></th>
						<th>To</th>
						<th></th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($actions as $action) : ?>
						<tr id="ma<?= $action['id'] ?>">

							<?php if ($action['type'] == 1) : ?>

								<td>Create new event: </td>
								<td><?= $action['action_obj']->eventTitle ?></td>
								<td> at </td>
								<td><?= $action['action_obj']->eventDate ?> with matchups: <br>
									<?php foreach ($action['action_obj']->matchups as $aMatchup) : ?>
										&nbsp;<?= $aMatchup[0] ?> vs <?= $aMatchup[1] ?><br>
									<?php endforeach ?>
								</td>
								<td><input type="submit" value="Accept" onclick="maAddEventWithMatchups(<?= $action['id'] ?>, '<?= htmlspecialchars($action['description']) ?>')">

								<?php elseif ($action['type'] == 2) : ?>

								<td>Rename event </td>
								<td><?= $action['view_extra']['new_event']->getName() ?></td>
								<td> to </td>
								<td><?= $action['action_obj']->eventTitle ?></td>
								<td><input type="submit" value="Accept" onclick="maRenameEvent(<?= $action['id'] ?>, '<?= htmlspecialchars($action['description']) ?>')">

								<?php elseif ($action['type'] == 3) : ?>

								<td>Change date of </td>
								<td><?= $action['view_extra']['new_event']->getName() ?></td>
								<td> from </td>
								<td><?= $action['view_extra']['new_event']->getDate() ?></td>
								<td> to </td>
								<td><?= $action['action_obj']->eventDate ?></td>
								<td><input type="submit" value="Accept" onclick="maRedateEvent(<?= $action['id'] ?>, '<?= htmlspecialchars($action['description']) ?>')">

								<?php elseif ($action['type'] == 4) : ?>

								<td>Delete event </td>
								<td></td>
								<td></td>
								<td><?= $action['view_extra']['new_event']->getName() ?> - <?= $action['view_extra']['new_event']->getDate() ?></td>
								<td></td>
								<td></td>
								<td><input type="submit" value="Accept" onclick="maDeleteEvent(<?= $action['id'] ?>, '<?= htmlspecialchars($action['description']) ?>')">

								<?php elseif ($action['type'] == 5) : ?>

								<td>Create matchup </td>
								<td><?= $action['action_obj']->matchups[0]->team1 ?> vs. <?= $action['action_obj']->matchups[0]->team2 ?></td>
								<td> at </td>
								<td><?= $action['view_extra']['new_event']->getName() ?></td>
								<td><input type="submit" value="Accept" onclick="maAddMatchup(<?= $action['id'] ?>, '<?= htmlspecialchars($action['description']) ?>')">

								<?php elseif ($action['type'] == 6) : ?>

								<td>Move </td>
								<td><a href="http://www.google.com/search?q=tapology <?= urlencode($action['view_extra']['matchup']->getTeamAsString(1) . ' vs. ' . $action['view_extra']['matchup']->getTeamAsString(2)) ?>"><?= $action['view_extra']['matchup']->getTeamAsString(1) ?> vs. <?= $action['view_extra']['matchup']->getTeamAsString(2) ?></a></td>
								<td> from </td>
								<td><?= $action['view_extra']['old_event']->getName() ?> (<?= $action['view_extra']['old_event']->getDate() ?>)</td>
								<td> to </td>
								<td><?= $action['view_extra']['new_event']->getName() ?> (<?= $action['view_extra']['new_event']->getDate() ?>)</td>
								<td><input type="submit" value="Accept" onclick="maMoveMatchup(<?= $action['id'] ?>, '<?= htmlspecialchars($action['description']) ?>')">

								<?php elseif ($action['type'] == 7) : //Delete matchup 
								?>

								<td>Delete </td>
								<td><a href="http://www.google.com/search?q=tapology <?= urlencode($action['view_extra']['matchup']->getTeamAsString(1) . ' vs. ' . $action['view_extra']['matchup']->getTeamAsString(2)) ?>"><?= $action['view_extra']['matchup']->getTeamAsString(1) ?> vs. <?= $action['view_extra']['matchup']->getTeamAsString(2) ?></a>
									<?= $action['view_extra']['odds'] == null ? ' <span class="badge bg-success">No odds</span> ' : ' <span class="badge bg-warning">Has odds</span>' ?>
									<?= $action['view_extra']['found1'] ? ' <span class="badge bg-success">' . $action['view_extra']['matchup']->getTeamAsString(1) . ' has other matchup</span> ' : '' ?>
									<?= $action['view_extra']['found2'] ? ' <span class="badge bg-success">' . $action['view_extra']['matchup']->getTeamAsString(2) . ' has other matchup</span> ' : '' ?>
								</td>
								<td> from </td>
								<td><?= $action['view_extra']['old_event']->getName() ?> (<?= $action['view_extra']['old_event']->getDate() ?>)</td>
								<td></td>
								<td></td>
								<td><input type="submit" value="Accept" onclick="maDeleteMatchup(<?= $action['id'] ?>, '<?= htmlspecialchars($action['description']) ?>')">

								<?php elseif ($action['type'] == 8) : //Move matchup to a non-existant event 
								?>

								<td>Move the following matchups:<br>
									<?php foreach ($action['view_extra']['matchups'] as $key => $matchup) : ?>
										&nbsp;<?= $matchup->getTeamAsString(1) ?> vs. <?= $matchup->getTeamAsString(2) ?><br>
									<?php endforeach ?>
								</td>
								<td> to </td>
								<td><?= $action['view_extra']['new_event'] != null ? $action['view_extra']['new_event']->getName() : 'TBD' ?></td>
								<td><input type="submit" value="Accept" ' .  <?= $action['view_extra']['new_event'] == null ? ' disabled=true ' : '' ?> onclick="maMoveMatchup(<?= $action['id'] ?>, ' <?= htmlspecialchars($action['view_extra']['newma'][$key]) ?>')"><br>

								<?php else : ?>

								<td>Unknown action: <?= $action['description'] ?></td>

							<?php endif ?>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		</div>
	</div>

<?php endif ?>