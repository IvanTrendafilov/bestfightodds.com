<?php

require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/parser/general/class.ParsedProp.php');
require_once('lib/bfocore/parser/utils/class.Logger.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.TeamHandler.php');

class PropParserV2
{

    private $logger;
    private $matchups;
    private $events;
    private $bookie_id;

    public function __construct($logger, $bookie_id)
    {
        $this->logger = $logger;
        $this->bookie_id = $bookie_id;

        //Prefetch all upcoming matchups and events
        $this->matchups = EventHandler::getAllUpcomingMatchups(true);
        $this->events = EventHandler::getAllUpcomingEvents();

        //We will need to check the alt names as well so for each upcoming matchup fetched , fetch the associated altnames for each team and add a new matchup using this
        $aNewMatchupList = $this->matchups;
        foreach ($this->matchups as $oMatchupToCheck)
        {
            $aNewMatchupList = array_merge($aNewMatchupList, $this->addAltNameMatchupsToMatchup($oMatchupToCheck));
        }
        $this->matchups = $aNewMatchupList;
    }

    public function matchProps($props)
    {
        $result = [];
        foreach ($props as $prop)
        {
            $match = $this->matchSingleProp($prop);
            if ($match['status'] == true)
            {
                $result[] = ['prop' => $prop, 'match_result' => $match];
            }
            else
            {
                //Unmatched to either template, matchup or event. Log this both in log file and in database
                switch ($match['fail_reason'])
                {
                    case 'no_template_found':
                        $this->logger->warning('--No template found for ' . $prop->toString());
                        break;
                    case 'no_matchup_found':
                        $this->logger->warning('---No matchup found for prop values ' . $prop->toString() . ' (Template ' . $match['template']->getID() . ' expecting ft: ' . $match['template']->getFieldsTypeAsExample() . ')');
                        break;
                    case 'no_event_found':
                        $this->logger->warning('---No event found for prop values ' . $prop->toString() . ' (Template ' . $match['template']->getID() . ' expecting ft: ' . $match['template']->getFieldsTypeAsExample() . ')');
                        break;
                    default:
                }
                $result[] = ['prop' => $prop, 'match_result' => $match];
            }
        }
        return $result;
    }

    public function matchSingleProp($prop)
    {
        $template = $this->matchPropToTemplate($prop, $this->bookie_id);
        if (!$template)
        {
            //No matching template
            return ['status' => false, 'fail_reason' => 'no_template_found'];
        }
        BookieHandler::updateTemplateLastUsed($template->getID());

        $event = null;
        $matchup = null;
        if ($template->isEventProp())
        {
            $event = $this->matchPropToEvent($prop, $template);
            if (!$event)
            {
                //No matching event
                return ['status' => false, 'fail_reason' => 'no_event_found', 'template' => $template];
            }            
        }
        else
        {
            $matchup = $this->matchPropToMatchup($prop, $template, true); //Check first with correlation ID enabed
            if (!$matchup['matchup_id'])
            {
                $matchup = $this->matchPropToMatchup($prop, $template, false); //Check with correlation ID disabled
                if (!$matchup['matchup_id'])
                {
                    return ['status' => false, 'fail_reason' => 'no_matchup_found', 'template' => $template];
                }
            }            
        }
        return ['status' => true, 'template' => $template, 'matchup' => $matchup, 'event' => $event, 'matched_type' => ($template->isEventProp() ? 'event' : 'matchup')];
   }

