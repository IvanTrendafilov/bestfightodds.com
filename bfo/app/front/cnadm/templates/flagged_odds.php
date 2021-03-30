<?php $this->layout('template', ['title' => 'Admin - Events']) ?>

<table>
    <tr>
        <td>Event</td>
        <td>Event Date</td>
        <td>Matchup</td>
        <td>Bookie</td>
        <td>First seen</td>
        <td>Last seen</td>
    </tr>
    <?php foreach ($flagged as $flagged_item) : ?>
        <tr>
            <td><?=$flagged_item['event_obj']->getName()?></td>
            <td><?=$flagged_item['event_obj']->getDate()?></td>
            <td><?=$flagged_item['fight_obj']->getTeamAsString(1)?> vs. <?=$flagged_item['fight_obj']->getTeamAsString(2)?></td>
            <td>
                <?php foreach ($flagged_item['bookies'] as $bookie): ?>
                    <?=$bookie?>,
                <?php endforeach ?>
            </td>
            <td><?=$flagged_item['initial_flagdate']?></td>
            <td><?=$flagged_item['last_flagdate']?></td>
            <td><?=$flagged_item['hours_diff']?></td>
        </tr>
    <?php endforeach ?>
</table>