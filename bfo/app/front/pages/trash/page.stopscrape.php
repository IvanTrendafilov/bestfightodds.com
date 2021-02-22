<?php

/**
 * Purpose of this script/page is to scramble the data for scraping applications
 */

require_once('config/inc.config.php');

$rFile = fopen(PARSE_PAGEDIR . 'page.odds.php', 'r');
$sText = fread($rFile, filesize(PARSE_PAGEDIR . 'page.odds.php'));

function scrambleDate($matches)
{
	return date("F jS", strtotime("+" . rand(1, 7) . " days", strtotime($matches[0])));
}
function scrambleOdds($matches)
{
	return (rand(1,2) == '1' ? '-' : '+') . rand(100,400);
}
function scrambleHoursAgo($matches)
{
	return rand(2, 7) . ' hours ago';
}

$sText = preg_replace_callback('((January|February|March|April|May|June|July|August|September|October|November|December) [0-9]{1,2}th)', 'scrambleDate', $sText);
$sText = preg_replace_callback('([-+][0-9]{3}[0-9]*)', 'scrambleOdds', $sText);
$sText = preg_replace_callback('#<\?php [^\?]* \?>#i', 'scrambleHoursAgo', $sText);
echo $sText;

?>