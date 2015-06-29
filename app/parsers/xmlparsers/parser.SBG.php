<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');

/**
 * XML Parser
 *
 * Bookie: SBG
 * Sport: MMA
 *
 * Comment: Prod version
 *
 */
class XMLParserSBG
{
	public function parseXML($a_sXML)
	{
		$oXML = simplexml_load_string($a_sXML);
		
		if ($oXML == false)
		{
			Logger::getInstance()->log("Warning: XML broke!!", -1);
		}
		
		$aSports = array();
		
		$oParsedSport = new ParsedSport('MMA');
		
		foreach($oXML->sport as $cSport)
		{
			if (trim((string) $cSport->name) == 'Boxing')
			{
				foreach($cSport->leagues->league as $cLeague)
				{
					if (trim((string) $cLeague->name) == 'Mixed Martial Arts' || trim((string) $cLeague->name) == 'Ultimate Fighting' || trim((string) $cLeague->name) == 'Other Fighting Events' || trim((string) $cLeague->name) == 'MMA Golden Boy Affliction' || substr(trim((string) $cLeague->name),0,3) == 'MMA')
					{
						foreach($cLeague->games->game as $cGame)
						{
							if (ParseTools::checkCorrectOdds(trim((string) $cGame->lines->line[0]->mlOdds)) &&
								ParseTools::checkCorrectOdds(trim((string) $cGame->lines->line[1]->mlOdds)) &&
								trim((string) $cGame->status) != 'Close')
							{
														
								$oParsedMatchup = new ParsedMatchup(
									(string) $cGame->lines->line[0]->teamName,
									(string) $cGame->lines->line[1]->teamName,
									(string) $cGame->lines->line[0]->mlOdds,
									(string) $cGame->lines->line[1]->mlOdds
								);
									
								$oParsedSport->addParsedMatchup($oParsedMatchup);

		            		    //Check if a total is available, if so, add it as a prop. line[0] is always over and line[1] always under
		            		    if (isset($cGame->lines->line[0]->totalPoints) && 
		            		    	isset($cGame->lines->line[1]->totalPoints) && 
		            		    	isset($cGame->lines->line[0]->totalOdds) && 
		            		    	isset($cGame->lines->line[1]->totalOdds) && 
		            		    	trim((string) $cGame->lines->line[0]->totalPoints) != '' && 
		            		    	trim((string) $cGame->lines->line[1]->totalPoints) != '' &&
		            		    	trim((string) $cGame->lines->line[0]->totalPoints) != 'NA' && 
		            		    	trim((string) $cGame->lines->line[1]->totalPoints) != 'NA')
		                        {
		                            //Total exists, add it
		                            $oParsedProp = new ParsedProp(
		                                          (string) $cGame->lines->line[0]->teamName . ' vs ' . (string) $cGame->lines->line[1]->teamName . ' - OVER ' . (string) $cGame->lines->line[0]->totalPoints,
		                                          (string) $cGame->lines->line[0]->teamName . ' vs ' . (string) $cGame->lines->line[1]->teamName . ' - UNDER ' . (string) $cGame->lines->line[1]->totalPoints,
		                                          (string) $cGame->lines->line[0]->totalOdds,
		                                          (string) $cGame->lines->line[1]->totalOdds);
	                          
		                            $oParsedSport->addFetchedProp($oParsedProp);
		                        }

							}
						}
					}
				}
			}
		}
		
		$aSports[] = $oParsedSport;
		
		return $aSports;
	}
}

?>