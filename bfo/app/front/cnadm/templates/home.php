<?php $this->layout('template', ['title' => 'Admin']) ?>

<table><b>Average matched in the last 24 hours:</b> <br /><div style="font-size: 12px; display: inline"><tr>

<?php foreach($runstatus as $runstatus_entry): ?>

    <td><?=$runstatus_entry['name']?>: <div style="font-weight: bold; display: inline; color: <?=($runstatus_entry['average_matched'] <= 0 ? 'red;' : 'green;')?>"><?=$runstatus_entry['average_matched']?></div></td>

<?php endforeach ?>

</tr></div></table>
