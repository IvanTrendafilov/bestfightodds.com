
<?php $this->layout('base/layout', ['title' => 'Admin - Events']) ?>
Add new fight:
<form method="post">
  <input type="text" id="team1" value="<?=$inteam1?>"> vs 
  <input type="text" id="team2" value="<?=$inteam2?>"><br><br>
  <select id="event-id">

    <?php foreach ($events as $event): ?>
        <option value="<?=$event->getID()?>" <?=$ineventid == $event->getID() ? ' selected' : ''?>><?=$event->getName()?> - <?=$event->getDate()?></option>
    <?php endforeach ?>

  </select>&nbsp;&nbsp;<input type="submit" id="create-matchup-button" value="Add fight">
</form>