<?php $this->layout('base/layout', ['title' => 'Admin - Events']) ?>

<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        //Delete flagged odds
        document.querySelectorAll('.delete-odds-button').forEach(item => {
            item.addEventListener('click', e => {
                var input = JSON.parse(e.target.dataset.odds);
                e.preventDefault();
                var opts = {
                    method: 'DELETE',
                    headers: {
                        'Content-type': 'application/json; charset=UTF-8'
                    },
                    body: JSON.stringify({
                        bookie_id: parseInt(input.bookie_id),
                        matchup_id: parseInt(input.matchup_id),
                    })
                };
                fetch('/cnadm/api/odds', opts).then(function(response) {
                    return response.json();
                })
                .then(function(body) {
                    if (body.error == true) {
                        alert(body.msg);
                    } else {
                        //Successfully deleted. Hides row from table
                        e.target.closest('tr').style.color  = '#ddd';
                        e.target.disabled = true;
                    }
                });
            })
        })
    });
</script>

<div class="card">
    <div class="card-header">
        <h5 class="card-title">Flagged odds</h5>
        <h6 class="card-subtitle text-muted">These odds have been flagged for deletion as they have not been seen by the parser during a full run
        </h6>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Event</th>
                <th>Event Date</th>
                <th>Matchup</th>
                <th>Bookie</th>
                <th>First flagged</th>
                <th>Last flagged</th>
                <th>Flagged for</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody class="bg-white">
            <?php foreach ($flagged as $flagged_item) : ?>
                <tr>
                    <td><?= $flagged_item['event_obj']->getName() ?></td>
                    <td><?= $flagged_item['event_obj']->getDate() ?></td>
                    <td><?= $flagged_item['fight_obj']->getTeamAsString(1) ?> vs. <?= $flagged_item['fight_obj']->getTeamAsString(2) ?></td>
                    <td><?= $flagged_item['bookie_name'] ?></td>
                    <td><?= $flagged_item['initial_flagdate'] ?></td>
                    <td><?= $flagged_item['last_flagdate'] ?></td>
                    <td><?= $flagged_item['hours_diff'] ?> hours</td>
                    <td>
                        <button class="btn btn-primary delete-odds-button" data-odds="<?= $this->e('{"bookie_id": "' . $flagged_item['bookie_id'] . '", "matchup_id": "' . $flagged_item['fight_obj']->getID() . '"}') ?>">Delete manually</button>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>