<?php

// This job is used to generate XML Sitemaps and place these in the pages directory as specified in the PARSE_PAGEDIR constant

require_once('config/inc.parseConfig.php');
require_once('lib/bfocore/utils/pagegen/class.PageGenerator.php');

PageGenerator::generatePage(PARSE_GENERATORDIR . 'gen.EventXMLSitemap.php',  PARSE_PAGEDIR . 'sitemap-events.xml');
PageGenerator::generatePage(PARSE_GENERATORDIR . 'gen.TeamXMLSitemap.php',  PARSE_PAGEDIR . 'sitemap-teams.xml');

?>