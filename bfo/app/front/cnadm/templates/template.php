<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>Admin</title>
<script src="/cnadm/js/jquery-1.12.4.min.js" language="JavaScript" type="text/javascript"></script>
<script src="/cnadm/js/jquery.autocomplete.min.js" language="JavaScript" type="text/javascript"></script>
<script src="/cnadm/js/admin-main.js" language="JavaScript" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="/cnadm/css/stylesheets.php" />
</head>
<body>
<div id="adminMenu">
<a href="/cnadm/" style="color: #a30000; text-decoration: none;">
<h1 style="">Admin</h1></a>

<a href="/cnadm/actions">Schedule</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=addNewEventForm">New Event</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=addNewFightForm">New Fight</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=eventsOverview">Events overview</a> <a href="?p=eventsOverview&amp;show=all">(all)</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=addFighterAltName">Altnames</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=addOddsManually">New odds</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=clearOddsForMatchupAndBookie">Delete odds</a><br />
<a href="index.php?p=addNewPropTemplate">New prop template</a>&nbsp;&nbsp;&nbsp;
<a href="/cnadm/proptemplates">View prop templates</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=resetChangeNum">Reset changenum</a>&nbsp;&nbsp;&nbsp;
<a href="index.php?p=testMail">Test mail</a>&nbsp;&nbsp;&nbsp;
<a href="/cnadm/logs/latest">View latest log</a> <a href="/cnadm/logs">(all)</a><br /><br />
</div>
<div class="contentWindow">

    <?=$this->section('content')?>

    </div>
</body>
</html>