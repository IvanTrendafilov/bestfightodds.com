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