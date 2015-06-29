<form method="post" action="logic/logic.php?action=addEvent">
	Event name: <input type="text" name="eventName" /><br />
	Event date: <input type="text" name="eventDate" value="<?php echo date("Y-m-d"); ?>" /><br />
    Visible on front page: <input type="checkbox" name="eventDisplay" checked /><br /><br />
    <input type="submit" value="Add event" />
</form>