<?php

$prepop_name = isset($_GET['eventName']) ? $_GET['eventName'] : '';
$prepop_date = isset($_GET['eventDate']) ? $_GET['eventDate'] : date("Y-m-d");

?>

<form method="post" action="logic/logic.php?action=addEvent">
	Event name: <input type="text" name="eventName" value="<?=$prepop_name?>"/><br />
	Event date: <input type="text" name="eventDate" value="<?=$prepop_date?>"/><br />
    Visible on front page: <input type="checkbox" name="eventDisplay" checked /><br /><br />
    <input type="submit" value="Add event" />
</form>