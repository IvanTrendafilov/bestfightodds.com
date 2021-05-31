<?php $this->layout('base/layout', ['title' => 'Admin - Log Viewer', 'current_page' => $this->name->getName()]) ?>

<?php if (isset($logs)) : ?>

    <?php foreach ($logs as $logfile) : ?>
        <a href="/cnadm/logs/<?= $this->e($logfile) ?>"><?= $this->e($logfile) ?></a><br />
    <?php endforeach ?>


<?php elseif (isset($log_contents)) : ?>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Log: <?=$log_filename?></h5>
        </div>
        <pre>
<?= $log_contents ?>
    </pre>
    </div>
<?php endif ?>