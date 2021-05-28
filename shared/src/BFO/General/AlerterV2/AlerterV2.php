<?php

namespace BFO\General\AlerterV2;

use BFO\General\AlerterV2\AlertsModel;
use BFO\General\EventHandler;
use BFO\General\OddsHandler;
use BFO\General\BookieHandler;

/*

LEFT TO DO:

- Templating of email structure to highlight parts of it. Like bold for linked matchup
- Actual sending of mails
- Front-end for adding props

Ambition right now is to not get specific prop categories in place but wait with that until this is done

*/

class AlerterV2
{
    public function __construct(
        private \Psr\Log\LoggerInterface $logger)
    {
        
    }

    //Possible error codes:
    //-100 Criteria already met
    //-2xx (passed from Model)
    //  -210 Duplicate entry
    //	-220 Invalid criterias
    //	-230 Invalid e-mail
    //  -240 Invalid odds type
    //  -250 Invalid criterias combination
    //
    // 1 = Alert added OK!

    public function addAlert($email, $oddstype, $criterias)
    {
        $am = new AlertsModel();
        //Before adding alert, check if criterias are already met. If so we return an exception
        if ($am->isAlertReached($criterias) == true) {
            //Criteria already met
            throw new \Exception('Criteria already met', 101);
        }
        try {
            $id = $am->addAlert($email, $oddstype, $criterias);
            $this->logger->info('Added alert ' . $id . ' for ' . $email . ', oddstype ' . $oddstype . ': ' . $criterias);
            return true;
        } catch (\Exception $e) {
            throw($e);
        }
        return null;
    }

    public function deleteAlert($alert_id)
    {
        $am = new AlertsModel();
        try {
            $am->deleteAlert($alert_id);
            $this->logger->info('Cleared alert ' . $alert_id);
        } catch (\Exception $e) {
            $this->logger->error('Unable to clear alert ' . $alert_id);
            return false;
        }
        return true;
    }

    public function checkAlerts()
    {
        $am = new AlertsModel();
        $alerts = $am->getAllAlerts();
        $alerts_to_send = [];
        foreach ($alerts as $alert) {
            //Evaluate if stored alert fills the criterias to be triggered
            if ($am->isAlertReached($alert->getCriterias())) {
                //Fills criteria, add it to list of alerts to dispatch
                $alerts_to_send[] = $alert;
            }
        }
        $ids_dispatched = $this->dispatchAlerts($alerts_to_send);
        foreach ($ids_dispatched as $alert_id) {
            $this->deleteAlert($alert_id);
        }
    }

    /*private function getAlertCategory($criterias)
    {
        if (isset($criterias['matchup_id'])) {
            return $this->isMatchupAlertReached($criterias);
        } elseif (isset($criterias['proptype_id'])) {
            return $this->isPropAlertReached($criterias);
        } elseif (isset($criterias['is_eventprop'])) {
            return $this->isEventPropAlertReached($criterias);
        }
        return false;
    }*/


    // Take a list of alerts and creates a new array grouping them by recipient e-mail
    private function groupAlerts($alerts)
    {
        $retgroup = [];
        foreach ($alerts as $alert) {
            $retgroup[(string) $alert->getEmail()][] = $alert;
        }
        return $retgroup;
    }

    // Dispatches alerts to a user, returns an array with the alert IDs successfully dispatched
    private function dispatchAlerts($alerts)
    {
        //Group alerts before dispatching them
        $grouped_alerts = $this->groupAlerts($alerts);
        $all_ids_dispatched = [];

        foreach ($grouped_alerts as $rcpt => $alerts) {
            $ids_dispatched = [];
            foreach ($alerts as $alert) {
                $ids_dispatched[] = $alert->getID();
            }

            $text = $this->formatAlertMail($alerts);
            $this->sendAlertsByMail($text);

            $this->logger->info('Dispatched ' . implode(' ,', $ids_dispatched) . ' for ' . $rcpt);
            $all_ids_dispatched = array_merge($all_ids_dispatched, $ids_dispatched);
        }
        return $all_ids_dispatched;
    }

