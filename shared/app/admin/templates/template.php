<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>Admin</title>
<script src="/cnadm/js/jquery-1.12.4.min.js" language="JavaScript" type="text/javascript"></script>
<script src="/cnadm/js/admin-main.js" language="JavaScript" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="/cnadm/css/admin.css" />
</head>
<body>
<div id="adminMenu">
<a href="/cnadm/" style="color: #a30000; text-decoration: none;">
<h1 style="">Admin</h1></a>

<a href="/cnadm/manualactions">Schedule</a>&nbsp;&nbsp;&nbsp;
<a href="/cnadm/events">New Event</a>&nbsp;&nbsp;&nbsp;
<a href="/cnadm/newmatchup">New Fight</a>&nbsp;&nbsp;&nbsp;
<a href="/cnadm/events">Events overview</a> <a href="/cnadm/events/all">(all)</a>&nbsp;&nbsp;&nbsp;
<a href="/cnadm/odds">Odds overview</a>&nbsp;&nbsp;&nbsp;
<a href="#">New odds</a>&nbsp;&nbsp;&nbsp;
<a href="/cnadm/flagged">View flagged odds</a>&nbsp;&nbsp;&nbsp;
<a href="/cnadm/proptemplate">New prop template</a>&nbsp;&nbsp;&nbsp;
<a href="/cnadm/proptemplates">View prop templates</a>&nbsp;&nbsp;&nbsp;
<a href="/cnadm/resetchangenums">Reset changenum</a>&nbsp;&nbsp;&nbsp;
<a href="/cnadm/parserlogs">View parser logs</a>&nbsp;&nbsp;&nbsp;
<a href="/cnadm/logs/latest">View latest log</a> <a href="/cnadm/logs">(all)</a>&nbsp;&nbsp;&nbsp;
<a href="/cnadm/logout">Logout</a> <br /><br />
</div>
<div class="contentWindow">

    <?=$this->section('content')?>

    </div>
</body>
</html>