<?php

//Generates a sitemap based on all teams (fighters)

use BFO\General\TeamHandler;

$teams = TeamHandler::getTeams();
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
foreach ($teams as $team)
{
	$teamlink = 'https://' . GENERAL_HOSTNAME . '/fighters/' . $team->getFighterAsLinkString();
	$xml->addChild('url')->addChild('loc', $teamlink);
}
echo $xml->asXML();

?>