    private function formatAlertMail($alerts)
    {
        if (!function_exists('BFO\General\AlerterV2\sortAlerts')) {
            function sortAlerts($a, $b)
            {
                //Determine if prop is matchup or event, use that ID as value to compare
                $sortval_a = 0;
                if (isset($a->getCriterias()['matchup_id'])) {
                    $sortval = $a->getCriterias()['matchup_id'];
                } elseif (isset($a->getCriterias()['event_id'])) {
                    $sortval = $a->getCriterias()['event_id'];
                }

                $sortval_b = 0;
                if (isset($b->getCriterias()['matchup_id'])) {
                    $sortval = $b->getCriterias()['matchup_id'];
                } elseif (isset($b->getCriterias()['event_id'])) {
                    $sortval = $b->getCriterias()['event_id'];
                }

                return $sortval_a < $sortval_b;
            }
        }
        usort($alerts, 'BFO\General\AlerterV2\sortAlerts');

        $text = "===========Alert mail:==========\n\n";
        $current_linked_id = 0;
        $latest_linked_id = 0;
        foreach ($alerts as $alert) {
            $formatted = $this->formatSingleAlert($alert);

            //Current linked id represents the id of the matchup or event that this alert is linked to. We print it once
            $latest_linked_id = isset($formatted['matchup']) ? $formatted['matchup']->getID() : $formatted['event']->getID();
            if ($current_linked_id != $latest_linked_id) {
                $current_linked_id = $latest_linked_id;
                $text .=  "** " . $formatted['header'] . " **\n";
            }

            $text .= $formatted['text'] . "\n";
        }
        $text .= "==============End of alert mail==============\n\n";
        return $text;
    }

    private function formatSingleAlert($alert)
    {
        $text = '';
        $type = '';
        $header = '';
        $matchup = null;
        $event = null;
        $criterias = $alert->getCriterias();
        //Add alert row
        if (isset($criterias['proptype_id'])) {
            //Prop
            $type = 'prop';
            $proptype = OddsHandler::getPropTypes(proptype_id: $criterias['proptype_id'])[0] ?? null;
            
            if (isset($criterias['matchup_id'])) {
                //Proptype is linked to a specific matchup
                $matchup = EventHandler::getMatchup($criterias['matchup_id']);
                $proptype->setPropDesc(str_replace('<T>', $matchup->getTeamLastNameAsString($criterias['team_num']), $proptype->getPropDesc()));
                $proptype->setPropNegDesc(str_replace('<T>', $matchup->getTeamLastNameAsString($criterias['team_num']), $proptype->getPropNegDesc()));
                $proptype->getPropDesc(str_replace('<T2>', $matchup->getTeamLastNameAsString(($criterias['team_num'] % 2) + 1), $proptype->getPropDesc()));
                $proptype->setPropNegDesc(str_replace('<T2>', $matchup->getTeamLastNameAsString(($criterias['team_num'] % 2) + 1), $proptype->getPropNegDesc()));
                $header = $matchup->getTeamAsString(1) . " vs. " . $matchup->getTeamAsString(2);
            } elseif (isset($criterias['event_id'])) {
                $event = EventHandler::getEvent($criterias['event_id']);
                $header = $event->getName();
            }

            $text .= '' . $proptype->getPropDesc() . ' / ' . $proptype->getPropNegDesc();
        } else {
            //Matchup
            $type = 'matchup';
            $matchup = EventHandler::getMatchup($criterias['matchup_id']);
            $header = $matchup->getTeamAsString(1) . " vs. " . $matchup->getTeamAsString(2);
            $latest_price = null;
            $add_bookie = '';

            if (isset($criterias['bookie_id'])) {
                //Grab bookie specific line
                $latest_price = EventHandler::getLatestOddsForFightAndBookie($criterias['matchup_id'], $criterias['bookie_id']);
                $bookie = BookieHandler::getBookieByID($criterias['bookie_id']);
                $add_bookie = ' at ' . $bookie->getName();
            } else {
                $latest_price = EventHandler::getBestOddsForFight($criterias['matchup_id']);
                //Grab generic best line
            }

            if (isset($criterias['line_limit'])) {
                $text = "Reached your limit (" . $criterias['line_limit'] . "): ";
                //Limit set, include it
                $text .= " " . $matchup->getTeamAsString($criterias['team_num']) . ' ' . $latest_price->getFighterOddsAsString($criterias['team_num']) . ' (vs. ' . $matchup->getTeamAsString((($criterias['team_num'] - 1) ^ 1) + 1) . ' ' . $latest_price->getFighterOddsAsString((($criterias['team_num'] - 1) ^ 1) + 1) . ')' . $add_bookie;
            } else {
                //No limit set, this is for show only
                $text = " " . $matchup->getTeamAsString(1) . ' (' . $latest_price->getFighterOddsAsString(1) . ') vs. ' . $matchup->getTeamAsString(2) . ' (' . $latest_price->getFighterOddsAsString(2) . ')' . $add_bookie;
            }
        }
        return ['type' => $type, 'text' => $text, 'matchup' => $matchup, 'event' => $event, 'header' => $header];
    }

    private function sendAlertsByMail($mail_text)
    {
        if (ALERTER_DEV_MODE == true) {
            //Do not actually send the alert, just echo it
            echo $mail_text;
        }
        //TODO: Format each alert determined by their criteras.. Do we do this earlier in the chain while we evaluate the criteras?
    }

    public function getAllAlerts()
    {
        return (new AlertsModel())->getAllAlerts();
    }
}
