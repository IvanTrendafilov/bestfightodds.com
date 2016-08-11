<?php

require 'vendor/autoload.php';

use Respect\Validation\Validator as v;

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.TeamHandler.php');
require_once('lib/bfocore/general/class.FighterHandler.php');
require_once('lib/bfocore/parser/utils/class.ParseTools.php');
require_once('config/inc.parseConfig.php');
require_once('lib/simple_html_dom/simple_html_dom.php');

require_once('lib/bfospec/results/class.EventPageParser.php');
require_once('lib/bfospec/results/class.TeamPageParser.php');
require_once('lib/bfospec/results/class.ResultsParserTools.php');

$rp = new ResultsParser();
$rp->startParser();

class ResultsParser
{
	public $logger;

	public function __construct() 
	{
		$this->logger = new Katzgrau\KLogger\Logger(PARSE_KLOGDIR, Psr\Log\LogLevel::DEBUG, ['prefix' => 'resultsparser_']);
	}

	public function startParser()
	{
		$this->logger->info('ResultParser start');

		$event_parser = new EventPageParser($this->logger);
		$event_parser->checkEventLists();
		//$event_parser->startParseEventResults();

		$team_parser = new TeamPageParser($this->logger);
		$team_parser->startParseTeamResults();



		$this->logger->info('ResultParser end');
	}

	

	

	
}

?>