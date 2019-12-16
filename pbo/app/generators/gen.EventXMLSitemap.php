<?php

//Generates a sitemap based on all events

require_once('config/inc.config.php');
require_once('lib/bfocore/general/class.EventHandler.php');

$events = EventHandler::getAllEvents();
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
foreach ($events as $event)
{
	$eventlink = 'https://' . GENERAL_HOSTNAME . '/events/' . $event->getEventAsLinkString();
	$xml->addChild('url')->addChild('loc', $eventlink);
}
echo $xml->asXML();

?>