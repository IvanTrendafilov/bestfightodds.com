<?php

require 'vendor/autoload.php';

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.TeamHandler.php');
require_once('lib/bfocore/general/class.FighterHandler.php');
require_once('lib/bfocore/parser/utils/class.ParseTools.php');
require_once('config/inc.parseConfig.php');
require_once('lib/simple_html_dom/simple_html_dom.php');

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

		//Retrieve all teams that have missing reults
		$teams = TeamHandler::getAllTeamsWithMissingResults();
		$this->logger->info('Parsing teams start');
		foreach ($teams as $team)
		{
			$ounte++;
			$this->logger->info('Checking: ' . $team->getName());
			$this->checkFighterPageHTML($team);
		}
		$this->logger->info('Parsing teams end');

		//Retrieve all events that have missing reults
		/*$events = EventHandler::getAllEventsWithMatchupsWithoutResults();
		$this->logger->info('Parsing events start');
		foreach ($events as $event)
		{
			$ounte++;
			$this->logger->info('Checking: ' . $event->getName());
			$this->checkEvent($event);
		}
		$this->logger->info('Parsing events end');*/

		$this->logger->info('ResultParser end');
	}

	private function checkFighterPage($fighter)
	{
		$found_unmatched = false;
		$found_matched = false;
		$page_content = $this->getPageFromWikipedia($fighter->getNameAsString() . ' hastemplate:"Infobox martial artist" hastemplate:"MMArecordbox"');
		
		//Remove \n
		$page_content=str_replace("\n","",$page_content);

		if (strstr($page_content, '{{MMA record start}}') === false)
		{
			$this->logger->warning('Missing MMA record start. Probably not a fighter page. Aborting');	
			return false;
		}

		//Fetch all matchups to keep track which ones we have matched and not
		$existing_db_matchups = EventHandler::getAllFightsForFighter($fighter->getID());
		$existing_matchups = [];
		foreach ($existing_db_matchups as $existing_db_matchup)
		{
			$existing_matchups[$existing_db_matchup->getID()] = false;
		}

		//Strip everything prior to mma record to avoid parsing 
		$matches = null;
		preg_match("/{{MMA record start}}(.+){{end}}/s", $page_content, $matches);
		$page_content = $matches[0];

		//Pick out all the fights ont he page using regexp magic
		$blocks = ParseTools::matchBlock($page_content, '/\|\-.+?(?=\|\-|{{end}})/');
		foreach ($blocks as $block)
		{
			$result = $this->checkFighterMatchup($block[0], $fighter);
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
		//If main part of name is in the prefix, just search directly for it
		$potential_newtitle = explode(':', $title)[0];
		if (is_numeric(substr($potential_newtitle, -1)))
		{
			$title = $potential_newtitle;
		}

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

	private function checkFighterMatchup($matchup, $fighter)
	{
		//Remove [[ ]] and multiple name alternatives (e.g. [[Frank Edgar|Frankie Edgar]])
		$matchup = preg_replace('/\[\[([^\]\|]+)(\|[^\]]+)*|\]\]/', '$1', $matchup);

		//Parse dts date format
		$matches = null;
		preg_match("/{{dts\|[^}]*(\d{4})\|([a-zA-Z]+)\|(\d{1,2})[^}]*}}/", $matchup, $matches);
		$check_date = null;
		try 
		{
			$check_date = new DateTime ($matches[3] . ' ' . $matches[2]  . ' ' . $matches[1]); //TODO: Validation
		}
		catch (Exception $e)
		{
			$this->logger->warning('Invalid date format: ' . $matches[3] . ' ' . $matches[2]  . ' ' . $matches[1]);	
			return false;
		}
		//Clear all strings containing {{ }} around them
		$matchup = preg_replace('/{{[^}]*}}/', '', $matchup);
		
		$fields = explode('|', $matchup);
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
				case 'draw':
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
											 'endtime' => $endtime,
											 'source' => 'teams']);

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

			$method = $this->getGenericWinningMethods($matchup[3]);
			//Add checks to convert method to a std format
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


	private function checkEvent($event)
	{
		$found_unmatched = false;
		$found_matched = false;
		$page_content = $this->getPageFromWikipedia('intitle:' . $event->getName() . ' hastemplate:"Infobox MMA event"');

		if (strstr($page_content, '{{MMAevent}}') === false)
		{
			$this->logger->warning('Missing MMAevent start. Probably not an event page. Aborting');	
			return false;
		}

		//Validate that date matches from the infobox on the page
		$matches = null;
		preg_match("/{{Infobox MMA event(.+)}}/s", $page_content, $matches);
		$datematches = null;
		preg_match("/date\s{0,1}=\s{0,1}([a-zA-Z\d]+ [a-zA-Z\d,]+ \d{4})\\n/", $matches[0], $datematches);
		if (empty($datematches))
		{
			preg_match("/\|(\d{4}\|\d{2}\|\d{2})}}/", $matches[0], $datematches);
			if (empty($datematches))
			{
				$this->logger->warning('Date not parsed. Aborting');
				return false;
			}
			$datematches[1] = str_replace('|','-', $datematches[1]);
		}
		$check_date = new DateTime($datematches[1]);
		$event_date = new DateTime($event->getDate());
		if ($event_date != $check_date && $event_date->add(new DateInterval('P1D')) != $check_date && $event_date->sub(new DateInterval('P2D')) != $check_date)
		{
			$this->logger->warning('Date does not match. Aborting');
			return false;
		}

		//Fetch all matchups to keep track which ones we have matched and not
		$existing_db_matchups = EventHandler::getAllFightsForEvent($event->getID());
		$existing_matchups = [];
		foreach ($existing_db_matchups as $existing_db_matchup)
		{
			$existing_matchups[$existing_db_matchup->getID()] = false;
		}

		//Pick out all the fights ont he page using regexp magic
		$blocks = ParseTools::matchBlock($page_content, '{{MMAevent bout[^\}]+}}');
		foreach ($blocks as $block)
		{
			$result = $this->checkEventMatchup($block[0], $event);
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
											 'endtime' => $endtime,
											 'source' => 'events']);

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