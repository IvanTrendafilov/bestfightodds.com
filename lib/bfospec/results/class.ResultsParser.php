<?php

require 'vendor/autoload.php';

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.TeamHandler.php');
require_once('lib/bfocore/parser/utils/class.ParseTools.php');
require_once('config/inc.parseConfig.php');

$rp = new ResultsParser();
$rp->parseResultsForFighter();

class ResultsParser
{
	public $logger;

	public function __construct() 
	{
		$this->logger = new Katzgrau\KLogger\Logger(PARSE_KLOGDIR, Psr\Log\LogLevel::DEBUG, ['prefix' => 'resultsparser_']);
	}

	public function parseResultsForFighter()
	{
		$this->logger->info('ResultParser start');
		$ounte = 0;
		//Retrieve all events that have not been fully parsed. Query should fetch all matchups that do not have results and then grab the events that they are connected to, it is the events that we will look for
		$teams = TeamHandler::getAllTeamsWithMissingResults();

		foreach ($teams as $team)
		{
			$ounte++;
			$this->logger->info('Checking: ' . $team->getName());
			$this->checkFighterPage($team);
		}

		$this->logger->info('ResultParser end');
	}

	public function parseResults()
	{
		$this->logger->info('ResultParser start');
		$ounte = 0;
		//Retrieve all events that have not been fully parsed. Query should fetch all matchups that do not have results and then grab the events that they are connected to, it is the events that we will look for
		$events = EventHandler::getAllEventsWithMatchupsWithoutResults();

		foreach ($events as $event)
		{
			$ounte++;
			$this->logger->info('Checking: ' . $event->getName());
			$this->checkEvent($event);
		}

		$this->logger->info('ResultParser end');
	}


	private function checkFighterPage($fighter)
	{
		//$page_content = $this->getPageFromWikipedia('Cain Velasquez' . ' (fighter)');
		$page_content = $this->getPageFromWikipedia($fighter->getNameAsString() . ' (fighter)');
		//Remove \n
		$page_content=str_replace("\n","",$page_content);


		//First check if this appears to be a correct page

		//CHECK FOR:  strstr
		if (strstr($page_content, '{{MMA record start}}') === false)
		{
			$this->logger->warning('Missing MMA record start. Probably not a fighter page. Aborting');	
			return false;
		}

		//Pick out all the fights ont he page using regexp magic
		$blocks = ParseTools::matchBlock($page_content, '/\|\-.+?(?=\|\-|{{end}})/');
		foreach ($blocks as $block)
		{
			$result = $this->checkFighterMatchup($block[0], $fighter);
		}
	}



	private function getPageFromWikipedia($title)
	{
			//TODO: Error handling
			//Search for it first
			$curl_opts = array(CURLOPT_USERAGENT => 'BestFightOdds/1.0 (https://bestfightodds.com/; info1@bestfightodds.com) BestFightOdds/1.0');
			$wiki_search_url = "https://en.wikipedia.org/w/api.php?action=query&list=search&utf8=&format=json&srsearch=intitle:" . urlencode($title);
			$this->logger->info('Search URL: ' . $wiki_search_url);
			$wiki_search_result = ParseTools::retrievePageFromURL($wiki_search_url, $curl_opts);
			$wiki_search_json = json_decode($wiki_search_result);

			//If the first results are lists of some sort (starts with "List ") then skip them
			$i = 0;
			$searchtitle = $wiki_search_json->query->search[0]->title;
			while (substr($wiki_search_json->query->search[$i]->title, 0, 5) == 'List ' && $i <= count($wiki_search_json->query->search))
			{
				$i++;
				$searchtitle = $wiki_search_json->query->search[$i]->title;

			}
			//Grab content page for found search result
			$wiki_content_url = "https://en.wikipedia.org/w/api.php?format=json&action=query&prop=revisions&rvprop=content&titles=" . urlencode($searchtitle);
			$wiki_content_result = ParseTools::retrievePageFromURL($wiki_content_url, $curl_opts);
			$wiki_content_json = json_decode($wiki_content_result, true);
			$this->logger->info('Found and fetched URL through search: ' . $wiki_content_url);

			return current($wiki_content_json['query']['pages'])['revisions'][0]['*']; 
	}

