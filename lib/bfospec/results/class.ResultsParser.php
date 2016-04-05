<?php

require 'vendor/autoload.php';

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.TeamHandler.php');
require_once('lib/bfocore/general/class.FighterHandler.php');
require_once('lib/bfocore/parser/utils/class.ParseTools.php');
require_once('config/inc.parseConfig.php');
require_once('lib/simple_html_dom/simple_html_dom.php');

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
		$this->startParseTeamResults();
		//$this->startParseEventResults();
		$this->logger->info('ResultParser end');
	}

	private function startParseTeamResults()
	{
		//Retrieve all teams that have missing reults
		$teams = TeamHandler::getAllTeamsWithMissingResults();
		shuffle($teams);
		$this->logger->info('Parsing teams start');
		foreach ($teams as $team)
		{
			$this->logger->info('Checking: ' . $team->getName());
			$this->checkFighterPageHTML($team);
		}
		$this->logger->info('Parsing teams end');
	}

	private function startParseEventResults()
	{
		//Retrieve all events that have missing reults
		$events = EventHandler::getAllEventsWithMatchupsWithoutResults();
		$this->logger->info('Parsing events start');
		foreach ($events as $event)
		{
			$this->logger->info('Checking: ' . $event->getName());
			$this->checkEventHTML($event);
		}
		$this->logger->info('Parsing events end');
	}

	private function checkFighterPageHTML($fighter)
	{
		$found_unmatched = false;
		$found_matched = false;
		$page_content = $this->getHTMLPageFromWikipedia($fighter->getNameAsString() . ' hastemplate:"Infobox martial artist" hastemplate:"MMArecordbox"');
		if ($page_content == false)
		{
			$this->logger->error('No page found');
			return false;
		}

		//Remove \n
		$page_content=str_replace("\n","",$page_content);

        $html = new simple_html_dom();
        $html->load($page_content);

		//Find mixed martial arts record headline and grab the 2nd table after. TODO: Maybe make this find a bit more intelligent
        $node = $html->find('span[id="Mixed_martial_arts_record"]',0);
        if (!is_object($node))
        {
        	$this->logger->error('Could not find Mixed martial arts record tag');
        	return false;
        }
        
		$i = 0;
		while (substr($node->tag,0,1) != 'h' && $i < 5)
		{
			$node = $node->parent();
		}

		if (!is_object($node->nextSibling()) && !is_object($node->nextSibling()->nextSibling()))
		{
			$this->logger->error('Invalid page');
        	return false;
		}
        $matchups = $node->nextSibling()->nextSibling();
        if (!isset($matchups->plaintext))
        {
        	$this->logger->error('Could not find matchups in HTML');
        	return false;
        }
        
		//Fetch all matchups to keep track which ones we have matched and not
		$existing_db_matchups = EventHandler::getAllFightsForFighter($fighter->getID());
		$existing_matchups = [];
		foreach ($existing_db_matchups as $existing_db_matchup)
		{
			$existing_matchups[$existing_db_matchup->getID()] = false;
		}

		//Loop through rows and fetch data. Pass it to checkFighterMatchupHTML
       	foreach ($matchups->find("tbody tr") as $matchup_row) 
        {
        	$matchup_data = [];
        	foreach ($matchup_row->find('td') as $matchup_cell)
        	{
        		$matchup_data[] = $matchup_cell->plaintext;
        	}
        	
        	if ($matchup_data != null && sizeof($matchup_data) == 10)
        	{
        		if (sizeof($matchup_data) != 10)
        		{
					$this->logger->error('Invalid cell count. Aborting');
	        		return false;
        		} 

				$result = $this->checkFighterMatchupHTML($matchup_data, $fighter);
				if ($result != false)
				{
					$existing_matchups[$result] = true;
					$found_matched = true;
				}
				else
				{
					$found_unmatched = true;
				}
        	}
        }

		//Check which matchups thare weren't found for some reason. If no checks above returned false then these should probably be cleaned as Cancalled bouts
		foreach ($existing_matchups as $key => $val)
		{
			if ($found_matched == true && $found_unmatched == false && $val == false)
			{
				$matchup = EventHandler::getFightByID($key);
				$this->logger->info('Matchup ' . $key . '(' . $matchup->getTeamAsString(1) . ' vs. ' . $matchup->getTeamAsString(2) . ') was not found on wiki page. Should probably be cleared as Cancelled');
			}
		}
	}

	private function getPageFromWikipedia($title)
	{
			$searchtitle = $this->searchWikipediaForTitle($title);
			//Grab content page for found search result
			$curl_opts = array(CURLOPT_USERAGENT => 'BestFightOdds/1.0 (https://bestfightodds.com/; info1@bestfightodds.com) BestFightOdds/1.0');
			$wiki_content_url = "https://en.wikipedia.org/w/api.php?format=json&action=query&prop=revisions&rvprop=content&titles=" . urlencode($searchtitle);
			$wiki_content_result = ParseTools::retrievePageFromURL($wiki_content_url, $curl_opts);
			$wiki_content_json = json_decode($wiki_content_result, true);
			$this->logger->info('Found and fetched URL through search: ' . $wiki_content_url);
			return current($wiki_content_json['query']['pages'])['revisions'][0]['*']; 
	}

	private function getHTMLPageFromWikipedia($title)
	{
			$searchtitle = $this->searchWikipediaForTitle($title);
			//Grab content page for found search result
			$curl_opts = array(CURLOPT_USERAGENT => 'BestFightOdds/1.0 (https://bestfightodds.com/; info1@bestfightodds.com) BestFightOdds/1.0');
			$wiki_content_url = "https://en.wikipedia.org/w/api.php?action=parse&format=json&prop=text&page=" . urlencode($searchtitle);
			$wiki_content_result = ParseTools::retrievePageFromURL($wiki_content_url, $curl_opts);
			$wiki_content_json = json_decode($wiki_content_result, true);
			$this->logger->info('Found and fetched URL through search: ' . $wiki_content_url);
			return $wiki_content_json['parse']['text']['*']; 
	}

	private function searchWikipediaForTitle($title)
	{
		$curl_opts = array(CURLOPT_USERAGENT => 'BestFightOdds/1.0 (https://bestfightodds.com/; info1@bestfightodds.com) BestFightOdds/1.0');
		$wiki_search_url = "https://en.wikipedia.org/w/api.php?action=query&list=search&utf8=&format=json&srsearch=" . urlencode($title);
		$this->logger->info('Search URL: ' . $wiki_search_url);
		$wiki_search_result = ParseTools::retrievePageFromURL($wiki_search_url, $curl_opts);
		$wiki_search_json = json_decode($wiki_search_result);

		if (!isset($wiki_search_json->query->search[0]->title))
		{
			$this->logger->warning('No search results found');	
			return false;
		}

		//If the first results are lists of some sort (starts with "List ") then skip them
		$i = 0;
		$searchtitle = $wiki_search_json->query->search[0]->title;
		while (substr($wiki_search_json->query->search[$i]->title, 0, 5) == 'List ' && $i <= count($wiki_search_json->query->search))
		{
			$i++;
			$searchtitle = $wiki_search_json->query->search[$i]->title;

		}
		return $searchtitle;
	}

	private function checkFighterMatchupHTML($matchup, $fighter)
	{
		//Fetch date from fields and validate
		$check_date = null;	
		if (preg_match("/((January|February|March|April|May|June|July|August|September|October|November|December) \d{1,2},\s?\d{4})/", $matchup[5], $datematches))
		{
			$check_date = $datematches[1];
		}
		try 
		{
			$check_date = new DateTime ($check_date); //TODO: Validation
		}
		catch (Exception $e)
		{
			$this->logger->warning('Invalid date format: ' . $check_date);	
			return false;
		}
		
		$temp_fight = new Fight(-1, $fighter->getNameAsString(), ParseTools::formatName($matchup[2]), -1);
		$result_text = trim(strtolower($matchup[0])); //win, loss, nc

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
				case 'draw':
					$winner_id = -1;
				break;
				default:
					$this->logger->warning('Unknown result:  ' . $result_text);
					return false;
			}

			$method = $this->getGenericWinningMethods($matchup[3])