    /** 
     * Finds a matching proptemplate for the specified prop and bookie
     * 
     * For example, matching the follow template to the follow prop:
     *   TEMPLATE =   <T>/<T> goes <*> round distance
     *   ParsedProp = Howard/Alves goes 3 round distance
     * 
     */
    public function matchPropToTemplate($prop, $bookie_id)
    {
        //Fetch all templates for bookie
        $templates = BookieHandler::getPropTemplatesForBookie($bookie_id);

        $bFound = false;
        $oFoundTemplate = null;
        foreach ($templates as $template)
        {
            $template_str = '';
            if ($template->isNegPrimary())
            {
                $template_str = $template->getTemplateNeg();
            }
            else
            {
                $template_str = $template->getTemplate();
            }

            //Convert template variables to equivalent regexps
            $template_str = strtoupper(str_replace('<T>', '([^:]+?)', $template_str));
            $template_str = str_replace('<*>', '.+?', $template_str);
            $template_str = str_replace('<.>', '.?', $template_str);
            $template_str = str_replace('<?>', '.*?', $template_str);
            $template_str = str_replace('/', '\/', $template_str);
            
            //Check the template against the prop
            $propvalue_matches = array();
            $found1 = (preg_match('/^' . $template_str . '$/', $prop->getTeamName(1), $propvalue_matches1) > 0);
            $found2 = (preg_match('/^' . $template_str . '$/', $prop->getTeamName(2), $propvalue_matches2) > 0);
            if ($found1 && $found2)
            {
                $this->logger->warning('--Both team fields match template. Picking shortest one as it is probably not the negative one: ' .  $prop->getTeamName(1) . ' compared to ' .  $prop->getTeamName(2));
                //TEMPORARY HACK FOR PROPTYPE 65. REMOVE WHEN NO ODDS ARE LIVE FOR THIS TYPE
                if ($template->getPropTypeID() == 65)
                {
                    $this->logger->warning('---Proptype 65, picking main prop 1 anyway. TODO: Remove this when no odds are live!');
                    $prop->setMainProp(1);
                    $propvalue_matches = $propvalue_matches1;
                }
                else if (strlen($prop->getTeamName(1)) <= strlen($prop->getTeamName(2)))
                {
                    $this->logger->debug('---Field 1 wins');
                    $prop->setMainProp(1);
                    $propvalue_matches = $propvalue_matches1;
                }
                else
                {
                    $this->logger->debug('---Field 2 wins');
                    $prop->setMainProp(2);
                    $propvalue_matches = $propvalue_matches2;
                }
            }
            else if ($found1)
            {
                //Found in team 1
                $prop->setMainProp(1);
                $propvalue_matches = $propvalue_matches1;
            }
            else if ($found2)
            {
                //Found in team 2
                $prop->setMainProp(2);
                $propvalue_matches = $propvalue_matches2;
            }
            $bFound = ($found1 || $found2);

            if ($bFound == true)
            {
                //Check if value is already stored, if so we have multiple templates matching the prop which is not good
                if ($oFoundTemplate != null)
                {
                    $this->logger->warning('--Multiple templates matched. Will accept longest one');
                }
            
                //Replacing only template if the new one is longer (better match)
                if ($oFoundTemplate == null || strlen($template->getTemplate()) > strlen($oFoundTemplate->getTemplate()))
                {
                    //Remove the first element in the array since that contains the full regexp match
                    array_shift($propvalue_matches);

                    //Check if no prop values were fetched, this will cause problems later on
                    if (count($propvalue_matches) == 0)
                    {
                        $this->logger->warning('--No prop values fetched, make sure that no invalid prop templates exist');
                    }
                    else
                    {
                        //Store the prop values in the prop
                        $prop->setPropValues($propvalue_matches);
                    }
                    $oFoundTemplate = $template;
                }
            
            }
            $bFound = false;
        }
        return $oFoundTemplate;
    }

