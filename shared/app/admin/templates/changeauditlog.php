<?php $this->layout('base/layout', ['title' => 'Admin - Log Viewer', 'current_page' => $this->name->getName()]) ?>
<pre>
<?= $log_contents ?>
</pre>