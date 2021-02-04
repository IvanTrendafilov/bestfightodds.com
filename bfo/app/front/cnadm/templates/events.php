<?php $this->layout('template', ['title' => 'Admin - Events']) ?>

<div>
    <div style="float: left;">
        <?php $this->insert('partials/event', ['events' => $events]) ?>
    </div>
    <div>
        <p style="font-size: 10px; line-height: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;Quick jump to</b><br />
            <?php foreach($events as $event): ?>
                &nbsp;&nbsp;&nbsp;<a href="#event<?=$event['event_obj']->getID()?>" style="color: #000000;"><?=$event['event_obj']->getName()?></a><br />
            <?php endforeach ?>
        </p>
    </div>
</div>