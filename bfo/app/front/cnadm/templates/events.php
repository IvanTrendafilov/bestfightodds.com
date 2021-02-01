<?php $this->layout('template', ['title' => 'Admin - Events']) ?>

<table class="eventsOverview" style="float: left;">

<?php foreach($events as $event): ?>
   
    <tr>
        <th colspan="6"><a name="event<?=$event['event_obj']->getID()?>"></a><div style="float: left; <?=($event['event_obj']->isDisplayed() ? '' : 'font-style: italic; color: #909090;')?>"><?=$event['event_obj']->getName()?> 
        <span style="color: #777777">-</span> <?=$event['event_obj']->getDate()?> &nbsp;
        <a href="?p=changeEventForm&eventID=<?=$event['event_obj']->getID()?>">edit</a></div>
        <div style="float: right; padding-right: 5px;"><span style="color: #ffffff"><?=sizeof($event['fights'])?></span> <b><a href="?p=addNewFightForm&eventID=<?=$event['event_obj']->getID()?>">add</a></b></div>
    </th>
    </tr>

	<?php foreach($event['fights'] as $fight): ?>

			<tr>
				<td class="eventID"><a href="/fight/<?=$fight['fight_obj']->getID()?>"><?=$fight['fight_obj']->getID()?></a></td>
				<td class="fight <?=$fight['fight_obj']->isMainEvent() ? ' main-event' : ''?>"><a href="/cnadm/fighters/<?=$fight['fight_obj']->getFighterID(1)?>"><?=$fight['fight_obj']->getFighterAsString(1)?></a> <span style="color: #777777">vs</span> <a href="/cnadm/fighters/<?=$fight['fight_obj']->getFighterID(2)?>"><?=$fight['fight_obj']->getFighterAsString(2)?></a></td>
				<td class="arbitrage <?=($fight['arbitrage_info']['profit'] && $fight['arbitrage_info']['profit'] > 1) ? 'positive' : ''?>"><?=$fight['arbitrage_info']['profit'] ?? ''?></td>
				<td class="imageLink"><a href="https://www.bestfightodds.com/fights/<?=$fight['fight_obj']->getID()?>.png">o</a></td>
				<td><a href="logic/logic.php?action=removeFight&fightID=<?=$fight['fight_obj']->getID()?>&returnPage=eventsOverview" onclick="javascript:return confirm(\'Really remove <?=$fight['fight_obj']->getFighterAsString(1)?> vs <?=$fight['fight_obj']->getFighterAsString(2)?>?\')" /><b>x</b></a></td>
				<td class="mainEvent"><a href="logic/logic.php?action=setFightAsMainEvent&fightID=<?=$fight['fight_obj']->getID()?>&isMain=<?=$fight['fight_obj']->isMainEvent() ? '0' : '1'?>"><?=$fight['fight_obj']->isMainEvent() ? 'v' : '^'?></a></td>
			</tr>
	
	<?php endforeach ?>

<?php endforeach ?>

</table>
<p style="font-size: 10px; line-height: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;Quick jump to</b><br />
    <?php foreach($events as $event): ?>
        &nbsp;&nbsp;&nbsp;<a href="#event<?=$event['event_obj']->getID()?>" style="color: #000000;"><?=$event['event_obj']->getName()?></a><br />
    <?php endforeach ?>
</p>