<?php $this->layout('base/layout', ['title' => 'Admin', 'current_page' => $this->name->getName()]) ?>
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Created alerts</h5>
        <h6 class="card-subtitle text-muted">This table shows all alerts created by end users
        </h6>
    </div>
    <table class="table table-sm table-hover">
        <thead>
            <tr>
                <th>E-mail</th>
                <th>Matchup</th>
                <th>Fighter</th>
                <th>Bookie</th>
                <th>Limit</th>
                <th>Odds type</th>
            </tr>
        </thead>

        <tbody class="bg-white">
            <?php foreach ($alerts as $alert) : ?>
                <tr>
                    <td><?= $alert['alert_obj']->getEmail() ?></td>
                    <td><?= $alert['fight_obj']->getFighterAsString(1) ?> vs <?= $alert['fight_obj']->getFighterAsString(2) ?></td>
                    <td><?= ($alert['alert_obj']->getLimit() == -9999 ? 'n/a' : $alert['fight_obj']->getFighterAsString($alert['alert_obj']->getFighter())) ?></td>
                    <td><?= ($alert['alert_obj']->getBookieID() == -1 ? 'All' : $alert['alert_obj']->getBookieID()) ?></td>
                    <td><?= ($alert['alert_obj']->getLimit() == -9999 ? 'Show' : $alert['alert_obj']->getLimitAsString()) ?></td>
                    <td><?= $alert['alert_obj']->getOddsType() ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>