<?php $this->layout('base/layout', ['title' => 'Admin - Log Viewer']) ?>

<?php if (isset($logs)): ?>

    <?php foreach($logs as $logfile): ?>
        <a href="/cnadm/logs/<?=$this->e($logfile)?>"><?=$this->e($logfile)?></a><br />
    <?php endforeach ?>

<?php elseif(isset($log_contents)) : ?>
    <pre>
        <?=$log_contents?>
    </pre>
<?php endif ?>