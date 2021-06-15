<?php $this->layout('base/layout', ['title' => 'Admin', 'current_page' => $this->name->getName()]) ?>

<script>
	document.addEventListener("DOMContentLoaded", function(event) {
		document.querySelectorAll('.update-event-button').forEach(item => {
			item.addEventListener('click', e => {
				var input = JSON.parse(e.target.dataset.event);
				e.preventDefault();
				var opts = {
					method: 'PUT',
					headers: {
						'Content-type': 'application/json; charset=UTF-8'
					},
					body: JSON.stringify({
						event_id: parseInt(input.eventid),
						event_name: input.eventname
					})
				};
				fetch('/cnadm/api/events/' + parseInt(input.eventid), opts).then(function(response) {
						return response.json();
					})
					.then(function(body) {
						if (body.error == true) {
							alert(body.msg);
						} else {
							e.target.closest('tr').style.color = '#ddd';
							e.target.disabled = true;
						}
					});
			})
		})
	});
</script>

<?php if ($actions != null && count($actions) > 0) : ?>

	<div class="card">
		<div class="card-header d-flex justify-content-between">
			<div>
				<h5 class="card-title">Scheduler manual actions</h5>
				<h6 class="card-subtitle text-muted">The scheduler has suggested the following changes to be made</h6>
			</div>
			<button class="btn btn-primary" onclick="$('button[onclick^=\'maAdd\']').click();">Accept all <b>Create</b></button>
		</div>
		<div class="table-responsive p-2">
			<table class="table table-sm table-hover">
				<thead>
					<tr>
						<th>Action</th>
						<th>Event/Matchup</th>
						<th>Accept</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($actions as $action) : ?>
						<tr id="ma<?= $action['id'] ?>">

							<?php if ($action['type'] == 1) : ?>

								<td>Create new event: </td>
								<td><b><?= $action['action_obj']->eventTitle ?></b> - <?= $action['action_obj']->eventDate ?>
									<ul>
										<?php foreach ($action['action_obj']->matchups as $aMatchup) : ?>
											<li><?= $this->e($aMatchup[0], 'strtolower|ucwords') ?> vs <?= $this->e($aMatchup[1], 'strtolower|ucwords') ?></li>
										<?php endforeach ?>
									</ul>
								</td>
								<td><button class="btn btn-primary" onclick="maAddEventWithMatchups(<?= $action['id'] ?>, '<?= htmlspecialchars($action['description']) ?>')">Accept</button>

								<?php elseif ($action['type'] == 2) : ?>

								<td>Rename event</td>
								<td>Old: <b><?= $action['view_extra']['new_event']->getName() ?></b><br>New: <b><?= $action['action_obj']->eventTitle ?></b></td>
								<td><button class="btn btn-primary" onclick="maRenameEvent(<?= $action['id'] ?>, '<?= htmlspecialchars($action['description']) ?>')">Accept</button>

								<?php elseif ($action['type'] == 3) : ?>

								<td>Change date of </td>
								<td><b><?= $action['view_extra']['new_event']->getName() ?></b>
									from
									<?= $action['view_extra']['new_event']->getDate() ?>
									to
									<?= $action['action_obj']->eventDate ?></td>
								<td><button class="btn btn-primary" onclick="maRedateEvent(<?= $action['id'] ?>, '<?= htmlspecialchars($action['description']) ?>')">Accept</button>

								<?php elseif ($action['type'] == 4) : ?>

								<td>Delete event </td>
								<td><b><?= $action['view_extra']['new_event']->getName() ?></b> (<?= $action['view_extra']['new_event']->getDate() ?>)</td>
								<td><button class="btn btn-primary" onclick="maDeleteEvent(<?= $action['id'] ?>, '<?= htmlspecialchars($action['description']) ?>')">Accept</button>

								<?php elseif ($action['type'] == 5) : ?>

								<td>Create matchup </td>
								<td><?= $action['action_obj']->matchups[0]->team1 ?> vs. <?= $action['action_obj']->matchups[0]->team2 ?> at <b><?= $action['view_extra']['new_event']->getName() ?></b></td>
								<td><button class="btn btn-primary" onclick="maAddMatchup(<?= $action['id'] ?>, '<?= htmlspecialchars($action['description']) ?>')">Accept</button>

								<?php elseif ($action['type'] == 6) : ?>

								<td>Move </td>
								<td><a href="http://www.google.com/search?q=tapology <?= urlencode($action['view_extra']['matchup']->getTeamAsString(1) . ' vs. ' . $action['view_extra']['matchup']->getTeamAsString(2)) ?>"><?= $action['view_extra']['matchup']->getTeamAsString(1) ?> vs. <?= $action['view_extra']['matchup']->getTeamAsString(2) ?></a>
									from
									<b><?= $action['view_extra']['old_event']->getName() ?></b> (<?= $action['view_extra']['old_event']->getDate() ?>)
									to
									<b><?= $action['view_extra']['new_event']->getName() ?></b> (<?= $action['view_extra']['new_event']->getDate() ?>)
								</td>
								<td><button class="btn btn-primary" onclick="maMoveMatchup(<?= $action['id'] ?>, '<?= htmlspecialchars($action['description']) ?>')">Accept</button>

								<?php elseif ($action['type'] == 7) : //Delete matchup
								?>

								<td>Delete matchup</td>
								<td>
									<a href="http://www.google.com/search?q=tapology <?= urlencode($action['view_extra']['matchup']->getTeamAsString(1) . ' vs. ' . $action['view_extra']['matchup']->getTeamAsString(2)) ?>"><?= $action['view_extra']['matchup']->getTeamAsString(1) ?> vs. <?= $action['view_extra']['matchup']->getTeamAsString(2) ?></a>
									<?= $action['view_extra']['odds'] == null ? ' <span class="badge bg-success">No odds</span> ' : ' <span class="badge bg-warning">Has odds</span>' ?>
									<?= $action['view_extra']['found1'] ? ' <span class="badge bg-success">' . $action['view_extra']['matchup']->getTeamAsString(1) . ' has other matchup</span> ' : '' ?>
									<?= $action['view_extra']['found2'] ? ' <span class="badge bg-success">' . $action['view_extra']['matchup']->getTeamAsString(2) . ' has other matchup</span> ' : '' ?>
									from <b><?= $action['view_extra']['old_event']->getName() ?></b> (<?= $action['view_extra']['old_event']->getDate() ?>)
								<td><button class="btn btn-primary <?= $action['view_extra']['odds'] == null ? 'btn-success' : 'btn-warning' ?>" onclick="maDeleteMatchup(<?= $action['id'] ?>, '<?= htmlspecialchars($action['description']) ?>')">Accept</button>

								<?php elseif ($action['type'] == 8) : //Move matchup to a non-existant event
								?>

								<td>Move the following matchups:<br>
									<?php foreach ($action['view_extra']['matchups'] as $key => $matchup) : ?>
										&nbsp;<?= $matchup->getTeamAsString(1) ?> vs. <?= $matchup->getTeamAsString(2) ?><br>
									<?php endforeach ?>
								</td>
								<td> to </td>
								<td><?= $action['view_extra']['new_event'] != null ? $action['view_extra']['new_event']->getName() : 'TBD' ?></td>
								<td><button class="btn btn-primary" <?= $action['view_extra']['new_event'] == null ? ' disabled=true ' : '' ?> onclick="maMoveMatchup(<?= $action['id'] ?>, ' <?= htmlspecialchars($action['view_extra']['newma'][$key]) ?>')"><br>Accept</button>

								<?php else : ?>

								<td>Unknown action: <?= $action['description'] ?></td>

							<?php endif ?>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		</div>
	</div>
