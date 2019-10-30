<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');

/**
 * XML Parser
 *
 * Bookie: Ladbrokes
 * Sport: Boxing
 *
 * Comment: In production
 *
 */
class XMLParserLadbrokes
{
    private $oParsedSport;
    private $bAuthorativeRun = false;

    public function __construct()
    {
        $this->oParsedSport = new ParsedSport('Boxing');
    }

    public function parseXML($a_sJSON)
    {
        //Input is actually JSON. Traverse JSON and fetch all events
        $oPage = json_decode($a_sJSON);

        if (isset($oPage->apiError))
        {
            Logger::getInstance()->log("Error in response: " . $oPage->apiError->responseStatusCode . ": " . $oPage->apiError->errorCode . ": " . $oPage->apiError->errorDescription, -1);
            return $this->oParsedSport;
        }

        $aURLs = [];
        //When looping through the json, we need to check if collections are actually a single value, and if so convert it to a single element array
        $oPage->classes->class->types->type = is_array($oPage->classes->class->types->type) ? $oPage->classes->class->types->type : [$oPage->classes->class->types->type];
        foreach ($oPage->classes->class->types->type as $oType)
        {
            $oType->subtypes->subtype = is_array($oType->subtypes->subtype) ? $oType->subtypes->subtype : [$oType->subtypes->subtype];
            foreach ($oType->subtypes->subtype as $oSubType)
            {
                $oSubType->events->event = is_array($oSubType->events->event) ? $oSubType->events->event : [$oSubType->events->event];
                foreach ($oSubType->events->event as $oEvent)
                {
                    $aURLs[] = "https://api.ladbrokes.com/v2/sportsbook-api/classes/null/types/null/subtypes/null/events/" . $oEvent->eventKey . "?locale=en-GB&api-key=LADe7bbeb07982e4438b00f6479fcdc241c&expand=selection";
                    Logger::getInstance()->log("Preparing fetch of " . end($aURLs), 0);
                }
            }
        }
        //Split retrieval into blocks of 5 due to 5TPS (5 Transactions Per Second limit) and then fetch them. We add a small workaround to limit first chunk to 4 elements since we have already fetched the main page        
        array_unshift($aURLs, 'blank');
        $aURLChunks = array_chunk($aURLs, 5);
        foreach ($aURLChunks as $aURLChunk)
        {
            usleep(1050000); //Sleep 1.05 seconds
            if ($aURLChunk[0] == 'blank')
            {
                array_shift($aURLChunk);
            }
            ParseTools::retrieveMultiplePagesFromURLs($aURLChunk);
        }
        array_shift($aURLs); //Remove 'blank'

        $bErrorsFound = false;
        foreach ($aURLs as $sURL)
        {
            $bResult = self::processSingleEvent(ParseTools::getStoredContentForURL($sURL));
            if ($bResult == false)
            {
                $bErrorsFound = true;
            }
        }

        //Declare authorative run if we fill the criteria
        if (!$bErrorsFound && count($aURLs) >= 10 && count($this->oParsedSport->getParsedMatchups()) >= 10 && $this->oParsedSport->getPropCount() >= 10)
        {
            $this->bAuthorativeRun = true;
            Logger::getInstance()->log("Declared authoritive run", 0);
        }

        return [$this->oParsedSport];
    }

    private function processSingleEvent($a_sJSON)
    {
        $oJSON = json_decode($a_sJSON);
        if (isset($oJSON->apiError))
        {
            Logger::getInstance()->log("Error in response: " . $oJSON->apiError->responseStatusCode . ": " . $oJSON->apiError->errorCode . ": " . $oJSON->apiError->errorDescription, -1);
            return false;
        }
        
        if (!is_array($oJSON->event->markets->market))
        {
            $oJSON->event->markets->market = [$oJSON->event->markets->market];
        }
        foreach ($oJSON->event->markets->market as $oMarket)
        {
            switch ($oMarket->marketName)
            {
                case 'Fight result':
                case "Fight Betting":
                    //Regular matchup
                    $aParticipants = [];
                    foreach ($oMarket->selections->selection as $oSelection)
                    {
                        //Find draw and ignore it
                        if (strtolower(substr($oSelection->selectionName,0,4)) != 'draw')
                        {
                            $aParticipants[] = $oSelection;
                        }
                    }

                    $oParsedMatchup = new ParsedMatchup(
                        $aParticipants[0]->selectionName,
                        $aParticipants[1]->selectionName,
                        OddsTools::convertDecimalToMoneyline((float) $aParticipants[0]->currentPrice->decimalPrice),
                        OddsTools::convertDecimalToMoneyline((float) $aParticipants[1]->currentPrice->decimalPrice));
                    $oGameDate = new DateTime($oJSON->event->eventDateTime);
                    $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                    $oParsedMatchup->setCorrelationID($oJSON->event->eventKey);
                    $this->oParsedSport->addParsedMatchup($oParsedMatchup);
                break;

                case 'Fight to go the distance':
                case substr($oMarket->marketName,0,12) == 'Total Rounds':
                    //Two option prop
                    $oParsedProp = new ParsedProp(
                                  $oJSON->event->eventName . ' : ' . $oMarket->selections->selection[0]->selectionName,
                                  $oJSON->event->eventName . ' : ' . $oMarket->selections->selection[1]->selectionName,
                                    OddsTools::convertDecimalToMoneyline((float) $oMarket->selections->selection[0]->currentPrice->decimalPrice),
                                    OddsTools::convertDecimalToMoneyline((float) $oMarket->selections->selection[1]->currentPrice->decimalPrice));
                 
                    $oParsedProp->setCorrelationID($oJSON->event->eventKey);
                    $this->oParsedSport->addFetchedProp($oParsedProp);
                break;
                
                default:
                    //One side prop (vs. any other result)
                    foreach ($oMarket->selections->selection as $oSelection)
                    {
                        $oParsedProp = new ParsedProp(
                              $oJSON->event->eventName . ' : ' . $oSelection->selectionName,
                              '',
                                OddsTools::convertDecimalToMoneyline((float) $oSelection->currentPrice->decimalPrice),
                               '-99999');
                 
                        $oParsedProp->setCorrelationID($oJSON->event->eventKey);
                        $this->oParsedSport->addFetchedProp($oParsedProp);
                    }

            }
        }
        return true;
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->bAuthorativeRun;
    }
}
?>