    public function matchPropToMatchup($a_oProp, $a_oTemplate, $use_correlation_id = true)
    {

        $aTemplateVariables = array();
        if ($a_oTemplate->isNegPrimary())
        {
            $aTemplateVariables = $a_oTemplate->getNegPropVariables();
        }
        else
        {
            $aTemplateVariables = $a_oTemplate->getPropVariables();
        }

        //Check that prop and template have the same number of variables/values
        $aPropValues = $a_oProp->getPropValues();

        if (count($aPropValues) != count($aTemplateVariables))
        {

            $this->logger->error('---Template variable count (' . count($aTemplateVariables) . ') does not match prop values count (' . count($aPropValues) . '). Not good, check template.');
            return null;
        }

        $aParsedMatchup = $a_oProp->getPropValues();

        //Loop through the parsed prop values and determine fields type. Default is full name
        $iNewFT = self::determineFieldsType($aParsedMatchup);
        if ($iNewFT != false)
        {
            $a_oTemplate->setFieldsTypeID($iNewFT);
        }

        sort($aParsedMatchup);

        //Determine last name composition from parsed matchup (e.g. if last name would be Dos Santos or Santos for Junior Dos Santos)
        $bOnlyOneLastName = true;
        if (strpos($aParsedMatchup[0],' ') !== false || (isset($aParsedMatchup[1]) && strpos($aParsedMatchup[1],' ') !== false))
        {
            $bOnlyOneLastName = false;
        }

        $oFoundMatchup = null;
        $iFoundMatchupID = null;
        $iFoundTeam = null; //Used if we need to find out which team the prop is for as well. Used when matching one single name
        $fFoundSim = 0; //Used to compare fsims if two matchups are found for the same prop

        $aMatchupsToCheck = $this->matchups;

        if ($use_correlation_id == true)
        {
            //Check if a manual correlation has been created and stored
            $sSearchProp = ($a_oProp->getMainProp() == 1 ? $a_oProp->getTeamName(1) : $a_oProp->getTeamName(2));
            $this->logger->debug('--- searching for manual correlation: ' . $sSearchProp . ' for bookie ' . $a_oTemplate->getBookieID());
            $sFoundCorrMatchup = OddsHandler::getMatchupForCorrelation($a_oTemplate->getBookieID(), $sSearchProp);
            if ($sFoundCorrMatchup != null)
            {
                $this->logger->debug('---- prop has manual correlation stored: ' . $sFoundCorrMatchup);
                $oPreMatchedMatchup = EventHandler::getFightByID($sFoundCorrMatchup);
                if ($oPreMatchedMatchup != null)
                {
                    $this->logger->debug('----- found stored manual correlation for ' . $sSearchProp . ' : ' . $oPreMatchedMatchup->getID());
                    $aMatchupsToCheck = array($oPreMatchedMatchup);
                    //Even though we have prematched the matchup, we still need to add alt name matchups
                    $aMatchupsToCheck = array_merge($aMatchupsToCheck, $this->addAltNameMatchupsToMatchup($oPreMatchedMatchup));
                }
            }
            else
            {
                $this->logger->debug('---- no stored manual correlation found');

                //Default is we search all upcoming matchups, but if there is a matchup
                //pre-matched already, we'll just check that
                if ($a_oProp->getCorrelationID() != '')
                {
                    $this->logger->debug('--- prop has correlation id: ' . $a_oProp->getCorrelationID());
                    $oPreMatchedMatchup = EventHandler::getFightByID(ParseTools::getCorrelation($a_oProp->getCorrelationID()));
                    if ($oPreMatchedMatchup != null)
                    {
                        $this->logger->debug('--- found stored correlation: ' . $oPreMatchedMatchup->getID());
                        $aMatchupsToCheck = array($oPreMatchedMatchup);
                        //Even though we have prematched the matchup, we still need to add alt name matchups
                        $aMatchupsToCheck = array_merge($aMatchupsToCheck, $this->addAltNameMatchupsToMatchup($oPreMatchedMatchup));
                    }
                    else
                    {
                        $this->logger->debug('--- no stored correlation found (' . $a_oProp->getCorrelationID() . ')');
                    }
                }
            }
        }
        



        //TODO: New Flow to be implemented at some point:
        //1. Determine if this needs to matchup one or two names (existance of <T> in multiples or only one)
        //2. Get all name combinations
        //3. Run through all combinations and gather a list of fsim
        //4. Sort by fsim
        //5. If fsim > 87 and multiple, we'll log this but pick the top one. If equal (or maybe even very close), chose the one with longest name


        //Apply a loop if we are checking a single name (need to check both fields of getTeam in matchup
        for ($iY = 1; $iY <= 2; $iY++)
        {
            foreach ($aMatchupsToCheck as $oMatchup)
            {
                $aStoredMatchup = null;
                switch ($a_oTemplate->getFieldsTypeID())
                {
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
                        $this->logger->error('---Unknown fields type ID in PropTemplate: ' . $a_oTemplate->getFieldsTypeID());
                        return null;
                }

                sort($aStoredMatchup);

                //Compare the values fetched from the prop with the stored matchup
                $bFound = false;
                $fNewSim = 0;
                for ($iX = 0; $iX < count($aParsedMatchup); $iX++)
                {
                    if (($bFound == false && $iX == 0) || ($bFound == true && $iX > 0))
                    {
                        similar_text($aStoredMatchup[$iX], $aParsedMatchup[$iX], $fSim);
                        $this->logger->debug("Checking: " . $aStoredMatchup[$iX] . " vs " . $aParsedMatchup[$iX] . " fsim:" . $fSim);

                        if ($fSim > 87)
                        { 
                            $fNewSim = $fSim;
                            $bFound = true;
                        }
                        else
                        {
                            $bFound = false;
                        }
                    }
                }

                if ($bFound == true)
                {
                    //First check if the match we found is just an alt name match for the same matchup
                    if ($oFoundMatchup != null && $oFoundMatchup->getID() == $oMatchup->getID())
                    {
                        //In that case, do nothing
                    }
                    else if ($oFoundMatchup != null)
                    {
                        $this->logger->info('---Found multiple matches for prop values. Comparing fsims, challenger: ' . $oMatchup->getTeamAsString(1) . ' vs ' . $oMatchup->getTeamAsString(2) . ' ' . $oMatchup->getID() . ' (' . $fNewSim . ') and current: ' . $oFoundMatchup->getTeamAsString(1) . ' vs ' . $oFoundMatchup->getTeamAsString(2) . ' ' . $oFoundMatchup->getID() . ' (' . $fFoundSim . ')', 0);
                        if ($fNewSim > $fFoundSim)
                        {
                            $oFoundMatchup = $oMatchup;
                            $iFoundMatchupID = $oFoundMatchup->getID();
                            $fFoundSim = $fNewSim;
                            $this->logger->info('----Challenger won, changing matched to new one: ' . $iFoundMatchupID);
                            $iFoundTeam = ($iY == 3 ? '0' : $iY); //If Y = 3 then team is not relevant, set it to 0
                        }
                        else if ($fNewSim == $fFoundSim)
                        {
                            $this->logger->warning('----Fsims are identical, cannot determine winner. Bailing..');
                            return array('matchup' => null, 'team' => 0);
                        }
                        else
                        {
                            $this->logger->info('----Current won. Sticking with current');
                        }
                    }
                    else
                    {
                        $oFoundMatchup = $oMatchup;
                        $iFoundMatchupID = $oFoundMatchup->getID();
                        $fFoundSim = $fNewSim;
                        $iFoundTeam = ($iY == 3 ? '0' : $iY); //If Y = 3 then team is not relevant, set it to 0
                        //If matchup has switched due to altnames, change the relevant team
                        if ($oFoundMatchup->hasOrderChanged() == true)
                        {
                            if ($iFoundTeam == 1)
                            {
                                $iFoundTeam = 2;
                            }
                            else if ($iFoundTeam == 2)
                            {
                                $iFoundTeam = 1;
                            }
                        }
                    }
                }
            }

            //Check if we found a match, then exit loop by setting Y. Kinda ugly..
            if ($bFound == true && $iFoundMatchupID != null)
            {
                $iY = 3;
            }
        }

        return array('matchup_id' => $iFoundMatchupID, 'team' => $iFoundTeam);
    }