	private function checkFighterMatchup($matchup, $fighter)
	{
		//Remove [[ ]] and multiple name alternatives (e.g. [[Frank Edgar|Frankie Edgar]])
		$matchup = preg_replace('/\[\[([^\]\|]+)(\|[^\]]+)*|\]\]/', '$1', $matchup);

		//Parse dts date format
		$matches = null;
		preg_match('/{{dts\|([^}]+)}}/', $matchup, $matches);
		$date_pieces = explode('|', $matches[1]);
		//TODO: Add check to validate that we got the pieces we need
		$check_date = null;
		try 
		{
			$check_date = new DateTime ($date_pieces[count($date_pieces) - 1] . ' ' . $date_pieces[count($date_pieces) - 2]  . ' ' . $date_pieces[count($date_pieces) - 3]); //TODO: Validation
		}
		catch (Exception $e)
		{
			$this->logger->warning('Invalid date format: ' . $date_pieces[count($date_pieces) - 1] . ' ' . $date_pieces[count($date_pieces) - 2]  . ' ' . $date_pieces[count($date_pieces) - 3]);	
			return false;
		}
		$matchup = preg_replace('/{{([^}]+)}}/', '', $matchup);


		
		$fields = explode('|', $matchup);
		if (count($fields) != 15)
		{
			$this->logger->info('Warning, unexpected number of table columns. Bailing..');	
			return false;
		}

		$temp_fight = new Fight(-1, $fighter->getNameAsString(), ParseTools::formatName($fields[5]), -1);
		$result_text = trim(strtolower($fields[2])); //win, loss, nc

		$this->logger->info('Found that:  ' . $temp_fight->getTeam($temp_fight->hasOrderChanged() ? 2 : 1) . ' ' . $result_text . ' ' . $temp_fight->getTeam($temp_fight->hasOrderChanged() ? 1 : 2));


		
		$matching_fight = EventHandler::getMatchingFightV2(['team1_name' => $temp_fight->getTeam(1),
															'team2_name' => $temp_fight->getTeam(2),
															'event_id' => $temp_fight->getEventID(),
															'known_fighter_id' => $fighter->getID(),
															'event_date' =>  $check_date->format('Y-m-d')]);
		if ($matching_fight != null)
		{
			$this->logger->info('Found match: ' . $matching_fight->getTeam($temp_fight->hasOrderChanged() ? 2 : 1) . ' vs ' . $matching_fight->getTeam($temp_fight->hasOrderChanged() ? 1 : 2));
			
			$winner_id = null;
			switch ($result_text)
			{
				case 'win':
					$winner_id = $fighter->getID();
				break;
				case 'loss':
					$winner_id = $matching_fight->getFighterID(1) == $fighter->getID() ? $matching_fight->getFighterID(2) : $matching_fight->getFighterID(1);
				break;
				case 'nc':
					$winner_id = -1;
				break;
				default:
					$this->logger->warning('Unknown result:  ' . $result_text);
					return false;
			}

			$method = $this->getGenericWinningMethods($fields[6]);
			//Add checks to convert method to a std format
			$endround = trim($fields[10]); //TODO: Validate numeric
			$endtime = trim($fields[12]); //TODO:  Validate time format (X:XX)

			$result = EventHandler::addMatchupResults(['matchup_id' => $matching_fight->getID(), 
											 'winner' => $winner_id,
											 'method' => $method,
											 'endround' => $endround,
											 'endtime' => $endtime]);

			if ($result == false)
			{
				$this->logger->error('Failed to store results', $block);
				return false;
			}
			$this->logger->info('Result stored');
			return true;
				
		}
		else
		{
			$this->logger->warning('No match found for ' . $temp_fight->getTeam(1) . ' ' . $result_text . ' ' . $temp_fight->getTeam(2));
		}
	}


