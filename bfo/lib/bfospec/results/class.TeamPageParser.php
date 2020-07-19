<?php

require_once('lib/bfospec/results/class.ResultsParserTools.php');

class TeamPageParser
{
	private $logger;

	public function __construct($logger) 
	{
		$this->logger = $logger;
	}

	public function startParseTeamResults()
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

	public function checkFighterPageHTML($fighter)
	{
		$rpt = new ResultParserTools($this->logger);

		$found_unmatched = false;
		$found_matched = false;
		$searchtitle = $rpt->searchWikipediaForTitle($fighter->getNameAsString() . ' hastemplate:"Infobox martial artist" hastemplate:"MMArecordbox"');
		$page_content = $rpt->getHTMLPageFromWikipedia($searchtitle);
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
        	$html->clear();
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
        	$html->clear();
        	return false;
		}
        $matchups = $node->nextSibling()->nextSibling();
        if (!isset($matchups->plaintext))
        {
        	$this->logger->error('Could not find matchups in HTML');
        	$html->clear();
        	return false;
        }
        
		//Fetch all matchups to keep track which ones we have matched and not
		$existing_db_matchups = EventHandler::getAllFightsForFighter($fighter->getID());
		//Filter out future matchups
		foreach ($existing_db_matchups as $key => $val)
		{
			if ($val->isFuture())
			{
				unset($existing_db_matchups[$key]);
			}
		} 
		$existing_db_matchups = array_values($existing_db_matchups);

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
	        		$html->clear();
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
				$event = EventHandler::getEvent($matchup->getEventID());
				$this->logger->info('Matchup ' . $key . ' - ' . $matchup->getTeamAsString(1) . ' vs. ' . $matchup->getTeamAsString(2) . ' at ' . $event->getName() . ' was not found on wiki page. Should probably be cleared as Cancelled');
			}
		}
		$html->clear();
	}

	public function checkFighterMatchupHTML($matchup, $fighter)
	{
		$rpt = new ResultParserTools($this->logger);

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

			$method = $rpt->getGenericWinningMethods($matchup[3])
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
				$this->logger->error('Failed to store results');
				return false;
			}
			$this->logger->info('Result stored' . $matching_fight->getID());
			return $matching_fight->getID();
				
		}
		else
		{
			$this->logger->warning('No match found for ' . $temp_fight->getTeam(1) . ' ' . $result_text . ' ' . $temp_fight->getTeam(2));
			return false;
		}
	}
}

?>