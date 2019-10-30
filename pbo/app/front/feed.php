<?php


require_once('config/inc.parseConfig.php');

header('Content-type: text/xml');
header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
header('Expires: Mon, 12 Jul 1996 04:11:00 GMT'); //Any date passed.
header('Pragma: no-cache');

$keys = array();

if (!isset($_GET['key']) || !in_array($_GET['key'], $keys))
{
	$oXML = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><bfo_feed></bfo_feed>');
	$oErrorXML = $oXML->addChild('error');
	$oErrorXML->addAttribute('error_code', '1001');
	$oErrorXML->addAttribute('error_message', 'Invalid key specified');
	echo $oXML->asXML();
}
else
{
	readfile(PARSE_PAGEDIR . 'page.feed.xml');
}

?>