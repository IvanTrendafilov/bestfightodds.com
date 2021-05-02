<?php $this->layout('base/layout', ['title' => 'Admin - Log Viewer']) ?>

<?php if (isset($bookies)): ?>

    Available log files for parsers. Click to view latest log:<br><br>
    <?php foreach ($bookies as $bookie => $values): ?>
        <b><a href="/cnadm/parserlogs/<?=$this->e($bookie)?>"><?=$this->e($bookie)?></a></b> (<?=$values['count']?> logs available) Preview of latest log:<br>
        <pre><?=$values['preview']?></pre>
        <br>
    <?php endforeach ?>

<?php elseif (isset($log_contents)) : ?>
    <pre>
<?=$log_contents?>
    </pre>
<?php endif ?>