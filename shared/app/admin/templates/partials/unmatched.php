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
                            event_row.classList.add("failed")
                        } else {
                            event_row.classList.add("success")
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
                                            item.classList.add("failed")
                                        } else {
                                            item.classList.add("success")
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
                                item.classList.add("failed")
                            } else {
                                item.classList.add("success")
                            }
                        });
                });
            });
        });
    });

</script>

<button id="clear-unmatched-button" class="btn btn-primary">Clear all unmatched matchups and props</button><br /><br />

<div class="card">
    <div class="card-header">
        <h5 class="card-title">Unmatched matchups</h5>
        </h6>
    </div>
    <div class="table-responsive">
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
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title">Matchups</h5>
        </h6>
    </div>
    <div class="table-responsive">
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
                            <td><a href="#" class="create-matchups-for-event" data-eventid="<?= $unmatched_group[0]['view_extras']['event_match']['id'] ?>" data-eventlink="event<?= $i ?>"><button class="btn btn-primary">Create all below at matched event</button></a></td>
                        <?php else : ?>
                            No match..
                            </td>
                            <td><a href="/cnadm/events?in_event_name=<?= $unmatched_group[0]['view_extras']['event_name_reduced'] ?? '' ?>&in_event_date=<?= $unmatched_group[0]['view_extras']['event_date_formatted'] ?>"><button class="btn btn-primary">Create</button></a> <a href="http://www.google.se/search?q=tapology <?= $unmatched_group[0]['view_extras']['event_name_reduced'] ?? '' ?>"><button class="btn btn-primary">Google</button></a></td>
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
                                    <td><a href="/cnadm/newmatchup?inteam1=<?= $unmatched_item['view_indata1'] ?>&inteam2=<?= $unmatched_item['view_indata2'] ?>&ineventid=<?= $unmatched_group[0]['view_extras']['event_match']['id'] ?? '' ?>"><button class="btn btn-primary">Add</button></a> <a href="http://www.google.se/search?q=tapology <?= $unmatched_item['matchup'] ?>"><button class="btn btn-primary">google</button></a>
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
                                    <a href="#" class="create-event-with-matchups" data-eventlink="event<?= $i ?>"><button class="btn btn-primary">Create event and matchups</button></a>
                                <?php else : ?>
                                    &nbsp;
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title">Props without matchups</h5>
        </h6>
    </div>
    <div>Props with no matching matchups: <?=$unmatched_props_matchups_count?></div>
    <div>Props with no matching template: <?=$unmatched_props_templates_count?></div>
    <a href="/cnadm/unmatched_props"><button id="clear-unmatched-button" class="btn btn-primary">Handle unmatched props</button></a><br /><br />
</div>