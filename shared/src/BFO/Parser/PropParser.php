<?php

namespace BFO\Parser;

use BFO\General\BookieHandler;
use BFO\General\OddsHandler;
use BFO\Parser\Utils\Logger;
use BFO\Parser\Utils\ParseTools;
use BFO\General\EventHandler;
use BFO\General\TeamHandler;
use BFO\DataTypes\PropBet;
use BFO\DataTypes\EventPropBet;
use BFO\DataTypes\Fight;

class PropParser
{
    private $oLogger;
    private $aMatchups;
    private $aEvents;
    private $aCachedTemplates;

    public function __construct()
    {
        $this->oLogger = Logger::getInstance();
        $this->aMatchups = EventHandler::getAllUpcomingMatchups(true);
        $this->aEvents = EventHandler::getAllUpcomingEvents();
        $this->aCachedTemplates = [];

        //We will need to check the alt names as well so for each upcoming matchup fetched , fetch the associated altnames for each team and add a new matchup using this
        $aNewMatchupList = $this->aMatchups;
        foreach ($this->aMatchups as $oMatchupToCheck) {
            $aNewMatchupList = array_merge($aNewMatchupList, $this->addAltNameMatchupsToMatchup($oMatchupToCheck));
        }
        $this->aMatchups = $aNewMatchupList;
    }

    public function parseProps($a_iBookieID, $a_aProps)
    {
        $iCounter = 0;
        $this->oLogger->log('Starting parsing of props', 2);

        foreach ($a_aProps as $oProp) {
            $this->oLogger->log('-Parsing: ' . $oProp->toString(), 2);
            if ($this->parseSingleProp($a_iBookieID, $oProp)) {
                $iCounter++;
            }
        }

        return $iCounter;
    }

