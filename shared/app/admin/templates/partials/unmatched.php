<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        document.querySelectorAll('.create-event-with-matchups').forEach(item => {
            item.addEventListener('click', e => {
                e.preventDefault();

                event_row = document.querySelector('#' + e.target.parentNode.dataset.eventlink);
                structure = JSON.parse(event_row.dataset.create);

                var opts = {
                    method: 'POST',
                    headers: {
                        'Content-type': 'application/json; charset=UTF-8'
                    },
                    body: JSON.stringify({
                        event_name: structure.name,
                        event_date: structure.date,
                        event_hidden: false
                    })
                };
                fetch('/cnadm/api/events', opts).then(function(response) {
                        return response.json();
                    })
                    .then(function(body) {
                        if (body.error == true) {
                            alert(body.msg);
                        } else {
                            e.target.closest('tr').style.color = '#ddd';
                            e.target.disabled = true;
                            //Run through matchups and add them to the event that was created
                            document.querySelectorAll(".matchup-row[data-eventlink='" + e.target.parentNode.dataset.eventlink + "']").forEach(function(item) {
                                structure = JSON.parse(item.dataset.create);
                                var opts = {
                                    method: 'POST',
                                    headers: {
                                        'Content-type': 'application/json; charset=UTF-8'
                                    },
                                    body: JSON.stringify({
                                        event_id: body.event_id,
                                        team1_name: structure.inteam1,
                                        team2_name: structure.inteam2,
                                        create_source: 1
                                    })
                                };
                                fetch('/cnadm/api/matchups', opts).then(function(response) {
                                        return response.json();
                                    })
                                    .then(function(body) {
                                        if (body.error == true) {
                                            alert(body.msg);
                                        } else {
                                            item.style.color = '#ddd';
                                            item.getElementsByTagName('button')[0].disabled = true;
                                        }
                                    });
                            });
                        }
                    });
            });
        });

        document.querySelectorAll('.create-matchups-for-event').forEach(item => {
            item.addEventListener('click', e => {
                e.preventDefault();
                const event_id = e.target.parentNode.dataset.eventid;
                //Run through matchups and add them to the event
                document.querySelectorAll(".matchup-row[data-eventlink='" + e.target.parentNode.dataset.eventlink + "']").forEach(function(item) {
                    structure = JSON.parse(item.dataset.create);
                    var opts = {
                        method: 'POST',
                        headers: {
                            'Content-type': 'application/json; charset=UTF-8'
                        },
                        body: JSON.stringify({
                            event_id: event_id,
                            team1_name: structure.inteam1,
                            team2_name: structure.inteam2
                        })
                    };
                    fetch('/cnadm/api/matchups', opts).then(function(response) {
                            return response.json();
                        })
                        .then(function(body) {
                            if (body.error == true) {
                                item.closest('tr').style.color = '#ff0000';
                            } else {
                                item.closest('tr').style.color = '#ddd';
                                item.getElementsByTagName('button')[0].disabled = true;
                                e.target.disabled = true;
                            }
                        });
                });
            });
        });
    });

    document.addEventListener("DOMContentLoaded", function(event) {
        document.querySelectorAll('.clear-unmatched-button').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                var opts = {
                    method: 'POST',
                    headers: {
                        'Content-type': 'application/json; charset=UTF-8'
                    }
                };
                fetch('/cnadm/api/clearunmatched', opts).then(function(response) {
                        return response.json();
                    })
                    .then(function(body) {
                        if (body.error == true) {
                            alert(body.msg);
                        } else {
                            location.reload();
                        }
                    });
            });
        });
    });
</script>

