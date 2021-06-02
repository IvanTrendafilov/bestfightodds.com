<?php

// Used to generate XML Sitemaps and place these in the pages directory as specified in the PARSE_PAGEDIR constant

require_once __DIR__ . "/../bootstrap.php";

use BFO\General\EventHandler;
use BFO\General\TeamHandler;

$smg = new SiteMapGenerator();

$smg->generateTeamSitemap(PARSE_PAGEDIR . 'sitemap-events.xml');
$smg->generateEventSitemap(PARSE_PAGEDIR . 'sitemap-teams.xml');

class SiteMapGenerator
{
    private function writeToFile(string $content, string $target_file): bool
    {
        if (empty($content) || empty($target_file)) {
            return false;
        }

        if (strlen($content) > 200) {
            $page = fopen($target_file, 'w');
            fwrite($page, $content);
            fclose($page);
            return true;
        }
        return false;
    }

    public function generateTeamSitemap(string $filename): bool
    {
        $teams = TeamHandler::getTeams();
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
        foreach ($teams as $team) {
            $teamlink = 'https://www.proboxingodds.com/fighters/' . $team->getFighterAsLinkString();
            $xml->addChild('url')->addChild('loc', $teamlink);
        }
        return $this->writeToFile($xml->asXML(), $filename);
    }

    public function generateEventSitemap(string $filename): bool
    {
        $events = EventHandler::getEvents();
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
        foreach ($events as $event) {
            $eventlink = 'https://www.proboxingodds.com/events/' . $event->getEventAsLinkString();
            $xml->addChild('url')->addChild('loc', $eventlink);
        }
        return $this->writeToFile($xml->asXML(), $filename);
    }
}
