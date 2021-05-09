<?php $this->layout('base/layout', ['title' => 'Admin - Events']) ?>

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
                        <a href="#">Action</a>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>