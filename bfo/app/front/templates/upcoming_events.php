<?php $this->layout('template') ?>

<?php foreach ($events as $event_data): ?>

    <?php $this->insert('partials/event', array_merge($event_data, ['bookies' => $bookies, 'alerts_enabled' => true])) ?>

<?php endforeach ?>