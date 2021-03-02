<?php $this->layout('template', ['title' => 'Admin - Log Viewer']) ?>

<?php if (isset($bookies)): ?>

    Available log files for parsers:<br><br>
    <?php foreach($bookies as $bookie => $log_count): ?>
        <a href="/cnadm/parserlogs/<?=$this->e($bookie)?>"><?=$this->e($bookie)?></a> (<?=$log_count?>)<br>
    <?php endforeach ?>

<?php elseif(isset($log_contents)) : ?>
    <pre>
<?=$log_contents?>
    </pre>
<?php endif ?>