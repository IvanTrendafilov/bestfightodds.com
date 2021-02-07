<?php $this->layout('template', ['title' => 'Admin']) ?>

<table class="eventsOverview odds-table">
<tr>
    <th></th>
    <?php foreach ($bookies as $key => $bookie): ?>
        <th colspan="2"><?=$bookie?></th>
    <?php endforeach ?>
</tr>
<?php foreach ($events as $event): ?>
    <tr><td><?=$event['event_obj']->getName()?></td></tr>

    <?php foreach ($event['matchups'] as $matchup): ?>
        <tr>
            <td><?=$matchup['matchup_obj']->getTeamAsString(1)?></td>
            <?php foreach ($bookies as $key => $bookie): ?>
                <td><?=(isset($matchup['odds'][$key]) ? $matchup['odds'][$key]->getOdds(1) : 'n/a')?></td>
                <td class="action-cell add-odds">+</td>
            <?php endforeach ?>
        </tr>
        <tr>
            <td><?=$matchup['matchup_obj']->getTeamAsString(2)?></td>
            <?php foreach ($bookies as $key => $bookie): ?>
                <td><?=(isset($matchup['odds'][$key]) ? $matchup['odds'][$key]->getOdds(2) : 'n/a')?></td>
                <?php if (isset($matchup['odds'][$key])): ?>
                    <td class="action-cell del-odds">-</td>
                <?php else: ?>
                    <td></td>
                <?php endif ?>
            <?php endforeach ?>
        </tr>

    <?php endforeach ?>
   
<?php endforeach ?>
</table>