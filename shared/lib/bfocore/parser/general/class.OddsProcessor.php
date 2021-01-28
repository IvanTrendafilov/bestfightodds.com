<?php

require_once('lib/bfocore/parser/general/inc.ParserMain.php');

/**
 * OddsProcessor - Goes through parsed matchups and props, matches and stores them in the database. Also keeps track of changes to matchups and more..
 */
class OddsProcessor
{
    private $logger = null;
    private $bookie_id = null;

    public function __construct($logger, $bookie_id)
    {
        $this->logger = $logger;
        $this->bookie_id = $bookie_id;
    }

    public function processSport()
    {

    }

}