    private function matchPropToEvent($a_oProp, $a_oTemplate)
    {
        $aTemplateVariables = array();
        if ($a_oTemplate->isNegPrimary())
        {
            $aTemplateVariables = $a_oTemplate->getNegPropVariables();
        }
        else
        {
            $aTemplateVariables = $a_oTemplate->getPropVariables();
        }

        //Check that prop and template have the same number of variables/values
        $aPropValues = $a_oProp->getPropValues();

        if (count($aPropValues) != count($aTemplateVariables))
        {

            $this->logger->error('---Template variable count (' . count($aTemplateVariables) . ') does not match prop values count (' . count($aPropValues) . '). Not good, check template.');
            return null;
        }

        $aParsedEvent = $a_oProp->getPropValues();
        sort($aParsedEvent);

        $oFoundEvent = null;
        $iFoundEventID = null;
        $fFoundSim = 0; //Used to compare fsims if two matchups are found for the same prop

        foreach ($this->events as $oEvent)
        {
            //Compare the values fetched from the prop with the stored matchup
            $bFound = false;
            $fNewSim = 0;
            similar_text(strtoupper($oEvent->getName()), $aParsedEvent[0], $fSim1);
            similar_text(substr(strtoupper($oEvent->getName()), 0, strpos($oEvent->getName(), ':')), $aParsedEvent[0], $fSim2);
            $fSim = ($fSim1 > $fSim2 ? $fSim1 : $fSim2);

            //DEBUG:
            $this->logger->debug("Checking: " . $oEvent->getName() . " _and " . substr(strtoupper($oEvent->getName()), 0, strpos($oEvent->getName(), ':')) . " vs " . $aParsedEvent[0] . " fsim:" . $fSim);

            if ($fSim > 90)
            { 
                $fNewSim = $fSim;
                $bFound = true;
            }
            else
            {
                $bFound = false;
            }

            if ($bFound == true)
            {
                if ($oFoundEvent != null)
                {
                    $this->logger->log('---Found multiple matches for prop values. Comparing fsims, challenger: ' . $oEvent->getName() . ' ' . $oEvent->getID() . ' (' . $fNewSim . ') and current: ' . $oFoundEvent->getName() . ' ' . $oFoundEvent->getID() . ' (' . $fFoundSim . ')', 0);
                    if ($fNewSim > $fFoundSim)
                    {
                        $oFoundEvent = $oEvent;
                        $iFoundEventID = $oFoundEvent->getID();
                        $fFoundSim = $fNewSim;
                        $this->logger->log('----Challenger won, changing matched to new one: ' . $iFoundEventID);
                    }
                    else if ($fNewSim == $fFoundSim)
                    {
                        $this->logger->log('----Fsims are identical, cannot determine winner. Bailing..');
                        return array('event' => null);
                    }
                    else
                    {
                        $this->logger->log('----Current won. Sticking with current');
                    }
                }
                else
                {
                    $oFoundEvent = $oEvent;
                    $iFoundEventID = $oFoundEvent->getID();
                    $fFoundSim = $fNewSim;
                }
            }
        }

        return array('event_id' => $iFoundEventID);
    }