	private function checkEvent($event)
	{
		$page_content = $this->getPageFromWikipedia($event->getName());
		//Pick out all the fights ont he page using regexp magic
		$blocks = ParseTools::matchBlock($page_content, '{{MMAevent bout[^\}]+}}');
		foreach ($blocks as $block)
		{
			$result = $this->checkEventMatchup($block[0], $event);
		}
	}

	private function checkEventMatchup($matchup, $event)
	{
		//Remove \n
		$matchup=str_replace("\n","",$matchup);
		//Remove [[ ]] and multiple name alternatives (e.g. [[Frank Edgar|Frankie Edgar]])
		$matchup = preg_replace('/\[\[([^\]\|]+)(\|[^\]]+)*|\]\]/', '$1', $matchup);

		$fields = explode('|', $matchup);
		$temp_fight = new Fight(-1, ParseTools::formatName($fields[2]), ParseTools::formatName($fields[4]), $event->getID());

		$this->logger->info('Found that:  ' . $temp_fight->getTeam($temp_fight->hasOrderChanged() ? 2 : 1) . ' ' . $fields[3] . ' ' . $temp_fight->getTeam($temp_fight->hasOrderChanged() ? 1 : 2));

		$matching_fight = EventHandler::getMatchingFightV2(['team1_name' => $temp_fight->getTeam(1),
															'team2_name' => $temp_fight->getTeam(2),
															'event_id' => $temp_fight->getEventID()]);
		if ($matching_fight != null)
		{
			$this->logger->info('Found match: ' . $matching_fight->getTeam($temp_fight->hasOrderChanged() ? 2 : 1) . ' vs ' . $matching_fight->getTeam($temp_fight->hasOrderChanged() ? 1 : 2));
			
			//If the order has changed for the temp_fight, this means the winner is in the second field (team2). However, if the matched fight is swithed in the database due to nicknames and stuff these two cancel eachother out
			$winner_id = null;
			if ($matching_fight->getComment() == 'switched')
			{
				$winner_id = $matching_fight->getFighterID($temp_fight->hasOrderChanged() ? 1 : 2);
				$this->logger->info('Note, switch in DB was made. Confirm results');
			}
			else
			{
				$winner_id = $matching_fight->getFighterID($temp_fight->hasOrderChanged() ? 2 : 1);
			}

			$method = $this->getGenericWinningMethods($fields[5]);
			//Add checks to convert method to a std format
			$endround = trim($fields[6]); //Validate numeric
			$endtime = trim($fields[7]); // Validate time format (X:XX)

			$result = EventHandler::addMatchupResults(['matchup_id' => $matching_fight->getID(), 
											 'winner' => $winner_id,
											 'method' => $method,
											 'endround' => $endround,
											 'endtime' => $endtime]);

			if ($result == false)
			{
				$this->logger->error('Failed to store results', $block);
				return false;
			}
			$this->logger->info('Result stored');
			return true;
				
		}
		else
		{
			$this->logger->warning('No match found for ' . $temp_fight->getTeam(1) . ' ' . $fields[3] . ' ' . $temp_fight->getTeam(2));
		}
	}

	private function getGenericWinningMethods($method)
	{
		$method = trim(strtolower($method));

		//Check for keywords in the method that determines the aggregated method
		if (strpos($method, 'submission') !== false) {
		    return 'submission';
		}
		else if (strpos($method, 'draw') !== false) {
		    return 'draw';
		}
		else if (strpos($method, 'decis') !== false) {
		    if (strpos($method, 'split') !== false) {
		    	return "split dec";
			}
			else if (strpos($method, 'majority') !== false) {
		    	return "majority dec";
			}
			else if (strpos($method, 'unan') !== false) {
		    	return "unanimous dec";
			}
		}
		else if (strpos($method, 'dq') !== false || strpos($method, 'disq') !== false) {
		    return 'nc';
		}		
		else if (strpos($method, 'tko') !== false || strpos($method, 'ko') !== false) {
		    return 'tko/ko';
		}
		else if (strpos($method, 'nc') !== false || strpos($method, 'no contest') !== false) {
		    return 'nc';
		}
		else if (strpos($method, 'stoppage') !== false) {
		    return 'stoppage';
		}
		return 'other: ' . $method;

	}
}

?>