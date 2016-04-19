<?php

require_once('lib/bfospec/results/class.ResultsParserTools.php');

class EventPageParser
{
	private $list_titles;
	private $logger;

	public function __construct($logger) 
	{
		$this->logger = $logger;
	}

	public function startParseEventResults()
	{
		//Retrieve all events that have missing reults
		$events = EventHandler::getAllEventsWithMatchupsWithoutResults();
		$this->logger->info('Parsing events start');
		foreach ($events as $event)
		{
			$this->logger->info('Checking: ' . $event->getName());
			$page_content = $this->freeSearchEvent($event->getName());
			$this->checkEventHTML($event, $page_content);
		}
		$this->logger->info('Parsing events end');
	}


	/* Method 1: Lists */


	public function checkEventLists()
	{
		$rpt = new ResultParserTools($this->logger);
		$titles = $this->getAllEventLists();

		$events = EventHandler::getAllEventsWithMatchupsWithoutResults();
		shuffle($events);
		$list_events = [];
		foreach ($titles as $url)
		{
			$list_events[] = $this->getEventsFromList($url);
		}

		foreach ($events as $event)
		{
			$this->logger->info('Checking: ' . $event->getName());
			foreach ($list_events as $list_event)
			{
				$key = array_search($event->getDate(), array_column($list_event, 'event_date'));

				//If date matched, check if same organization organized the event
				//Some manual mappings
				if ($key != false && strpos($list_event[$key]['event_name'], 'The Ultimate Fighter') !== false) 
				{
				    $list_event[$key]['event_name'] = 'UFC ' . $list_event[$key]['event_name'];
				}
				if (substr($event->getName(),0,4) == 'UFC:')
				{
					$event->setName('UFC' . substr($event->getName(),4));
				}

				$new_matches = null;
				$stored_matches = null;
				if ($key != false)
				{
					preg_match('/^([a-zA-Z-]+)[\s:]/', $event->getName(), $stored_matches);
					preg_match('/^([a-zA-Z-]+)[\s:]/', $list_event[$key]['event_name'], $new_matches);
					$this->logger->info('--compare: ' . $event->getName() . '(' . $event->getDate() . ') and ' . $list_event[$key]['event_name'] . '(' . $list_event[$key]['event_date'] . ')');
				}

				if ($key != false && (strtolower($stored_matches[1]) == strtolower($new_matches[1])))
				{
		        	$this->logger->error('Found match: ' . $event->getName() . ' found in ' . $list_event[$key]['event_name']);
					$this->checkEventHTML($event, $rpt->getHTMLPageFromWikipedia($list_event[$key]['event_page_title']), true);
				}
			}
		}
	}

	public function getAllEventLists()
	{
		$rpt = new ResultParserTools($this->logger);
		$content = $rpt->fetchPage('https://en.wikipedia.org/w/api.php?action=query&list=categorymembers&cmtitle=Category:Mixed_martial_arts_events_lists&cmprop=title&cmlimit=500&format=json');
		$json = json_decode($content);
		$titles = [];
		foreach ($json->query->categorymembers as $member)
		{
			$titles[] = $member->title;
		}
		//return ['List of Bellator events'];
		return $titles;
	}

	public function getEventsFromList($url)
	{
		$rpt = new ResultParserTools($this->logger);
		$content = $rpt->getHTMLPageFromWikipedia($url);
		$ret_events = [];
		$html = new simple_html_dom();
        $html->load($content);

		foreach ($html->find('tr') as $tr_node)
		{
			$td_nodes = $tr_node->find('td');
			if (count($td_nodes) > 2)
			{
				//Validate format of date and check if href is set
				$check_date = null;	
				if (preg_match("/((January|February|March|April|May|June|July|August|September|October|November|December|Jan|Feb|Mar|Apr|Jun|Jul|Aug|Sep|Oct|Nov|Dec) \d{1,2},\s?\d{4})/", $td_nodes[2]->plaintext, $datematches) && isset($td_nodes[1]->find('a',0)->href) )
				{
					$date_obj = new DateTime($datematches[1]);
					$ret_events[] = ['event_name' => $td_nodes[1]->plaintext ,'event_page_title' => substr($td_nodes[1]->find('a',0)->href,6), 'event_date' => $date_obj->format('Y-m-d')];

				}
			}
		}
		return $ret_events;
	}

	/* Method 2: Free search */

	public function freeSearchEvent($title)
	{
		$rpt = new ResultParserTools($this->logger);

		//If main part of name is in the prefix, just search directly for it
		$potential_newtitle = explode(':', $title)[0];
		if (is_numeric(substr($potential_newtitle, -1)))
		{
			$title = $potential_newtitle;
		}

		$searchtitle = $this->searchWikipediaForTitle('intitle:' . $title . ' hastemplate:"Infobox MMA event"');
		$page_content = $rpt->getHTMLPageFromWikipedia($searchtitle);

		if ($page_content == false)
		{
			$this->logger->error('No page found');
			return false;
		}
		return $page_content;
	}

	/* Shared */

	public function checkEventHTML($event, $page_content, $date_prechecked = false)
	{
		$this->logger->error('Start parse page (' . strlen($page_content) . ')');
		$rpt = new ResultParserTools($this->logger);

		$found_unmatched = false;
		$found_matched = false;

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
        	$date_found = false;
        	if ($date_prechecked == false || count($node_array) > 1)
        	{

				//Traverse backwards and find infobox. If date matches, go ahead and parse
				$datenode = $node->previousSibling();
				while ($datenode->tag != 'table')
				{
					if ($datenode == null)
					{
						break;
					}
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
			}
			else if ($date_prechecked == true && count($node_array) == 1)
			{
				$this->logger->info('Date prematched and node array is 1');
				$date_found = true;
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
		        	if (preg_match('/[0-9]{1,2}:[0-9]{1,2}/', $matchup_data[6]) &&
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
		$html->clear();

	}

	public function checkEventMatchupHTML($matchup, $event)
	{
		$rpt = new ResultParserTools($this->logger);

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

			$method = $rpt->getGenericWinningMethods($matchup[4]);
			//If method is draw or NC, set winner_id to -1
			if ($method == 'draw' || $method == 'nc')
			{
				$winner_id = -1;
			}

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
				$this->logger->error('Failed to store results');
				return false;
			}
			$this->logger->info('Result stored' . $matching_fight->getID());
			return $matching_fight->getID();
				
		}
		else
		{
			$this->logger->warning('No match found for ' . $temp_fight->getTeam(1) . ' ' . $matchup[2] . ' ' . $temp_fight->getTeam(2));
		}
	}

	public function getListPage($urls)
	{

	}
}

?>