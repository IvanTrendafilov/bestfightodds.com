<?php

//Disable caching
header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
header('Expires: Mon, 12 Jul 1996 04:11:00 GMT'); //Any date passed.
header('Pragma: no-cache');

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>Admin</title>
<script src="js/jquery-1.12.4.min.js" language="JavaScript" type="text/javascript"></script>
<script src="js/jquery.autocomplete.min.js" language="JavaScript" type="text/javascript"></script>
<script src="js/admin-main.js" language="JavaScript" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="css/stylesheets.php" />
</head>
<body>
<div id="adminMenu">
<a href="index.php" style="color: #a30000; text-decoration: none;">
<h1 style="">Admin</h1></a>

<a href="index.php">Main</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=viewManualActions">Schedule</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=addNewEventForm">New Event</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=addNewFightForm">New Fight</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=eventsOverview">Events overview</a> <a href="?p=eventsOverview&amp;show=all">(all)</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=addFighterAltName">Altnames</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=addOddsManually">New odds</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=clearOddsForMatchupAndBookie">Delete odds</a><br />
<a href="index.php?p=addNewPropTemplate">New prop template</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=viewPropTemplates">View prop templates</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=resetChangeNum">Reset changenum</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=testMail">Test mail</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=viewLatestLog&log=latest">View latest log</a> <a href="index.php?p=viewLatestLog">(all)</a><br /><br />
</div>
<div class="contentWindow">
<?php

if (isset($_GET['p']) && $_GET['p'] != '' && preg_match('/[a-zA-Z0-9]*/', $_GET['p'])) {
    include_once('app/front/cnadm/pages/' . $_GET['p'] . '.php');
} else {
    //Show default
    include_once('app/front/cnadm/pages/main.php');
}

/*if (isset($_GET['message']) && $_GET['message'] != '')
{
    echo '<script language="Javascript">alert ("' . $_GET['message'] . '")</script>';
}*/

?>
</div>
</body>
</html>