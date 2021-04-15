<?php $this->layout('base/layout', ['title' => 'Admin - Events']) ?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">New matchup</h5>
    </div>
    <div class="card-body">
        <form method="post">
            <input type="text" id="team1" value="<?= $inteam1 ?>"> vs
            <input type="text" id="team2" value="<?= $inteam2 ?>"><br><br>
            <select id="event-id">

                <?php foreach ($events as $event) : ?>
                    <option value="<?= $event->getID() ?>" <?= $ineventid == $event->getID() ? ' selected' : '' ?>><?= $event->getName() ?> - <?= $event->getDate() ?></option>
                <?php endforeach ?>

            </select>&nbsp;&nbsp;<input type="submit" id="create-matchup-button" value="Add fight">
        </form>
    </div>
</div>