<div class="col-12">
    <div class="card" id="unmatched-normal-card">
        <div class="card-header d-flex justify-content-between">
            <div class="d-flex">
                <h5 class="card-title align-self-center pr-5">Unmatched matchups</h5>
            </div>

            <?php if (count($unmatched_matchup_groups) > 0) : ?>
                <div class="btn-group" role="group" aria-label="Basic example">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('unmatched-group-card').style.display = 'none'; document.getElementById('unmatched-normal-card').style.display = 'flex';" disabled=true>Single view</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('unmatched-normal-card').style.display = 'none'; document.getElementById('unmatched-group-card').style.display = 'flex';">Grouped view</button>
                </div>
                <button class="clear-unmatched-button btn btn-primary pl-5">Clear all unmatched</button>
            <?php endif ?>
        </div>

        <?php if (count($unmatched_matchup_groups) > 0) : ?>

            <div class="table-responsive p-2">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Matchup</th>
                            <th>Dates</th>
                            <th>Parsed events</th>
                            <th>Matched events</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($unmatched_matchup_groups as $key_matchup => $unmatched_matchup_group) : ?>
                            <tr>
                                <td><a href="http://www.google.se/search?q=tapology <?= $this->e($key_matchup, 'strtolower|ucwords') ?>"><?= $this->e($key_matchup, 'strtolower|ucwords') ?></a></td>
                                <td>
                                    <?php foreach ($unmatched_matchup_group['dates'] as $key_date => $date) : ?>

                                        <?= $key_date ?>
                                        <?php foreach ($date['unmatched'] as $unmatched_item) : ?>
                                            <?= $bookies[$unmatched_item['bookie_id']] ?>
                                        <?php endforeach ?><br>
                                    <?php endforeach ?>
                                </td>
                                <td>

                                    <?php foreach ($unmatched_matchup_group['dates'] as $key_date => $date) : ?>
                                        <?php if (isset($date['parsed_events'])) : ?>
                                            <?php foreach ($date['parsed_events'] as $event) : ?>
                                                <?= $event ?><br>
                                            <?php endforeach ?>
                                        <?php endif ?>
                                    <?php endforeach ?>
                                </td>

                                <td>

                                    <?php foreach ($unmatched_matchup_group['dates'] as $key_date => $date) : ?>
                                        <?php if (isset($date['matched_events'])) : ?>
                                            <?php foreach ($date['matched_events'] as $event) : ?>
                                                <?= $event->getName() ?><br>
                                            <?php endforeach ?>
                                        <?php endif ?>
                                    <?php endforeach ?>
                                </td>

                                <td>
                                    <a href="/cnadm/newmatchup?inteam1=<?= $this->e($unmatched_matchup_group['teams'][0], 'urlencode') ?>&inteam2=<?= $this->e($unmatched_matchup_group['teams'][1], 'urlencode') ?>&ineventid=<?= isset(array_values($unmatched_matchup_group['dates'])[0]['matched_events'][0]) ? array_values(@$unmatched_matchup_group['dates'])[0]['matched_events'][0]->getID() : '' ?>"><button class="btn btn-primary">Add</button></a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="card-body pt-0  text-success  align-self-center">All done <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                </svg>
            </div>
        <?php endif ?>
    </div>

    <div class="card" id="unmatched-group-card" style="display: none">
        <div class="card-header d-flex justify-content-between">
            <h5 class="card-title align-self-center">Unmatched matchups</h5>
            <?php if (count($unmatched_groups) > 0) : ?>
                <div class="btn-group" role="group" aria-label="Basic example">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('unmatched-group-card').style.display = 'none'; document.getElementById('unmatched-normal-card').style.display = 'flex';">Single view</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('unmatched-normal-card').style.display = 'none'; document.getElementById('unmatched-group-card').style.display = 'flex';" disabled=true>Grouped view</button>
                </div>

                <button class="clear-unmatched-button btn btn-primary pl-5">Clear all unmatched</button>
            <?php endif ?>
        </div>
        <?php if (count($unmatched_groups) > 0) : ?>
            <div class="table-responsive p-2">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Last seen</th>
                            <th>Bookie</th>
                            <th>Matchup</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($unmatched_groups as $unmatched_group) : ?>
                            <?php if (isset($unmatched_group[0]['view_extras'])) : ?>
                                <tr class="event event-group" id="event<?= isset($i) ? ++$i : $i = 1 ?>" data-create="<?= $this->e('{"name": "' . ($unmatched_group[0]['view_extras']['event_name_reduced'] ?? '') . '", "date": "' . $unmatched_group[0]['view_extras']['event_date_formatted'] . '"}') ?>">
                                    <td></td>
                                    <td></td>
                                    <td data-date=""><b><?= $unmatched_group[0]['metadata']['event_name'] ?? '' ?> / <?= $unmatched_group[0]['view_extras']['event_name_reduced'] ?? '' ?></b> (<?= $unmatched_group[0]['view_extras']['event_date_formatted'] ?>)
                                        <?php if (isset($unmatched_group[0]['view_extras']['event_match']['id'])) : ?>
                                            Match: <?= $unmatched_group[0]['view_extras']['event_match']['name'] ?> (<?= $unmatched_group[0]['view_extras']['event_match']['date'] ?>)
                                    </td>
                                    <td><a href="#" class="create-matchups-for-event" data-eventid="<?= $unmatched_group[0]['view_extras']['event_match']['id'] ?>" data-eventlink="event<?= $i ?>"><button class="btn btn-primary">Auto create all below at matched event</button></a></td>
                                <?php else : ?>
                                    No match..
                                    </td>
                                    <td><a href="/cnadm/events?in_event_name=<?= $unmatched_group[0]['view_extras']['event_name_reduced'] ?? '' ?>&in_event_date=<?= $unmatched_group[0]['view_extras']['event_date_formatted'] ?>"><button class="btn btn-primary">Manually create</button></a> <a href="http://www.google.se/search?q=tapology <?= $unmatched_group[0]['view_extras']['event_name_reduced'] ?? '' ?>"><button class="btn btn-success">Google</button></a></td>
                                <?php endif ?>

                            <?php else : ?>
                                <tr class="event event-group" id="event<?= isset($i) ? ++$i : $i = 1 ?>">
                                    <td></td>
                                    <td></td>
                                    <td data-date=""></td>
                                    <td></td>
                                <?php endif ?>
                                </tr>

                                <?php foreach ($unmatched_group as $unmatched_item) : ?>
                                    <?php if ($unmatched_item['type'] == 0) : ?>
                                        <tr class="matchup-row" data-create="<?= $this->e('{"inteam1": "' . $unmatched_item['view_indata1'] . '", "inteam2": "' . $unmatched_item['view_indata2'] . '"}') ?>" data-eventlink="event<?= $i ?>">
                                            <td><?= date("Y-m-d H:i:s", strtotime($unmatched_item['log_date'])) ?></td>
                                            <td><b><?= $bookies[$unmatched_item['bookie_id']] ?></b></td>
                                            <td>
                                                <?= $this->e($unmatched_item['matchup'], 'strtolower|ucwords') ?></td>
                                            <td><a href="/cnadm/newmatchup?inteam1=<?= $unmatched_item['view_indata1'] ?>&inteam2=<?= $unmatched_item['view_indata2'] ?>&ineventid=<?= $unmatched_group[0]['view_extras']['event_match']['id'] ?? '' ?>"><button class="btn btn-primary">Add</button></a> <a href="http://www.google.se/search?q=tapology <?= $unmatched_item['matchup'] ?>"><button class="btn btn-success">Google</button></a>
                                            </td>
                                        </tr>
                                    <?php endif ?>
                                <?php endforeach ?>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>
                                        <?php if (isset($unmatched_group[0]['view_extras']['event_name_reduced'])) : ?>
                                            <a href="#" class="create-event-with-matchups" data-eventlink="event<?= $i ?>"><button class="btn btn-primary">Auto create new event and matchups</button></a>
                                        <?php else : ?>
                                            &nbsp;
                                        <?php endif ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="card-body pt-0 text-success  align-self-center">All done <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                </svg>
            </div>
        <?php endif ?>
    </div>
</div>
<div class="col-xl-3">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Unmatched props</h5>
            </h6>
        </div>
        <div class="card-body pt-0">
            <div>Unmatched props: <?= $unmatched_props_matchups_count ?></div>
            <div>Props w/o template: <?= $unmatched_props_templates_count ?></div>
            <a href="/cnadm/unmatched_props"><button class="btn btn-primary mt-3">Handle unmatched props</button></a><br /><br />
        </div>
    </div>
</div>