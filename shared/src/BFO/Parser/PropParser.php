<?php

namespace BFO\Parser;

use BFO\General\BookieHandler;
use BFO\General\OddsHandler;
use BFO\Parser\Utils\ParseTools;
use BFO\General\EventHandler;
use BFO\General\TeamHandler;
use BFO\DataTypes\PropBet;
use BFO\DataTypes\EventPropBet;
use BFO\DataTypes\Fight;
use BFO\DataTypes\PropTemplate;

class PropParser
{
    private $logger;
    private $matchups;
    private $events;
    private $bookie_id;
    private $templates;

    public function __construct($logger, $bookie_id)
    {
        $this->logger = $logger;
        $this->bookie_id = $bookie_id;

        //Prefetch bookie prop templates
        $this->templates = BookieHandler::getPropTemplatesForBookie($bookie_id);

        //Prefetch all upcoming matchups and events
        $this->matchups = EventHandler::getMatchups(future_matchups_only: true, only_with_odds: true);
        $this->events = EventHandler::getEvents(future_events_only: true);

        //We will need to check the alt names as well so for each upcoming matchup fetched , fetch the associated altnames for each team and add a new matchup using this
        $new_matchup_list = $this->matchups;
        foreach ($this->matchups as $matchup) {
            $new_matchup_list = array_merge($new_matchup_list, $this->addAltNameMatchupsToMatchup($matchup));
        }
        $this->matchups = $new_matchup_list;
    }

