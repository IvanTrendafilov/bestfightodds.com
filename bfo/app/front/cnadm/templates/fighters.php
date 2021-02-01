<?php $this->layout('template', ['title' => 'Admin']) ?>

<form method="post" action="logic/addFighterAltName.php">

		Fighter: <?=$fighter_obj->getName()?> &nbsp; aka &nbsp;
		<input type="hidden" name="fighter_id" value="<?=$fighter_obj->getID()?>"/>	
		<input type="text" name="alt_name" style="width: 200px;"/>
		<input type="submit" value="Add">
</form>

<br />
<form method="post" action="logic/logic.php?action=addTeamTwitterHandle">
		<input type="hidden" name="fighter_id" value="<?=$fighter_obj->getID()?>">Twitter handle 
		[<a href="http://www.google.se/search?q=site:twitter.com<?=$fighter_obj->getName()?>">google</a>]
		 &nbsp;
	<input type="text" name="twitterHandle" style="width: 200px;" value="<?=$twitter_handle ?? ''?>"/>
	<input type="submit" value="Save">
</form>
