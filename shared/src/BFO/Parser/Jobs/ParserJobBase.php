<?php

namespace BFO\Parser\Jobs;

use BFO\Parser\OddsProcessor;
use BFO\Parser\ParsedSport;
use BFO\Parser\Utils\ParseTools;
use BFO\Parser\RulesetInterface;
use Psr\Log\LoggerInterface;

abstract class ParserJobBase
{
    public bool $full_run = false;
    public int $change_num = 0;

    public function __construct(
        public int $bookie_id,
        public LoggerInterface $logger, 
        public RulesetInterface $ruleset,
        public array $content_urls,
        public array $mockfiles
    ) {
    }

    public function run(string $mode = 'normal'): bool
    {
        $this->logger->info('Started parser');

        $contents = [];
        if ($mode == 'mock') {
            foreach ($this->mockfiles as $content_id => $mockfile) {
                $this->logger->info("Note: Using content mock file at " . $mockfile);
                $contents[$content_id] = ParseTools::retrievePageFromFile($mockfile);
            }
        } else {
            $this->logger->info("Fetching content through " . count($this->content_urls) . " URLs");
            $contents = $this->fetchContent($this->content_urls);
        }

        $parsed_sport = $this->parseContent($contents);
        $this->processParsedOdds($parsed_sport);

        $this->logger->info('Finished');
        return true;
    }

    public function processParsedOdds(ParsedSport $parsed_sport): bool
    {
        try {
            $op = new OddsProcessor($this->logger, $this->bookie_id, $this->ruleset);
            $op->processParsedSport($parsed_sport, $this->full_run);
        } catch (\Exception $e) {
            $this->logger->error('Exception: ' . $e->getMessage());
        }
        return true;
    }

    abstract public function fetchContent(array $content_urls): array;

    abstract public function parseContent(array $contents): ParsedSport;
}