    public function matchProps($props)
    {
        $result = [];
        foreach ($props as $prop) {
            $match = $this->matchSingleProp($prop);
            if ($match['status'] == true) {
                $result[] = ['prop' => $prop, 'match_result' => $match];
            } else {
                //Unmatched to either template, matchup or event. Log this both in log file and in database
                switch ($match['fail_reason']) {
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
        $template = $this->matchPropToTemplate($prop);
        if (!$template) {
            //No matching template
            return ['status' => false, 'fail_reason' => 'no_template_found'];
        }
        BookieHandler::updateTemplateLastUsed($template->getID());

        $event = null;
        $matchup = null;
        if ($template->isEventProp()) {
            $event = $this->matchPropToEvent($prop, $template);
            if (!$event['event_id']) {
                //No matching event
                return ['status' => false, 'fail_reason' => 'no_event_found', 'template' => $template];
            }
        } else {
            $matchup = $this->matchPropToMatchup($prop, $template, true); //Check first with correlation ID enabled
            if (!$matchup['matchup_id']) {
                $matchup = $this->matchPropToMatchup($prop, $template, false); //Check with correlation ID disabled
                if (!$matchup['matchup_id']) {
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
    public function matchPropToTemplate(ParsedProp $prop): ?PropTemplate
    {
        $is_found = false;
        $found_template = null;
        foreach ($this->templates as $template) {
            $template_str = '';
            if ($template->isNegPrimary()) {
                $template_str = $template->getTemplateNeg();
            } else {
                $template_str = $template->getTemplate();
            }

            //Convert template variables to equivalent regexps
            $template_str = str_replace(' - ', ' \- ', $template_str);
            $template_str = str_replace(' + ', ' \+ ', $template_str);
            $template_str = strtoupper(str_replace('<T>', '([^:]+?)', $template_str));
            $template_str = str_replace('<*>', '.+?', $template_str);
            $template_str = str_replace('<.>', '.?', $template_str);
            $template_str = str_replace('<?>', '.*?', $template_str);
            $template_str = str_replace('/', '\/', $template_str);

            //Check the template against the prop
            $propvalue_matches = [];
            $found1 = (preg_match('/^' . $template_str . '$/', $prop->getTeamName(1), $propvalue_matches1) > 0);
            $found2 = (preg_match('/^' . $template_str . '$/', $prop->getTeamName(2), $propvalue_matches2) > 0);
            if ($found1 && $found2) {
                $this->logger->info('--Both team fields match template. Picking shortest one as it is probably not the negative one: ' .  $prop->getTeamName(1) . ' compared to ' .  $prop->getTeamName(2));
                //TEMPORARY HACK FOR PROPTYPE 65. REMOVE WHEN NO ODDS ARE LIVE FOR THIS TYPE
                if ($template->getPropTypeID() == 65) {
                    $this->logger->warning('---Proptype 65, picking main prop 1 anyway. TODO: Remove this when no odds are live!');
                    $prop->setMainProp(1);
                    $propvalue_matches = $propvalue_matches1;
                } elseif (strlen($prop->getTeamName(1)) <= strlen($prop->getTeamName(2))) {
                    $this->logger->debug('---Field 1 wins');
                    $prop->setMainProp(1);
                    $propvalue_matches = $propvalue_matches1;
                } else {
                    $this->logger->debug('---Field 2 wins');
                    $prop->setMainProp(2);
                    $propvalue_matches = $propvalue_matches2;
                }
            } elseif ($found1) {
                //Found in team 1
                $prop->setMainProp(1);
                $propvalue_matches = $propvalue_matches1;
            } elseif ($found2) {
                //Found in team 2
                $prop->setMainProp(2);
                $propvalue_matches = $propvalue_matches2;
            }
            $is_found = ($found1 || $found2);

            if ($is_found) {
                //Check if value is already stored, if so we have multiple templates matching the prop which is not good
                if ($found_template) {
                    $this->logger->warning('--Multiple templates matched. Will accept longest one');
                }

                //Replacing only template if the new one is longer (better match)
                if (!$found_template || strlen($template->getTemplate()) > strlen($found_template->getTemplate())) {
                    //Remove the first element in the array since that contains the full regexp match
                    array_shift($propvalue_matches);

                    //Check if no prop values were fetched, this will cause problems later on
                    if (count($propvalue_matches) == 0) {
                        $this->logger->warning('--No prop values fetched, make sure that no invalid prop templates exist');
                    } else {
                        //Store the prop values in the prop
                        $prop->setPropValues($propvalue_matches);
                    }
                    $found_template = $template;
                }
            }
            $is_found = false;
        }
        return $found_template;
    }

    public function matchPropToMatchup(ParsedProp $parsed_prop, PropTemplate $template, bool $use_correlation_id = true): array
    {
        //Get template variables (e.g. <T>) from the primary side of the prop
        $template_variables = [];
        if ($template->isNegPrimary()) {
            $template_variables = $template->getNegPropVariables();
        } else {
            $template_variables = $template->getPropVariables();
        }
        //Check that prop and template have the same number of variables/values
        $aPropValues = $parsed_prop->getPropValues();

        if (count($aPropValues) != count($template_variables)) {
            $this->logger->error('---Template variable count (' . count($template_variables) . ') does not match prop values count (' . count($aPropValues) . '). Not good, check template.');
            return null;
        }

        $prop_matchup_values = $parsed_prop->getPropValues();

        //Loop through the parsed prop values and determine fields type. Default is full name
        $new_fieldstype_id = $this->determineFieldsType($prop_matchup_values);
        $fields_type = $template->getFieldsTypeID();
        if ($new_fieldstype_id != false) {
            $fields_type = $new_fieldstype_id;
        }

        sort($prop_matchup_values);

        $matchups_to_check = $this->getMatchupsToCheck($parsed_prop, $use_correlation_id);
        $matches = $this->comparePropValuesToMatchups($prop_matchup_values, $matchups_to_check, $fields_type);

        $found_matchup_id = null;
        $found_team_num = null; //Used if we need to find out which team the prop is for as well. Used when matching one single name
        if (count($matches) >= 1) {

            //If we have multiple matches, ensure that there are not two matchups with the same fsim. If that is the case we bail
            if (count($matches) > 1) {
                for ($i = 0; $i < count($matches) - 1; $i++) {
                    if ($matches[$i]['fsim'] == $matches[$i + 1]['fsim'] && $matches[$i]['matchup_obj']->getID() != $matches[$i + 1]['matchup_obj']->getID()) {
                        $this->logger->warning('----Two or more matches with identical scores (' . $matches[0]['fsim'] . '): '
                            . $matches[0]['matchup_obj']->getTeam(1) . '/' . $matches[0]['matchup_obj']->getTeam(2) . ' and '
                            . $matches[1]['matchup_obj']->getTeam(1) . '/' . $matches[1]['matchup_obj']->getTeam(2)
                            . ', cannot determine winner. Bailing..');
                        return array('matchup_id' => null, 'team' => 0);
                    }
                }
            }

            $found_matchup_id = $matches[0]['matchup_obj']->getID();
            $found_team_num = $matches[0]['found_team_pos'];
            //Switch team order if the matchup has order changed
            if ($matches[0]['matchup_obj']->hasOrderChanged()) {
                if ($found_team_num == 1) {
                    $found_team_num = 2;
                } elseif ($found_team_num == 2) {
                    $found_team_num = 1;
                }
            }
        }

        return ['matchup_id' => $found_matchup_id, 'team' => $found_team_num];
    }

    private function getMatchupValuesBasedOnFieldType(Fight $matchup, int $fields_type, bool $only_single_lastname, int $specific_team_num = 0): array
    {
        $stored_matchup_values = null;
        switch ($fields_type) {
                //1 lastname vs lastname (koscheck vs miller)
            case 1:
                $stored_matchup_values = array(ParseTools::getLastnameFromName($matchup->getTeam(1), $only_single_lastname), ParseTools::getLastnameFromName($matchup->getTeam(2), $only_single_lastname));
                break;
                //2 fullname vs fullname (e.g josh koscheck vs dan miller)
            case 2:
                $stored_matchup_values = array($matchup->getTeam(1), $matchup->getTeam(2));
                break;
                //3 single lastname (koscheck)
            case 3:
                $stored_matchup_values = array(ParseTools::getLastnameFromName($matchup->getTeam($specific_team_num), $only_single_lastname));
                break;
                //4 full name (josh koscheck)
            case 4:
                $stored_matchup_values = array($matchup->getTeam($specific_team_num));
                break;
                //5 first letter.lastname (e.g. j.koscheck)
            case 5:
                $initials = ParseTools::getInitialsFromName($matchup->getTeam($specific_team_num));
                $stored_matchup_values = array($initials[0] . '.' . ParseTools::getLastnameFromName($matchup->getTeam($specific_team_num), $only_single_lastname));
                break;
                //6 first letter.lastname vs first letter.lastname (e.g. j.koscheck vs d.miller)
            case 6:
                $initials1 = ParseTools::getInitialsFromName($matchup->getTeam(1));
                $initials2 = ParseTools::getInitialsFromName($matchup->getTeam(2));
                $stored_matchup_values = array($initials1[0] . '.' . ParseTools::getLastnameFromName($matchup->getTeam(1), $only_single_lastname), $initials2[0] . '.' . ParseTools::getLastnameFromName($matchup->getTeam(2), $only_single_lastname));
                break;
                //7 first letter lastname vs first letter lastname (e.g. j koscheck vs d miller)
            case 7:
                $initials1 = ParseTools::getInitialsFromName($matchup->getTeam(1));
                $initials2 = ParseTools::getInitialsFromName($matchup->getTeam(2));
                $stored_matchup_values = array($initials1[0] . ' ' . ParseTools::getLastnameFromName($matchup->getTeam(1), $only_single_lastname), $initials2[0] . ' ' . ParseTools::getLastnameFromName($matchup->getTeam(2), $only_single_lastname));
                break;
                //8 first letter lastname (e.g. j koscheck)
            case 8:
                $initials = ParseTools::getInitialsFromName($matchup->getTeam($specific_team_num));
                $stored_matchup_values = array($initials[0] . ' ' . ParseTools::getLastnameFromName($matchup->getTeam($specific_team_num), $only_single_lastname));
                break;
            default:
                $this->logger->error('---Unknown fields type ID in PropTemplate: ' . $fields_type);
                return null;
        }

        sort($stored_matchup_values);
        return $stored_matchup_values;
    }

    private function comparePropValuesToMatchups(array $prop_values, array $matchups_to_check, int $fields_type): array
    {
        //Determine last name composition from parsed matchup (e.g. if last name would be Dos Santos or Santos for Junior Dos Santos)
        $only_single_lastname = true;
        if (strpos($prop_values[0], ' ') !== false || (isset($prop_values[1]) && strpos($prop_values[1], ' ') !== false)) {
            $only_single_lastname = false;
        }

        $matches = [];

        if (count($prop_values) == 1) { //One team prop (e.g. Santos wins by submission)
            foreach ($matchups_to_check as $matchup) {
                //Check twice, once for each team side
                for ($i = 1; $i <= 2; $i++) {
                    $matchup_values = $this->getMatchupValuesBasedOnFieldType($matchup, $fields_type, $only_single_lastname, $i);
                    //Compare the values fetched from the prop with the stored matchup
                    similar_text($matchup_values[0], $prop_values[0], $fSim);
                    $this->logger->debug("Checking: " . $matchup_values[0] . " vs " . $prop_values[0] . " fsim:" . $fSim);
                    if ($fSim > 87) {
                        $matches[] = ['matchup_obj' => $matchup, 'fsim' => $fSim, 'found_team_pos' => $i];
                    }
                }
            }
        } else if (count($prop_values) == 2) { //Two team prop (e.g. Santos/Werdum goes the distance)
            $fsim_sides = [];
            //We check this two times, first time X vs Y against A vs B, the second time X vs Y against A vs B
            foreach ($matchups_to_check as $matchup) {
                $matchup_values = $this->getMatchupValuesBasedOnFieldType($matchup, $fields_type, $only_single_lastname);
                $fsim_sides = [];
                for ($i = 0; $i <= 1; $i++) { //Compare both sides of the prop
                    //Compare the values fetched from the prop with the stored matchup
                    similar_text($matchup_values[$i], $prop_values[$i], $fsim_sides[$i]);
                    $this->logger->debug("Checking: " . $matchup_values[$i] . " vs " . $prop_values[$i] . " fsim:" . $fsim_sides[$i]);
                }
                $fSim = min($fsim_sides); //Best sim for this combination
                if ($fSim > 87) {
                    $matches[] = ['matchup_obj' => $matchup, 'fsim' => $fSim, 'found_team_pos' => 0];
                }
            }
        }

        //Sort by fsim descending
        usort($matches, function ($a, $b) {
            return $b['fsim'] <=> $a['fsim'];
        });

        return $matches;
    }


    private function getMatchupsToCheck(ParsedProp $parsed_prop, bool $use_correlation_id): array
    {
        $matchups = $this->matchups;
        if ($use_correlation_id) {
            //Check if a manual correlation has been created and stored
            $prop_name = ($parsed_prop->getMainProp() == 1 ? $parsed_prop->getTeamName(1) : $parsed_prop->getTeamName(2));
            $this->logger->debug('--- searching for manual correlation: ' . $prop_name . ' for bookie ' . $this->bookie_id);
            $manual_correlation = OddsHandler::getMatchupForCorrelation($this->bookie_id, $prop_name);
            if ($manual_correlation) {
                $this->logger->debug('---- prop has manual correlation stored: ' . $manual_correlation);
                $matchup_from_correlation = EventHandler::getMatchup((int) $manual_correlation);
                if ($matchup_from_correlation) {
                    $this->logger->debug('----- found stored manual correlation for ' . $prop_name . ' : ' . $matchup_from_correlation->getID());
                    $matchups = [$matchup_from_correlation];
                    //Even though we have prematched the matchup, we still need to add alt name matchups
                    $matchups = array_merge($matchups, $this->addAltNameMatchupsToMatchup($matchup_from_correlation));
                }
            } else {
                //Default is we search all upcoming matchups, but if there is a matchup
                //pre-matched already, we'll just check that
                if ($parsed_prop->getCorrelationID() != '') {
                    $this->logger->debug('--- prop has correlation id: ' . $parsed_prop->getCorrelationID());
                    $matchup_from_correlation = EventHandler::getMatchup((int) ParseTools::getCorrelation($parsed_prop->getCorrelationID()));
                    if ($matchup_from_correlation) {
                        $this->logger->debug('--- found stored correlation: ' . $matchup_from_correlation->getID());
                        $matchups = [$matchup_from_correlation];
                        //Even though we have prematched the matchup, we still need to add alt name matchups
                        $matchups = array_merge($matchups, $this->addAltNameMatchupsToMatchup($matchup_from_correlation));
                    } else {
                        $this->logger->debug('--- no stored correlation found (' . $parsed_prop->getCorrelationID() . ')');
                    }
                }
            }
        }
        return $matchups;
    }

    private function matchPropToEvent($a_oProp, $a_oTemplate)
    {
        $template_variables = [];
        if ($a_oTemplate->isNegPrimary()) {
            $template_variables = $a_oTemplate->getNegPropVariables();
        } else {
            $template_variables = $a_oTemplate->getPropVariables();
        }

        //Check that prop and template have the same number of variables/values
        $aPropValues = $a_oProp->getPropValues();

        if (count($aPropValues) != count($template_variables)) {
            $this->logger->error('---Template variable count (' . count($template_variables) . ') does not match prop values count (' . count($aPropValues) . '). Not good, check template.');
            return null;
        }

        $aParsedEvent = $a_oProp->getPropValues();
        sort($aParsedEvent);

        $oFoundEvent = null;
        $iFoundEventID = null;
        $fFoundSim = 0; //Used to compare fsims if two matchups are found for the same prop

        foreach ($this->events as $oEvent) {
            //Compare the values fetched from the prop with the stored matchup
            $bFound = false;
            $fNewSim = 0;
            similar_text(strtoupper($oEvent->getName()), $aParsedEvent[0], $fSim1);
            similar_text(substr(strtoupper($oEvent->getName()), 0, strpos($oEvent->getName(), ':')), $aParsedEvent[0], $fSim2);
            $fSim = ($fSim1 > $fSim2 ? $fSim1 : $fSim2);

            //DEBUG:
            $this->logger->debug("Checking: " . $oEvent->getName() . " _and " . substr(strtoupper($oEvent->getName()), 0, strpos($oEvent->getName(), ':')) . " vs " . $aParsedEvent[0] . " fsim:" . $fSim);

            if ($fSim > 90) {
                $fNewSim = $fSim;
                $bFound = true;
            } else {
                $bFound = false;
            }

            if ($bFound == true) {
                if ($oFoundEvent != null) {
                    $this->logger->info('---Found multiple matches for prop values. Comparing fsims, challenger: ' . $oEvent->getName() . ' ' . $oEvent->getID() . ' (' . $fNewSim . ') and current: ' . $oFoundEvent->getName() . ' ' . $oFoundEvent->getID() . ' (' . $fFoundSim . ')');
                    if ($fNewSim > $fFoundSim) {
                        $oFoundEvent = $oEvent;
                        $iFoundEventID = $oFoundEvent->getID();
                        $fFoundSim = $fNewSim;
                        $this->logger->info('----Challenger won, changing matched to new one: ' . $iFoundEventID);
                    } elseif ($fNewSim == $fFoundSim) {
                        $this->logger->info('----Fsims are identical, cannot determine winner. Bailing..');
                        return array('event' => null);
                    } else {
                        $this->logger->info('----Current won. Sticking with current');
                    }
                } else {
                    $oFoundEvent = $oEvent;
                    $iFoundEventID = $oFoundEvent->getID();
                    $fFoundSim = $fNewSim;
                }
            }
        }

        return array('event_id' => $iFoundEventID);
    }

    private function addAltNameMatchupsToMatchup(Fight $matchup): array
    {
        $new_matchups = [];

        $team1_altnames = TeamHandler::getAltNamesForTeamByID($matchup->getFighterID(1));
        $team2_altnames = TeamHandler::getAltNamesForTeamByID($matchup->getFighterID(2));

        if ($team1_altnames) {
            foreach ($team1_altnames as $team1_altname) {

                $new_matchup = new Fight($matchup->getID(), $team1_altname, $matchup->getTeam(2), $matchup->getEventID());
                $new_matchup->setExternalOrderChanged($matchup->hasExternalOrderChanged());
                $new_matchups[] = $new_matchup;

                if ($team2_altnames) {
                    foreach ($team2_altnames as $team2_altname) {
                        $new_matchup = new Fight($matchup->getID(), $team1_altname, $team2_altname, $matchup->getEventID());
                        $new_matchup->setExternalOrderChanged($matchup->hasExternalOrderChanged());
                        $new_matchups[] = $new_matchup;
                    }
                }
            }
        }
        if ($team2_altnames) {
            foreach ($team2_altnames as $team2_altname) {

                $new_matchup = new Fight($matchup->getID(), $matchup->getTeam(1), $team2_altname, $matchup->getEventID());
                $new_matchup->setExternalOrderChanged($matchup->hasExternalOrderChanged());
                $new_matchups[] = $new_matchup;
            }
        }

        return $new_matchups;
    }

    private function determineFieldsType($parsed_matchup)
    {
        $found_fields_type = [];
        for ($iZ = 0; $iZ < count($parsed_matchup); $iZ++) {
            if (strpos($parsed_matchup[$iZ], " ") != true) {
                //Either Koscheck or J.Koscheck
                if (preg_match('/^[^\s]\.\s?[^\s]+$/', $parsed_matchup[$iZ])) {
                    //Probably J.Koscheck
                    $found_fields_type[$iZ] = (count($parsed_matchup) > 1 ? 6 : 5);
                } else {
                    //Probably Koscheck
                    $found_fields_type[$iZ] = (count($parsed_matchup) > 1 ? 1 : 3);
                }
            } elseif (preg_match('/^[^\s] [^\n]+/', $parsed_matchup[$iZ])) {
                //Probably J Koscheck
                $found_fields_type[$iZ] = (count($parsed_matchup) > 1 ? 7 : 8);
            } else {
                //Could be J. Koscheck
                if (preg_match('/^[^\s]\.\s[^\s]+$/', $parsed_matchup[$iZ])) {
                    //Probably J. Koscheck (checks against J.Koscheck)
                    $found_fields_type[$iZ] = (count($parsed_matchup) > 1 ? 6 : 5);
                } else {
                    //Probably Josh Koschek

                    //Since we cant be sure if this is a double last name, we need to return false here for a single user..
                    if (count($parsed_matchup) == 1) {
                        return false;
                    }

                    $found_fields_type[$iZ] = (count($parsed_matchup) > 1 ? 2 : 4);
                }
            }
        }
        //For multiple fields, compare and make sure we have only one type for both. If not return false
        if (count($parsed_matchup) > 1) {
            if ($found_fields_type[0] != $found_fields_type[1]) {
                return false;
            }
        }
        return $found_fields_type[0];
    }

    public function updateMatchedProps($matched_props)
    {
        foreach ($matched_props as $matched_prop) {
            if ($matched_prop['match_result']['status'] == true) {
                if ($matched_prop['match_result']['template']->isEventProp()) {
                    $this->updateMatchedEventProp($matched_prop);
                } else {
                    $this->updateMatchedMatchupProp($matched_prop);
                }
            }
        }
    }

    private function updateMatchedEventProp($matched_prop)
    {
        $new_prop = null;

        if ($matched_prop['match_result']['template']->isNegPrimary()) {
            //Create a EventPropBet object for storage
            $new_prop = new EventPropBet(
                $matched_prop['match_result']['event']['event_id'],
                $this->bookie_id,
                '',
                $matched_prop['prop']->getMoneyline(($matched_prop['prop']->getMainProp() % 2) + 1),
                '',
                $matched_prop['prop']->getMoneyline($matched_prop['prop']->getMainProp()),
                $matched_prop['match_result']['template']->getPropTypeID(),
                ''
            );
        } else {
            //Create a EventPropBet object for storage
            $new_prop = new EventPropBet(
                $matched_prop['match_result']['event']['event_id'],
                $this->bookie_id,
                '',
                $matched_prop['prop']->getMoneyline($matched_prop['prop']->getMainProp()),
                '',
                $matched_prop['prop']->getMoneyline(($matched_prop['prop']->getMainProp() % 2) + 1),
                $matched_prop['match_result']['template']->getPropTypeID(),
                ''
            );
        }

        //Store prop bet if it has changed
        if (OddsHandler::checkMatchingEventPropOdds($new_prop)) {
            $this->logger->debug("------- nothing has changed since last event prop odds");
            return true;
        } else {
            $this->logger->info("------- adding new event prop odds!");
            if (OddsHandler::addEventPropBet($new_prop)) {
                return true;
            }

            $this->logger->error('----Prop not stored properly: ' . var_export($new_prop, true) . '');
            return false;
        }
    }

    private function updateMatchedMatchupProp($matched_prop)
    {
        //Check if the specific bookie has normal odds (non-prop) for the fight, if not, cancel the matching since no bookie generally has props but no normal odds
        if (EventHandler::getLatestOddsForFightAndBookie($matched_prop['match_result']['matchup']['matchup_id'], $this->bookie_id) == null) {
            $this->logger->warning('----Bookie does not have normal odds for matchup ' . ($matched_prop['match_result']['matchup']['matchup_id']) . ', bailing');
            return false;
        }

        $new_prop = null;
        if ($matched_prop['match_result']['template']->isNegPrimary()) {
            //Create a PropBet object for storage
            $new_prop = new PropBet(
                $matched_prop['match_result']['matchup']['matchup_id'],
                $this->bookie_id,
                '',
                $matched_prop['prop']->getMoneyline(($matched_prop['prop']->getMainProp() % 2) + 1),
                '',
                $matched_prop['prop']->getMoneyline($matched_prop['prop']->getMainProp()),
                $matched_prop['match_result']['template']->getPropTypeID(),
                '',
                $matched_prop['match_result']['matchup']['team']
            );
        } else {
            //Create a PropBet object for storage
            $new_prop = new PropBet(
                $matched_prop['match_result']['matchup']['matchup_id'],
                $this->bookie_id,
                '',
                $matched_prop['prop']->getMoneyline($matched_prop['prop']->getMainProp()),
                '',
                $matched_prop['prop']->getMoneyline(($matched_prop['prop']->getMainProp() % 2) + 1),
                $matched_prop['match_result']['template']->getPropTypeID(),
                '',
                $matched_prop['match_result']['matchup']['team']
            );
        }

        //Store prop bet if it has changed
        if (OddsHandler::checkMatchingPropOdds($new_prop)) {
            $this->logger->debug("------- nothing has changed since last prop odds - matchup: " . $new_prop->getMatchupID() . " proptype_id: " . $new_prop->getPropTypeID() . " team_num: " . $new_prop->getTeamNumber());
            return true;
        } else {
            $this->logger->info("------- adding new prop odds: " . $new_prop->getMatchupID() . " proptype_id: " . $new_prop->getPropTypeID() . " team_num: " . $new_prop->getTeamNumber());
            if (OddsHandler::addPropBet($new_prop)) {
                return true;
            }

            $this->logger->error('----Prop not stored properly: ' . var_export($new_prop, true) . '');
            return false;
        }
    }

    public function logUnmatchedProps($matched_props)
    {
        $counter = 0;
        foreach ($matched_props as $prop) {
            if ($prop['match_result']['status'] == false) {
                $counter++;
                switch ($prop['match_result']['fail_reason']) {
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
        return $counter;
    }
}
