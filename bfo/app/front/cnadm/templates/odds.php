<?php $this->layout('template', ['title' => 'Admin']) ?>

<table class="odds-table">
<tr>
    <th></th>
    <?php foreach ($bookies as $key => $bookie): ?>
        <th><?=$bookie?></th>
    <?php endforeach ?>
</tr>
<?php foreach ($events as $event): ?>
    <tr><td><?=$event['event_obj']->getName()?></td></tr>
    <?php foreach ($event['matchups'] as $matchup): ?>
        <?php for ($i = 1; $i <= 2; $i++): ?>
            <tr>
            <td><?=$matchup['matchup_obj']->getTeamAsString($i)?></td>
            <?php foreach ($bookies as $key => $bookie): ?>
                <td><?=(isset($matchup['odds'][$key]) ? $matchup['odds'][$key]->getOdds($i) : 'n/a')?></td>
            <?php endforeach ?>
            </tr>
        <?php endfor ?>

    <?php endforeach ?>
    
<?php endforeach ?>
</table>