    public function parseSingleProp($a_iBookieID, &$a_oProp)
    {
        //Get matching template for prop
        $oTemplate = $this->matchParsedPropToTemplate($a_iBookieID, $a_oProp);
        if ($oTemplate == null) {
            $this->oLogger->log('--No template found for ' . $a_oProp->toString() .
                ' [<a href="?p=addNewPropTemplate&inBookieID=' . $a_iBookieID . '&inTemplate=' . $a_oProp->getTeamName(1) . '&inNegTemplate=' . $a_oProp->getTeamName(2) . '">add</a>]', -1);
            EventHandler::logUnmatched($a_oProp->toString(), $a_iBookieID, 2);
            return false;
        }
        $this->oLogger->log('--Template found for ' . $a_oProp->toString() . ': ' . $oTemplate->toString(), 1);
        BookieHandler::updateTemplateLastUsed($oTemplate->getID());


        if ($oTemplate->isEventProp() == true) { //TODO: Check if prop_type is event only
            //---EVENT PROP---
            //Find a matching event for the prop
            $aResult = $this->matchParsedPropToEvent($a_oProp, $oTemplate);

            if ($aResult['event'] == null) {
                $this->oLogger->log('---No event found for prop values ' . $a_oProp->toString() . ' (Template ' . $oTemplate->getID() . ' expecting ft: ' . $oTemplate->getFieldsTypeAsExample() . ')' .
                    '', -1);
                EventHandler::logUnmatched($a_oProp->toString(), $a_iBookieID, 1);
                return false;
            }
            $this->oLogger->log('---We found a event match for prop values! (' . $aResult['event'] . ')', 2);

            $oNewProp = null;

            if ($oTemplate->isNegPrimary()) {
                //Create a EventPropBet object for storage
                $oNewProp = new EventPropBet(
                    $aResult['event'],
                    $a_iBookieID,
                    '',
                    $a_oProp->getMoneyline(($a_oProp->getMainProp() % 2) + 1),
                    '',
                    $a_oProp->getMoneyline($a_oProp->getMainProp()),
                    $oTemplate->getPropTypeID(),
                    ''
                );
            } else {
                //Create a EventPropBet object for storage
                $oNewProp = new EventPropBet(
                    $aResult['event'],
                    $a_iBookieID,
                    '',
                    $a_oProp->getMoneyline($a_oProp->getMainProp()),
                    '',
                    $a_oProp->getMoneyline(($a_oProp->getMainProp() % 2) + 1),
                    $oTemplate->getPropTypeID(),
                    ''
                );
            }

            //Store prop bet if it has changed
            if (OddsHandler::checkMatchingEventPropOdds($oNewProp)) {
                $this->oLogger->log("------- nothing has changed since last event prop odds", 2);
                return true;
            } else {
                $this->oLogger->log("------- adding new event prop odds!", 2);
                if (OddsHandler::addEventPropBet($oNewProp)) {
                    return true;
                }

                $this->oLogger->log('----Prop not stored properly: ' . var_export($oNewProp, true) . '', -2);
                return false;
            }
        } else {
            //---MATCHUP PROP---

            //Find a matching matchup for the prop
            $aResult = $this->matchParsedPropToMatchup($a_oProp, $oTemplate);

            //If the prop had a correlation ID but still no match was found, try again without correlation ID
            if ($aResult['matchup'] == null && $a_oProp->getCorrelationID() != '') {
                $this->oLogger->log('--Matchup has correlation ID from bookie but no match. Checking all matchups', 0);
                $a_oProp->setCorrelationID('');
                $aResult = $this->matchParsedPropToMatchup($a_oProp, $oTemplate);
            }

            if ($aResult['matchup'] == null) {
                $this->oLogger->log('---No matchup found for prop values ' . $a_oProp->toString() . ' (Template ' . $oTemplate->getID() . ' expecting ft: ' . $oTemplate->getFieldsTypeAsExample() . ')' .
                    ' [<a href="?p=addManualPropCorrelation&inBookieID=' . $a_iBookieID . '&inCorrelation=' . ($a_oProp->getMainProp() == 1 ? $a_oProp->getTeamName(1) : $a_oProp->getTeamName(2)) . '">link manually</a>]', -1);
                EventHandler::logUnmatched($a_oProp->toString(), $a_iBookieID, 1);
                return false;
            }
            $this->oLogger->log('---We found a match for prop values! (' . $aResult['matchup'] . ')', 2);
            $a_oProp->setMatchedMatchupID($aResult['matchup']);

            //Check if the specific bookie has normal odds (non-prop) for the fight, if not, cancel the matching since no bookie generally has props but no normal odds
            if (EventHandler::getLatestOddsForFightAndBookie($a_oProp->getMatchedMatchupID(), $a_iBookieID) == null) {
                $this->oLogger->log('----Bookie does not have normal odds for matchup, bailing', -1);
                return false;
            }


            //If prop requires that team is specified, add this to the prop
            if ($aResult['team'] != null) {
                $a_oProp->setMatchedTeamNumber($aResult['team']);
            }

            $oNewProp = null;

            if ($oTemplate->isNegPrimary()) {
                //Create a PropBet object for storage
                $oNewProp = new PropBet(
                    $a_oProp->getMatchedMatchupID(),
                    $a_iBookieID,
                    '',
                    $a_oProp->getMoneyline(($a_oProp->getMainProp() % 2) + 1),
                    '',
                    $a_oProp->getMoneyline($a_oProp->getMainProp()),
                    $oTemplate->getPropTypeID(),
                    '',
                    $a_oProp->getMatchedTeamNumber()
                );
            } else {
                //Create a PropBet object for storage
                $oNewProp = new PropBet(
                    $a_oProp->getMatchedMatchupID(),
                    $a_iBookieID,
                    '',
                    $a_oProp->getMoneyline($a_oProp->getMainProp()),
                    '',
                    $a_oProp->getMoneyline(($a_oProp->getMainProp() % 2) + 1),
                    $oTemplate->getPropTypeID(),
                    '',
                    $a_oProp->getMatchedTeamNumber()
                );
            }

            //Store prop bet if it has changed
            if (OddsHandler::checkMatchingPropOdds($oNewProp)) {
                $this->oLogger->log("------- nothing has changed since last prop odds", 2);
                return true;
            } else {
                $this->oLogger->log("------- adding new prop odds!", 2);
                if (OddsHandler::addPropBet($oNewProp)) {
                    return true;
                }

                $this->oLogger->log('----Prop not stored properly: ' . var_export($oNewProp, true) . '', -2);
                return false;
            }
        }
    }

