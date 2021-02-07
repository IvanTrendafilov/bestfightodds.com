<?php $this->layout('template', ['title' => 'Admin']) ?>

<a href="/cnadm/alerts">Alerts stored: <?=$alertcount?></a>

<br><br>

<table><b>Average matched in the last 24 hours:</b> <br /><div style="font-size: 12px; display: inline"><tr>

<?php foreach($runstatus as $runstatus_entry): ?>

    <td><?=$runstatus_entry['name']?>: <div style="font-weight: bold; display: inline; color: <?=($runstatus_entry['average_matched'] <= 0 ? 'red;' : 'green;')?>"><?=$runstatus_entry['average_matched']?></div></td>

<?php endforeach ?>

</tr></div></table>

<br>

<a href="logic/logic.php?action=clearUnmatched">Clear unmatched table</a><br /><br />

<b>Matchups:</b> <br />
<table class="genericTable">
<?php foreach ($unmatched as $unmatched_item): ?>
	<?php if ($unmatched_item['type'] == 0): ?>
		<tr><td><?=date("Y-m-d H:i:s", strtotime($unmatched_item['log_date']))?></td><td><b><?=$bookies[$unmatched_item['bookie_id']]?></b></td><td>
		<?=$unmatched_item['matchup']?></td><td>[<a href="/cnadm/newmatchup?inteam1=<?=$unmatched_item['view_indata1']?>&inteam2=<?=$unmatched_item['view_indata2']?>">add</a>] [<a href="http://www.google.se/search?q=tapology <?=$unmatched_item['matchup']?>">google</a>] $event_text </td></tr>
	<?php endif ?>
<?php endforeach ?>
</table><br />

<b>Props without templates</b>: <br />
<table class="genericTable">
<?php foreach ($unmatched as $unmatched_item): ?>
	<?php if ($unmatched_item['type'] == 1): ?>
		<tr><td><?=date("Y-m-d H:i:s", strtotime($unmatched_item['log_date']))?></td><td><b><?=$bookies[$unmatched_item['bookie_id']]?></b></td><td><?=$unmatched_item['matchup']?></td><td>[<a href="?p=addNewPropTemplate&inBookieID=<?=$unmatched_item['bookie_id']?>&intemplate=<?=$unmatched_item['view_indata1']?>&innegtemplate=<?=$unmatched_item['view_indata1']?>">add</a>]</td></tr>		
	<?php endif ?>
<?php endforeach ?>
</table><br />

<b>Props without matchups:</b> <br />
<table class="genericTable">
<?php foreach ($unmatched as $unmatched_item): ?>
	<?php if ($unmatched_item['type'] == 2): ?>
		<tr><td><?=date("Y-m-d H:i:s", strtotime($unmatched_item['log_date']))?></td><td><b><?=$bookies[$unmatched_item['bookie_id']]?></b></td><td><?=$unmatched_item['matchup']?></td><td>[<a href="/cnadm/propcorrelation?bookie_id=<?=$unmatched_item['bookie_id']?>&input_prop=<?=$unmatched_item['matchup']?>">link manually</a>]</td></tr>				
	<?php endif ?>
<?php endforeach ?>
</table><br />