    private function addAltNameMatchupsToMatchup($oMatchupToCheck)
    {
        $new_matchups = array();

        $aTeam1Alts = TeamHandler::getAltNamesForTeamByID($oMatchupToCheck->getFighterID(1));
        $aTeam2Alts = TeamHandler::getAltNamesForTeamByID($oMatchupToCheck->getFighterID(2));

        if ($aTeam1Alts != null)
        {
            foreach ($aTeam1Alts as $sAltName1)
            {
                $new_matchups[] = new Fight($oMatchupToCheck->getID(), $sAltName1, $oMatchupToCheck->getTeam(2), $oMatchupToCheck->getEventID(), $oMatchupToCheck->getComment());

                if ($aTeam2Alts != null)
                {
                    foreach ($aTeam2Alts as $sAltName2)
                    {
                        $new_matchups[] = new Fight($oMatchupToCheck->getID(), $sAltName1, $sAltName2, $oMatchupToCheck->getEventID(), $oMatchupToCheck->getComment());
                    }
                }
            }
        }
        if ($aTeam2Alts != null)
        {
            foreach ($aTeam2Alts as $sAltName2)
            {
                $new_matchups[] = new Fight($oMatchupToCheck->getID(), $oMatchupToCheck->getTeam(1), $sAltName2, $oMatchupToCheck->getEventID(), $oMatchupToCheck->getComment());
            }
        }

        return $new_matchups;
    }