;			//Add checks to convert method to a std format
			$endround = trim($matchup[6]); //TODO: Validate numeric
			$endtime = trim($matchup[7]); //TODO:  Validate time format (X:XX)

			$result = EventHandler::addMatchupResults(['matchup_id' => $matching_fight->getID(), 
											 'winner' => $winner_id,
											 'method' => $method,
											 'endround' => $endround,
											 'endtime' => $endtime,
											 'source' => 'teams2']);

			if ($result == false)
			{
				$this->logger->error('Failed to store results', $block);
				return false;
			}
			$this->logger->info('Result stored');
			return $matching_fight->getID();
				
		}
		else
		{
			$this->logger->warning('No match found for ' . $temp_fight->getTeam(1) . ' ' . $result_text . ' ' . $temp_fight->getTeam(2));
			return false;
		}
	}



 	/*===========EVENT MATCHING BELOW=====*/

	private function checkEventHTML($event)
	{
		$found_unmatched = false;
		$found_matched = false;

		//If main part of name is in the prefix, just search directly for it
		$title = $event->getName();
		$potential_newtitle = explode(':', $event->getName())[0];
		if (is_numeric(substr($potential_newtitle, -1)))
		{
			$title = $potential_newtitle;
		}

		$page_content = $this->getHTMLPageFromWikipedia('intitle:' . $title . ' hastemplate:"Infobox MMA event"');

		if ($page_content == false)
		{
			$this->logger->error('No page found');
			return false;
		}

		//Remove \n
		$page_content=str_replace("\n","",$page_content);

        $html = new simple_html_dom();
        $html->load($page_content);

		//Find mixed martial arts record headline and grab the 2nd table after. TODO: Maybe make this find a bit more intelligent
        $node_array = $html->find('table.toccolours');
        if (!is_array($node_array))
        {
        	$this->logger->error('Could not find table.toccolours element');
        	return false;
        }

		//Fetch all matchups to keep track which ones we have matched and not
		$existing_db_matchups = EventHandler::getAllFightsForEvent($event->getID());
		$existing_matchups = [];
		foreach ($existing_db_matchups as $existing_db_matchup)
		{
			$existing_matchups[$existing_db_matchup->getID()] = false;
		}

        foreach ($node_array as $node)
        {
			//Traverse backwards and find infobox. If date matches, go ahead and parse
			$datenode = $node->previousSibling();
			while ($datenode->tag != 'table')
			{
				$datenode = $datenode->previousSibling();
			}
			$infobox_date = '';
			foreach ($datenode->find('tbody tr') as $infobox_row)
			{
				if (is_object($infobox_row->find('th',0)) && strtolower($infobox_row->find('th',0)->plaintext) == 'date')
				{
					$infobox_date = $infobox_row->find('td',0)->plaintext;
					$infobox_date = preg_replace('/\[[0-9]+\]/', '', $infobox_date);
					$infobox_date = html_entity_decode($infobox_date); 
				}
			}

			$check_date = null;
			$event_date = null;
			try
			{
				$check_date = new DateTime($infobox_date);
				$event_date = new DateTime($event->getDate());
			}
			catch (Exception $e)
			{
				$this->logger->warning('Invalid date format:' . $infobox_date);
				$date_found = false;
			}

			$date_found = true;
			if ($event_date != $check_date && $event_date->add(new DateInterval('P1D')) != $check_date && $event_date->sub(new DateInterval('P2D')) != $check_date)
			{
				$this->logger->warning('Date does not match. Aborting');
				$date_found = false;
			}

			if ($date_found == true)
			{
				//Loop through rows and fetch data. Pass it to checkFighterMatchupHTML
		       	foreach ($node->find("tbody tr") as $matchup_row) 
		        {
		        	$matchup_data = [];
		        	foreach ($matchup_row->find('td') as $matchup_cell)
		        	{
		        		$matchup_data[] = $matchup_cell->plaintext;
		        	}

		        	//Validate cells to verify that this is a proper matchup row
		        	if (count($matchup_data) == 8 &&
		        		is_numeric((int) $matchup_data[5]))
		        	{
						$result = $this->checkEventMatchupHTML($matchup_data, $event);
						if ($result != false)
						{
							$existing_matchups[$result] = true;
							$found_matched = true;
						}
						else
						{
							$found_unmatched = true;
						}
		        	}
		        }
		    }
        }

		//Check which matchups thare weren't found for some reason. If no checks above returned false then these should probably be cleaned as Cancalled bouts
		foreach ($existing_matchups as $key => $val)
		{
			if ($found_matched == true && $found_unmatched == false && $val == false)
			{
				$matchup = EventHandler::getFightByID($key);
				$this->logger->info('Matchup ' . $key . '(' . $matchup->getTeamAsString(1) . ' vs. ' . $matchup->getTeamAsString(2) . ') was not found on wiki page. Should probably be cleared as Cancelled');
			}
		}

	}

	private function checkEventMatchupHTML($matchup, $event)
	{
		//Loop through fields and remove references (e.g. [4])
		for($y = 0; $y < count($matchup); $y++)
		{
			$matchup[0] = preg_replace('/\[[0-9]+\]/', '', $matchup[0]);
		}

		$temp_fight = new Fight(-1, ParseTools::formatName($matchup[1]), ParseTools::formatName($matchup[3]), $event->getID());

		$this->logger->info('Found that:  ' . $temp_fight->getTeam($temp_fight->hasOrderChanged() ? 2 : 1) . ' ' . $matchup[2] . ' ' . $temp_fight->getTeam($temp_fight->hasOrderChanged() ? 1 : 2));

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

			$method = $this->getGenericWinningMethods($matchup[4]);
			//Add checks to convert method to a std format
			$endround = trim($matchup[5]); //Validate numeric
			$endtime = trim($matchup[6]); // Validate time format (X:XX)

			$result = EventHandler::addMatchupResults(['matchup_id' => $matching_fight->getID(), 
											 'winner' => $winner_id,
											 'method' => $method,
											 'endround' => $endround,
											 'endtime' => $endtime,
											 'source' => 'events2']);

			if ($result == false)
			{
				$this->logger->error('Failed to store results', $block);
				return false;
			}
			$this->logger->info('Result stored');
			return $matching_fight->getID();
				
		}
		else
		{
			$this->logger->warning('No match found for ' . $temp_fight->getTeam(1) . ' ' . $matchup[2] . ' ' . $temp_fight->getTeam(2));
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