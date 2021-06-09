<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        //Set matchup as main event
        document.querySelectorAll('.set-mainevent-button').forEach(item => {
            item.addEventListener('click', e => {
                e.preventDefault();
                var opts = {
                    method: 'PUT',
                    headers: {
                        'Content-type': 'application/json; charset=UTF-8'
                    },
                    body: JSON.stringify({
                        matchup_id: parseInt(e.target.dataset.matchupid),
                        is_main_event: parseInt(e.target.dataset.mainevent),
                    })
                };
                fetch('/cnadm/api/matchups/' + e.target.dataset.matchupid, opts).then(function(response) {
                        return response.json();
                    })
                    .then(function(body) {
                        if (body.error == true) {
                            alert(body.msg);
                        } else {
                            location.reload();
                        }
                    });
            })
        })

        //Delete matchup
        document.querySelectorAll('.delete-matchup-button').forEach(item => {
            item.addEventListener('click', e => {

                if (confirm("Really remove " + e.target.closest('tr').getElementsByTagName('td')[1].innerText + "?")) {
                    console.log(e.target);
                    e.preventDefault();
                    var opts = {
                        method: 'DELETE',
                        headers: {
                            'Content-type': 'application/json; charset=UTF-8'
                        },
                        body: JSON.stringify({
                            matchup_id: parseInt(e.target.dataset.matchupid),
                        })
                    };
                    fetch('/cnadm/api/matchups/' + e.target.dataset.matchupid, opts).then(function(response) {
                            return response.json();
                        })
                        .then(function(body) {
                            if (body.error == true) {
                                alert(body.msg);
                            } else {
                                //Successfully deleted. Hides row from table
                                e.target.closest('tr').style.display = 'none';
                            }
                        });
                } else {
                    return false;
                }
            })
        })
    });
</script>

<?php foreach ($events as $event) : ?>

    <div class="card">

        <?php if (!isset($hide_header) || $hide_header == false) : ?>
            <div class="card-header d-flex justify-content-between">
                <h5 class="card-title" style="<?= ($event['event_obj']->isDisplayed() ? '' : 'font-style: italic; color: #909090;') ?>"><?= $event['event_obj']->getName() ?> - <?= $event['event_obj']->getDate() ?><a href="#" name="event<?= $event['event_obj']->getID() ?>"></a></h5>
                <div>
                    <a href="/cnadm/events/<?= $event['event_obj']->getID() ?>"><button class="btn btn-primary">Edit or Add matchups</button></a> &nbsp;
                </div>
            </div>
        <?php endif ?>
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Matchup</th>
                    <th>Arbitrage</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php foreach ($event['fights'] as $fight) : ?>
                    <tr>
                        <td><a href="/cnadm/matchups/<?= $fight['fight_obj']->getID() ?>"><?= $fight['fight_obj']->getID() ?></a></td>
                        <td style="<?= $fight['fight_obj']->isMainEvent() ? 'font-weight: bold' : '' ?>"><a href="/cnadm/fighters/<?= $fight['fight_obj']->getFighterID(1) ?>"><?= $fight['fight_obj']->getFighterAsString(1) ?></a> <span style="color: #777777">vs</span> <a href="/cnadm/fighters/<?= $fight['fight_obj']->getFighterID(2) ?>"><?= $fight['fight_obj']->getFighterAsString(2) ?></a></td>
                        <td>
                            <?php if (isset($fight['arbitrage_info'])) : ?>
                                <?= $fight['arbitrage_info']['profit'] ?? '' ?>
                            <?php endif ?>
                        </td>
                        <td>
                            <button class="delete-matchup-button btn btn-sm btn-danger" data-matchupid="<?= $fight['fight_obj']->getID() ?>">Delete</button>
                            <button class="set-mainevent-button btn btn-sm btn-primary" data-matchupid="<?= $fight['fight_obj']->getID() ?>" data-mainevent="<?= $fight['fight_obj']->isMainEvent() ? '0' : '1' ?>"><?= $fight['fight_obj']->isMainEvent() ? 'Demote' : 'Promote' ?></button>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endforeach ?>