    public function matchParsedPropToTemplate($a_iBookieID, &$a_oProp)
    {
        //Fetch all templates for bookie if not done earlier
        $aTemplates = null;
        if (!isset($this->aCachedTemplates[$a_iBookieID])) {
            $this->aCachedTemplates[$a_iBookieID] = BookieHandler::getPropTemplatesForBookie($a_iBookieID);
        }
        $aTemplates = $this->aCachedTemplates[$a_iBookieID];

        //Ex: TEMPLATE =  <T>/<T> goes <*> round distance
        //Ex: ParsedProp = Howard/Alves goes 3 round distance

        $bFound = false;
        $oFoundTemplate = null;
        foreach ($aTemplates as $oTemplate) {
            $sTemplate = '';
            if ($oTemplate->isNegPrimary()) {
                $sTemplate = $oTemplate->getTemplateNeg();
            } else {
                $sTemplate = $oTemplate->getTemplate();
            }

            $sTemplate = strtoupper(str_replace('<T>', '([^:]+?)', $sTemplate));
            $sTemplate = str_replace('<*>', '.+?', $sTemplate);
            $sTemplate = str_replace('<.>', '.?', $sTemplate);
            $sTemplate = str_replace('<?>', '.*?', $sTemplate);
            $sTemplate = str_replace('/', '\/', $sTemplate);

            
            //Check the template against the prop
            $aPVMatches = array();
            $bFound1 = (preg_match('/^' . $sTemplate . '$/', $a_oProp->getTeamName(1), $aPVMatches1) > 0);
            $bFound2 = (preg_match('/^' . $sTemplate . '$/', $a_oProp->getTeamName(2), $aPVMatches2) > 0);
            if ($bFound1 && $bFound2) {
                $this->oLogger->log('--Both team fields match template. Picking shortest one as it is probably not the negative one: ' .  $a_oProp->getTeamName(1) . ' compared to ' .  $a_oProp->getTeamName(2), 0);
                //TEMPORARY HACK FOR PROPTYPE 65. REMOVE WHEN NO ODDS ARE LIVE FOR THIS TYPE
                if ($oTemplate->getPropTypeID() == 65) {
                    $this->oLogger->log('---Proptype 65, picking main prop 1 anyway. TODO: Remove this when no odds are live!', -1);
                    $a_oProp->setMainProp(1);
                    $aPVMatches = $aPVMatches1;
                } elseif (strlen($a_oProp->getTeamName(1)) <= strlen($a_oProp->getTeamName(2))) {
                    $this->oLogger->log('---Field 1 wins', 0);
                    $a_oProp->setMainProp(1);
                    $aPVMatches = $aPVMatches1;
                } else {
                    $this->oLogger->log('---Field 2 wins', 0);
                    $a_oProp->setMainProp(2);
                    $aPVMatches = $aPVMatches2;
                }
            } elseif ($bFound1) {
                //Found in team 1
                $a_oProp->setMainProp(1);
                $aPVMatches = $aPVMatches1;
            } elseif ($bFound2) {
                //Found in team 2
                $a_oProp->setMainProp(2);
                $aPVMatches = $aPVMatches2;
            }
            $bFound = ($bFound1 || $bFound2);



            if ($bFound == true) {
                //Check if value is already stored, if so we have multiple templates matching the prop which is not good
                if ($oFoundTemplate != null) {
                    $this->oLogger->log('--Warning: Multiple templates matched. Will accept longest one', 0);
                }
               
                //Replacing only template if the new one is longer (better match)
                if ($oFoundTemplate == null || strlen($oTemplate->getTemplate()) > strlen($oFoundTemplate->getTemplate())) {
                    //Remove the first element in the array since that contains the full regexp match
                    array_shift($aPVMatches);

                    //Check if no prop values were fetched, this will cause problems later on
                    if (count($aPVMatches) == 0) {
                        $this->oLogger->log('--No prop values fetched, make sure that no invalid prop templates exist');
                    } else {
                        //Store the prop values in the prop
                        $a_oProp->setPropValues($aPVMatches);
                    }
                    $oFoundTemplate = $oTemplate;
                }
            }
            $bFound = false;
        }
        return $oFoundTemplate;
    }

