<?php $this->layout('template', ['title' => 'Admin - Log Viewer']) ?>

<?php if (isset($logs)): ?>

    <?php foreach($logs as $logfile): ?>
        <a href="/cnadm/logs/<?=$this->e($logfile)?>"><?=$this->e($logfile)?></a><br />
    <?php endforeach ?>

<?php elseif(isset($log_contents)) : ?>

    <?=$log_contents?>

<?php endif ?>