<?php else : ?>
	<div class="card">
		<div class="card-header d-flex justify-content-between">
			<div>
				<h5 class="card-title">Scheduler manual actions</h5>
			</div>
		</div>
		<div class="card-body pt-0  text-success  align-self-center">All done <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
				<path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
			</svg>
		</div>
	</div>

<?php endif ?>

<?php if (isset($recommendations)) : ?>
	<div class="card">
		<div class="card-header">
			<h5 class="card-title">Event renamings</h5>
			<h6 class="card-subtitle text-muted">Sportsbooks metadata has suggested the following renamings
			</h6>
		</div>
		<div class="table-responsive p-2">
			<table class="table table-sm table-hover">
				<thead>
					<tr>
						<th>Event Date</th>
						<th>Current name</th>
						<th>New name</th>
						<th>Action</th>
					</tr>
				</thead>

				<tbody class="bg-white">
					<?php foreach ($recommendations as $recommendation) : ?>
						<?php if ($recommendation['change']) : ?>
							<tr>
								<td><?= $recommendation['event']->getDate() ?></td>
								<td><?= $recommendation['event']->getName() ?></td>
								<td><?= $recommendation['new_name'] ?></td>
								<td>
									<button class="btn btn-primary update-event-button" data-event="<?= $this->e('{"eventid": "' . $recommendation['event']->getID() . '", "eventname": "' . $recommendation['new_name'] . '"}') ?>">Accept</button>
								</td>
							</tr>
						<?php endif ?>
					<?php endforeach ?>
				</tbody>
			</table>
		</div>
	</div>

<?php endif ?>