    public function matchParsedPropToMatchup($a_oProp, $a_oTemplate)
    {
        $aTemplateVariables = array();
        if ($a_oTemplate->isNegPrimary()) {
            $aTemplateVariables = $a_oTemplate->getNegPropVariables();
        } else {
            $aTemplateVariables = $a_oTemplate->getPropVariables();
        }

        //Check that prop and template have the same number of variables/values
        $aPropValues = $a_oProp->getPropValues();

        if (count($aPropValues) != count($aTemplateVariables)) {
            $this->oLogger->log('---Template variable count (' . count($aTemplateVariables) . ') does not match prop values count (' . count($aPropValues) . '). Not good, check template.', -2);
            return null;
        }

        $aParsedMatchup = $a_oProp->getPropValues();

        //Loop through the parsed prop values and determine fields type. Default is full name
        $iNewFT = self::determineFieldsType($aParsedMatchup);
        if ($iNewFT != false) {
            $a_oTemplate->setFieldsTypeID($iNewFT);
        }

        sort($aParsedMatchup);

        //Determine last name composition from parsed matchup (e.g. if last name would be Dos Santos or Santos for Junior Dos Santos)
        $bOnlyOneLastName = true;
        if (strpos($aParsedMatchup[0], ' ') !== false || (isset($aParsedMatchup[1]) && strpos($aParsedMatchup[1], ' ') !== false)) {
            $bOnlyOneLastName = false;
        }

        $oFoundMatchup = null;
        $iFoundMatchupID = null;
        $iFoundTeam = null; //Used if we need to find out which team the prop is for as well. Used when matching one single name
        $fFoundSim = 0; //Used to compare fsims if two matchups are found for the same prop

        $aMatchupsToCheck = $this->aMatchups;

        //Check if a manual correlation has been created and stored
        $sSearchProp = ($a_oProp->getMainProp() == 1 ? $a_oProp->getTeamName(1) : $a_oProp->getTeamName(2));
        $this->oLogger->log('--- searching for manual correlation: ' . $sSearchProp . ' for bookie ' . $a_oTemplate->getBookieID(), 1);
        $sFoundCorrMatchup = OddsHandler::getMatchupForCorrelation($a_oTemplate->getBookieID(), $sSearchProp);
        if ($sFoundCorrMatchup != null) {
            $this->oLogger->log('---- prop has manual correlation stored: ' . $sFoundCorrMatchup, 1);
            $oPreMatchedMatchup = EventHandler::getFightByID($sFoundCorrMatchup);
            if ($oPreMatchedMatchup != null) {
                $this->oLogger->log('----- found stored manual correlation for ' . $sSearchProp . ' : ' . $oPreMatchedMatchup->getID(), 2);
                $aMatchupsToCheck = array($oPreMatchedMatchup);
                //Even though we have prematched the matchup, we still need to add alt name matchups
                $aMatchupsToCheck = array_merge($aMatchupsToCheck, $this->addAltNameMatchupsToMatchup($oPreMatchedMatchup));
            }
        } else {
            $this->oLogger->log('---- no stored manual correlation found', 1);

            //Default is we search all upcoming matchups, but if there is a matchup
            //pre-matched already, we'll just check that
            if ($a_oProp->getCorrelationID() != '') {
                $this->oLogger->log('--- prop has correlation id: ' . $a_oProp->getCorrelationID(), 1);
                $oPreMatchedMatchup = EventHandler::getFightByID(ParseTools::getCorrelation($a_oProp->getCorrelationID()));
                if ($oPreMatchedMatchup != null) {
                    $this->oLogger->log('--- found stored correlation: ' . $oPreMatchedMatchup->getID(), 2);
                    $aMatchupsToCheck = array($oPreMatchedMatchup);
                    //Even though we have prematched the matchup, we still need to add alt name matchups
                    $aMatchupsToCheck = array_merge($aMatchupsToCheck, $this->addAltNameMatchupsToMatchup($oPreMatchedMatchup));
                } else {
                    $this->oLogger->log('--- no stored correlation found (' . $a_oProp->getCorrelationID() . ')', 1);
                }
            }
        }

        //Apply a loop if we are checking a single name (need to check both fields of getTeam in matchup
        for ($iY = 1; $iY <= 2; $iY++) {
            foreach ($aMatchupsToCheck as $oMatchup) {
                $aStoredMatchup = null;
                switch ($a_oTemplate->getFieldsTypeID()) {
                    //1 lastname vs lastname (koscheck vs miller)
                    case 1:
                        $aStoredMatchup = array(ParseTools::getLastnameFromName($oMatchup->getTeam(1), $bOnlyOneLastName), ParseTools::getLastnameFromName($oMatchup->getTeam(2), $bOnlyOneLastName));
                        $iY = 3; //Increment Y since we do not need to run this more than once
                        break;
                    //2 fullname vs fullname (e.g josh koscheck vs dan miller)
                    case 2:
                        $aStoredMatchup = array($oMatchup->getTeam(1), $oMatchup->getTeam(2));
                        $iY = 3; //Increment Y since we do not need to run this more than once
                        break;
                    //3 single lastname (koscheck)
                    case 3:
                        $aStoredMatchup = array(ParseTools::getLastnameFromName($oMatchup->getTeam($iY), $bOnlyOneLastName));
                        break;
                    //4 full name (josh koscheck)
                    case 4:
                        $aStoredMatchup = array($oMatchup->getTeam($iY));
                        break;
                    //5 first letter.lastname (e.g. j.koscheck)
                    case 5:
                        $aInitials = ParseTools::getInitialsFromName($oMatchup->getTeam($iY));
                        $aStoredMatchup = array($aInitials[0] . '.' . ParseTools::getLastnameFromName($oMatchup->getTeam($iY), $bOnlyOneLastName));
                        break;
                    //6 first letter.lastname vs first letter.lastname (e.g. j.koscheck vs d.miller)
                    case 6:
                        $aInitials1 = ParseTools::getInitialsFromName($oMatchup->getTeam(1));
                        $aInitials2 = ParseTools::getInitialsFromName($oMatchup->getTeam(2));
                        $aStoredMatchup = array($aInitials1[0] . '.' . ParseTools::getLastnameFromName($oMatchup->getTeam(1), $bOnlyOneLastName), $aInitials2[0] . '.' . ParseTools::getLastnameFromName($oMatchup->getTeam(2), $bOnlyOneLastName));
                        $iY = 3; //Increment Y since we do not need to run this more than once
                        break;
                        
                    //7 first letter lastname vs first letter lastname (e.g. j koscheck vs d miller)
                    case 7:
                        $aInitials1 = ParseTools::getInitialsFromName($oMatchup->getTeam(1));
                        $aInitials2 = ParseTools::getInitialsFromName($oMatchup->getTeam(2));
                        $aStoredMatchup = array($aInitials1[0] . ' ' . ParseTools::getLastnameFromName($oMatchup->getTeam(1), $bOnlyOneLastName), $aInitials2[0] . ' ' . ParseTools::getLastnameFromName($oMatchup->getTeam(2), $bOnlyOneLastName));
                        $iY = 3; //Increment Y since we do not need to run this more than once
                        break;
                    //8 first letter lastname (e.g. j koscheck)
                    case 8:
                        $aInitials = ParseTools::getInitialsFromName($oMatchup->getTeam($iY));
                        $aStoredMatchup = array($aInitials[0] . ' ' . ParseTools::getLastnameFromName($oMatchup->getTeam($iY), $bOnlyOneLastName));
                        break;
                    default:
                        $this->oLogger->log('---Unknown fields type ID in PropTemplate: ' . $a_oTemplate->getFieldsTypeID(), -2);
                        return null;
                }

                sort($aStoredMatchup);

                //Compare the values fetched from the prop with the stored matchup
                $bFound = false;
                $fNewSim = 0;
                for ($iX = 0; $iX < count($aParsedMatchup); $iX++) {
                    if (($bFound == false && $iX == 0) || ($bFound == true && $iX > 0)) {
                        similar_text($aStoredMatchup[$iX], $aParsedMatchup[$iX], $fSim);

                        //DEBUG:
                        /*$this->oLogger->log("checking: " . $aStoredMatchup[$iX] . " vs " . $aParsedMatchup[$iX] . " fsim:" . $fSim, -2);
                        echo "checking: " . $aStoredMatchup[$iX] . " vs " . $aParsedMatchup[$iX] . "
                        fsim:" . $fSim;*/

                        if ($fSim > 87) {
                            $fNewSim = $fSim;
                            $bFound = true;
                        } else {
                            $bFound = false;
                        }
                    }
                }

                if ($bFound == true) {
                    //First check if the match we found is just an alt name match for the same matchup
                    if ($oFoundMatchup != null && $oFoundMatchup->getID() == $oMatchup->getID()) {
                        //In that case, do nothing
                    } elseif ($oFoundMatchup != null) {
                        $this->oLogger->log('---Found multiple matches for prop values. Comparing fsims, challenger: ' . $oMatchup->getTeamAsString(1) . ' vs ' . $oMatchup->getTeamAsString(2) . ' ' . $oMatchup->getID() . ' (' . $fNewSim . ') and current: ' . $oFoundMatchup->getTeamAsString(1) . ' vs ' . $oFoundMatchup->getTeamAsString(2) . ' ' . $oFoundMatchup->getID() . ' (' . $fFoundSim . ')', 0);
                        if ($fNewSim > $fFoundSim) {
                            $oFoundMatchup = $oMatchup;
                            $iFoundMatchupID = $oFoundMatchup->getID();
                            $fFoundSim = $fNewSim;
                            $this->oLogger->log('----Challenger won, changing matched to new one: ' . $iFoundMatchupID);
                            $iFoundTeam = ($iY == 3 ? '0' : $iY); //If Y = 3 then team is not relevant, set it to 0
                        } elseif ($fNewSim == $fFoundSim) {
                            $this->oLogger->log('----Fsims are identical, cannot determine winner. Bailing..');
                            return array('matchup' => null, 'team' => 0);
                        } else {
                            $this->oLogger->log('----Current won. Sticking with current');
                        }
                    } else {
                        $oFoundMatchup = $oMatchup;
                        $iFoundMatchupID = $oFoundMatchup->getID();
                        $fFoundSim = $fNewSim;
                        $iFoundTeam = ($iY == 3 ? '0' : $iY); //If Y = 3 then team is not relevant, set it to 0
                        //If matchup has switched due to altnames, change the relevant team
                        if ($oFoundMatchup->hasOrderChanged() == true) {
                            if ($iFoundTeam == 1) {
                                $iFoundTeam = 2;
                            } elseif ($iFoundTeam == 2) {
                                $iFoundTeam = 1;
                            }
                        }
                    }
                }
            }


            //Check if we found a match, then exit loop by setting Y. Kinda ugly..
            if ($bFound == true && $iFoundMatchupID != null) {
                $iY = 3;
            }
        }

        return array('matchup' => $iFoundMatchupID, 'team' => $iFoundTeam);
    }