    private function determineFieldsType($aParsedMatchup)
    {
        $aFoundFieldsType = array();
        for ($iZ = 0; $iZ < count($aParsedMatchup); $iZ++)
        {
            if (strpos($aParsedMatchup[$iZ], " ") != true)
            {
                //Either Koscheck or J.Koscheck
                if (preg_match('/^[^\s]\.\s?[^\s]+$/', $aParsedMatchup[$iZ]))
                {
                    //Probably J.Koscheck
                    $aFoundFieldsType[$iZ] = (count($aParsedMatchup) > 1 ? 6 : 5);
                }
                else
                {
                    //Probably Koscheck
                    $aFoundFieldsType[$iZ] = (count($aParsedMatchup) > 1 ? 1 : 3);
                }
            }
            else if (preg_match('/^[^\s] [^\n]+/', $aParsedMatchup[$iZ]))
            {
                //Probably J Koscheck   
                $aFoundFieldsType[$iZ] = (count($aParsedMatchup) > 1 ? 7 : 8);
            }
            else 
            {
                //Could be J. Koscheck
                if (preg_match('/^[^\s]\.\s[^\s]+$/', $aParsedMatchup[$iZ]))
                {
                    //Probably J. Koscheck (checks against J.Koscheck)
                    $aFoundFieldsType[$iZ] = (count($aParsedMatchup) > 1 ? 6 : 5);
                }
                else
                {
                    //Probably Josh Koschek
                
                    //Since we cant be sure if this is a double last name, we need to return false here for a single user..
                    if (count($aParsedMatchup) == 1)
                    {
                        return false;
                    }

                    $aFoundFieldsType[$iZ] = (count($aParsedMatchup) > 1 ? 2 : 4);
                }
            }
        }
        //For multiple fields, compare and make sure we have only one type for both. If not return false
        if (count($aParsedMatchup) > 1)
        {
            if ($aFoundFieldsType[0] != $aFoundFieldsType[1])
            {
                return false;
            }
        }
        return $aFoundFieldsType[0];
    }

    public function updateMatchedProps($matched_props)
    {
        foreach ($matched_props as $matched_prop)
        {
            if ($matched_prop['match_result']['status'] == true)
            {
                if ($matched_prop['match_result']['template']->isEventProp())
                {
                    $this->updateMatchedEventProp($matched_prop);
                }
                else
                {
                    $this->updateMatchedMatchupProp($matched_prop);
                }
            }
        }
    }

    private function updateMatchedEventProp($matched_prop)
    {
        $new_prop = null;

        if ($matched_prop['match_result']['template']->isNegPrimary())
        {
            //Create a EventPropBet object for storage
            $new_prop = new EventPropBet($matched_prop['match_result']['event']['event_id'],
                            $a_iBookieID,
                            '',
                            $a_oProp->getMoneyline(($a_oProp->getMainProp() % 2) + 1),
                            '',
                            $a_oProp->getMoneyline($a_oProp->getMainProp()),
                            $matched_prop['match_result']['template']->getPropTypeID(),
                            '');
        }
        else
        {
            //Create a EventPropBet object for storage
            $new_prop = new EventPropBet($matched_prop['match_result']['event']['event_id'],
                            $a_iBookieID,
                            '',
                            $a_oProp->getMoneyline($a_oProp->getMainProp()),
                            '',
                            $a_oProp->getMoneyline(($a_oProp->getMainProp() % 2) + 1),
                            $matched_prop['match_result']['template']->getPropTypeID(),
                            '');
        }

        //Store prop bet if it has changed
        if (OddsHandler::checkMatchingEventPropOdds($new_prop))
        {
            $this->logger->info("------- nothing has changed since last event prop odds");
            return true;
        }
        else
        {
            $this->logger->info("------- adding new event prop odds!");
            if (OddsHandler::addEventPropBet($new_prop))
            {
                return true;
            }

            $this->logger->error('----Prop not stored properly: ' . var_export($new_prop, true) . '');
            return false;
        }
    }


