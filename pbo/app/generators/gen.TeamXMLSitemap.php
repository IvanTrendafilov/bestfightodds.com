<?php

//Generates a sitemap based on all teams (fighters)

require_once('config/inc.config.php');
require_once('lib/bfocore/general/class.FighterHandler.php');

$teams = FighterHandler::getAllFighters();
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
foreach ($teams as $team)
{
	$teamlink = 'https://' . GENERAL_HOSTNAME . '/fighters/' . $team->getFighterAsLinkString();
	$xml->addChild('url')->addChild('loc', $teamlink);
}
echo $xml->asXML();

?>