    private function matchParsedPropToEvent($a_oProp, $a_oTemplate)
    {
        $aTemplateVariables = array();
        if ($a_oTemplate->isNegPrimary()) {
            $aTemplateVariables = $a_oTemplate->getNegPropVariables();
        } else {
            $aTemplateVariables = $a_oTemplate->getPropVariables();
        }

        //Check that prop and template have the same number of variables/values
        $aPropValues = $a_oProp->getPropValues();

        if (count($aPropValues) != count($aTemplateVariables)) {
            $this->oLogger->log('---Template variable count (' . count($aTemplateVariables) . ') does not match prop values count (' . count($aPropValues) . '). Not good, check template.', -2);
            return null;
        }

        $aParsedEvent = $a_oProp->getPropValues();
        sort($aParsedEvent);

        $oFoundEvent = null;
        $iFoundEventID = null;
        $fFoundSim = 0; //Used to compare fsims if two matchups are found for the same prop

        $aEventsToCheck = $this->aEvents;

        foreach ($aEventsToCheck as $oEvent) {
            //Compare the values fetched from the prop with the stored matchup
            $bFound = false;
            $fNewSim = 0;
            similar_text(strtoupper($oEvent->getName()), $aParsedEvent[0], $fSim1);
            similar_text(substr(strtoupper($oEvent->getName()), 0, strpos($oEvent->getName(), ':')), $aParsedEvent[0], $fSim2);
            $fSim = ($fSim1 > $fSim2 ? $fSim1 : $fSim2);

            //DEBUG:
            /*$this->oLogger->log("checking: " . $oEvent->getName() . " _and " . substr(strtoupper($oEvent->getName()), 0, strpos($oEvent->getName(), ':')) . " vs " . $aParsedEvent[0] . " fsim:" . $fSim, -2);
            echo "checking: " . $oEvent->getName() . " vs " . $aParsedEvent[0] . "
            fsim:" . $fSim;*/

            if ($fSim > 90) {
                $fNewSim = $fSim;
                $bFound = true;
            } else {
                $bFound = false;
            }

            if ($bFound == true) {
                if ($oFoundEvent != null) {
                    $this->oLogger->log('---Found multiple matches for prop values. Comparing fsims, challenger: ' . $oEvent->getName() . ' ' . $oEvent->getID() . ' (' . $fNewSim . ') and current: ' . $oFoundEvent->getName() . ' ' . $oFoundEvent->getID() . ' (' . $fFoundSim . ')', 0);
                    if ($fNewSim > $fFoundSim) {
                        $oFoundEvent = $oEvent;
                        $iFoundEventID = $oFoundEvent->getID();
                        $fFoundSim = $fNewSim;
                        $this->oLogger->log('----Challenger won, changing matched to new one: ' . $iFoundEventID);
                    } elseif ($fNewSim == $fFoundSim) {
                        $this->oLogger->log('----Fsims are identical, cannot determine winner. Bailing..');
                        return array('event' => null);
                    } else {
                        $this->oLogger->log('----Current won. Sticking with current');
                    }
                } else {
                    $oFoundEvent = $oEvent;
                    $iFoundEventID = $oFoundEvent->getID();
                    $fFoundSim = $fNewSim;
                }
            }
        }

        return array('event' => $iFoundEventID);
    }


