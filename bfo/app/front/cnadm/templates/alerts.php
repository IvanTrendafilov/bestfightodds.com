<?php $this->layout('template', ['title' => 'Admin']) ?>

<table border="1" cellspacing="3">
<tr><td>E-mail</td><td>Fight</td><td>Fighter</td><td>Bookie</td><td>Limit</td><td>Odds type</td></tr>

<?php foreach($alerts as $alert): ?>
	
	<tr>
        <td><?=$alert['alert_obj']->getEmail()?></td>
        <td><?=$alert['fight_obj']->getFighterAsString(1)?> vs <?=$alert['fight_obj']->getFighterAsString(2)?></td>
        <td><?=($alert['alert_obj']->getLimit() == -9999 ? 'n/a' : $alert['fight_obj']->getFighterAsString($alert['alert_obj']->getFighter()))?></td>
        <td><?=($alert['alert_obj']->getBookieID() == -1 ? 'All' : $alert['alert_obj']->getBookieID())?></td>
        <td><?=($alert['alert_obj']->getLimit() == -9999 ? 'Show' : $alert['alert_obj']->getLimitAsString())?></td>
        <td><?=$alert['alert_obj']->getOddsType()?></td>
    </tr>
    
<?php endforeach ?>