    private function updateMatchedMatchupProp($matched_prop)
    {
        //Check if the specific bookie has normal odds (non-prop) for the fight, if not, cancel the matching since no bookie generally has props but no normal odds
        if (EventHandler::getLatestOddsForFightAndBookie($matched_prop['match_result']['matchup']['matchup_id'], $this->bookie_id) == null)
        {
            $this->logger->warning('----Bookie does not have normal odds for matchup ' . ($matched_prop['match_result']['matchup']['matchup_id']) . ', bailing');
            return false;
        }

        $new_prop = null;
        if ($matched_prop['match_result']['template']->isNegPrimary())
        {
            //Create a PropBet object for storage
            $new_prop = new PropBet($matched_prop['match_result']['matchup']['matchup_id'],
                            $this->bookie_id,
                            '',
                            $matched_prop['prop']->getMoneyline(($matched_prop['prop']->getMainProp() % 2) + 1),
                            '',
                            $matched_prop['prop']->getMoneyline($matched_prop['prop']->getMainProp()),
                            $matched_prop['match_result']['template']->getPropTypeID(),
                            '',
                            $matched_prop['match_result']['matchup']['team']);
        }
        else
        {
            //Create a PropBet object for storage
            $new_prop = new PropBet($matched_prop['match_result']['matchup']['matchup_id'],
                            $this->bookie_id,
                            '',
                            $matched_prop['prop']->getMoneyline($matched_prop['prop']->getMainProp()),
                            '',
                            $matched_prop['prop']->getMoneyline(($matched_prop['prop']->getMainProp() % 2) + 1),
                            $matched_prop['match_result']['template']->getPropTypeID(),
                            '',
                            $matched_prop['match_result']['matchup']['team']);
        }

        //Store prop bet if it has changed
        if (OddsHandler::checkMatchingPropOdds($new_prop))
        {
            $this->logger->info("------- nothing has changed since last prop odds - matchup: " . $new_prop->getMatchupID() . " proptype_id: " . $new_prop->getPropTypeID());
            return true;
        }
        else
        {
            $this->logger->info("------- adding new prop odds: " . $new_prop->getMatchupID() . " proptype_id: " . $new_prop->getPropTypeID() . " team_num: " . $new_prop->getTeamNumber());
            if (OddsHandler::addPropBet($new_prop))
            {
                return true;
            }

            $this->logger->error('----Prop not stored properly: ' . var_export($new_prop, true) . '');
            return false;
        }
    }

    public function logUnmatchedProps($matched_props)
    {
        foreach ($matched_props as $prop)
        {
            if ($prop['match_result']['status'] == false)
            {
                switch ($prop['match_result']['fail_reason'])
                {
                    case 'no_template_found':
                        EventHandler::logUnmatched($prop['prop']->toString(), $this->bookie_id, 2, $prop['prop']->getAllMetaData());
                        break;
                    case 'no_matchup_found':
                        EventHandler::logUnmatched($prop['prop']->toString(), $this->bookie_id, 1, $prop['prop']->getAllMetaData());
                        break;
                    case 'no_event_found':
                        EventHandler::logUnmatched($prop['prop']->toString(), $this->bookie_id, 1, $prop['prop']->getAllMetaData());
                        break;
                    default:
                }
            }
        }
    }

}

?>
