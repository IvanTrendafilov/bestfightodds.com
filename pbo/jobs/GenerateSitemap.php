<?php

// This job is used to generate XML Sitemaps and place these in the pages directory as specified in the PARSE_PAGEDIR constant

require_once __DIR__ . "/../bootstrap.php";

generatePage(PARSE_GENERATORDIR . 'gen.EventXMLSitemap.php',  PARSE_PAGEDIR . 'sitemap-events.xml');
generatePage(PARSE_GENERATORDIR . 'gen.TeamXMLSitemap.php',  PARSE_PAGEDIR . 'sitemap-teams.xml');

function generatePage($generator_file, $target_file)
{
    if ($generator_file == null || $target_file == null || !file_exists($generator_file))
    {
        return null;
    }

    ob_start();
    include_once($generator_file);
    $buffer = ob_get_clean();
    if (strlen($buffer) > 200)
    {
        $page = fopen($target_file, 'w');
        fwrite($page, $buffer);
        fclose($page);
        return true;
    }
    return false;
}
