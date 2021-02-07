<?php $this->layout('template', ['title' => 'Admin']) ?>

<form method="post" action="logic/logic.php?action=addManualPropCorrelation"  name="addManualPropCorrelationForm">
Correlation: <input type="text" id="correlation" size="70" value="<?=$input_prop?>" /><br /><br />
Bookie: <input type="hidden" id="bookie_id" size="70" value="<?=$bookie_id?>" /> <?=$bookie_id?>
<br /><br />
Matchup: <select id="matchup_id">

<?php foreach($events as $event): ?>

    <option value="-1"><?=$event['event_obj']->getName()?></option>

    <?php foreach($event['matchups'] as $matchup): ?>
        
        <option value="<?=$matchup['matchup_obj']->getID()?>">&nbsp;&nbsp;&nbsp;<?=$matchup['matchup_obj']->getTeamAsString(1)?> vs <?=$matchup['matchup_obj']->getTeamAsString(2)?></option>
        
    <?php endforeach ?>        

    <option value="0"></option>

<?php endforeach ?>

</select><br /><br />

<input type="submit" value="Add correlation" />
</form>