    private function addAltNameMatchupsToMatchup($oMatchupToCheck)
    {
        $aAltMatchups = array();

        $aTeam1Alts = TeamHandler::getAllAltNamesForTeam($oMatchupToCheck->getTeam(1));
        $aTeam2Alts = TeamHandler::getAllAltNamesForTeam($oMatchupToCheck->getTeam(2));

        if ($aTeam1Alts != null) {
            foreach ($aTeam1Alts as $sAltName1) {
                $aAltMatchups[] = new Fight($oMatchupToCheck->getID(), $sAltName1, $oMatchupToCheck->getTeam(2), $oMatchupToCheck->getEventID(), $oMatchupToCheck->getComment());

                if ($aTeam2Alts != null) {
                    foreach ($aTeam2Alts as $sAltName2) {
                        $aAltMatchups[] = new Fight($oMatchupToCheck->getID(), $sAltName1, $sAltName2, $oMatchupToCheck->getEventID(), $oMatchupToCheck->getComment());
                    }
                }
            }
        }
        if ($aTeam2Alts != null) {
            foreach ($aTeam2Alts as $sAltName2) {
                $aAltMatchups[] = new Fight($oMatchupToCheck->getID(), $oMatchupToCheck->getTeam(1), $sAltName2, $oMatchupToCheck->getEventID(), $oMatchupToCheck->getComment());
            }
        }

        return $aAltMatchups;
    }

    private function determineFieldsType($aParsedMatchup)
    {
        $aFoundFieldsType = array();
        for ($iZ = 0; $iZ < count($aParsedMatchup); $iZ++) {
            if (strpos($aParsedMatchup[$iZ], " ") != true) {
                //Either Koscheck or J.Koscheck
                if (preg_match('/^[^\s]\.\s?[^\s]+$/', $aParsedMatchup[$iZ])) {
                    //Probably J.Koscheck
                    $aFoundFieldsType[$iZ] = (count($aParsedMatchup) > 1 ? 6 : 5);
                } else {
                    //Probably Koscheck
                    $aFoundFieldsType[$iZ] = (count($aParsedMatchup) > 1 ? 1 : 3);
                }
            } elseif (preg_match('/^[^\s] [^\n]+/', $aParsedMatchup[$iZ])) {
                //Probably J Koscheck
                $aFoundFieldsType[$iZ] = (count($aParsedMatchup) > 1 ? 7 : 8);
            } else {
                //Could be J. Koscheck
                if (preg_match('/^[^\s]\.\s[^\s]+$/', $aParsedMatchup[$iZ])) {
                    //Probably J. Koscheck (checks against J.Koscheck)
                    $aFoundFieldsType[$iZ] = (count($aParsedMatchup) > 1 ? 6 : 5);
                } else {
                    //Probably Josh Koschek
                
                    //Since we cant be sure if this is a double last name, we need to return false here for a single user..
                    if (count($aParsedMatchup) == 1) {
                        return false;
                    }

                    $aFoundFieldsType[$iZ] = (count($aParsedMatchup) > 1 ? 2 : 4);
                }
            }
        }
        //For multiple fields, compare and make sure we have only one type for both. If not return false
        if (count($aParsedMatchup) > 1) {
            if ($aFoundFieldsType[0] != $aFoundFieldsType[1]) {
                return false;
            }
        }
        return $aFoundFieldsType[0];
    }
}