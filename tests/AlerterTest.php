<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once 'bfo/bootstrap.php';

use BFO\General\EventHandler;
use BFO\General\BookieHandler;
use BFO\DataTypes\Fight;
use BFO\DataTypes\Event;
use BFO\DataTypes\PropTemplate;
use BFO\Parser\OddsProcessor;
use Psr\Log\NullLogger;
use BFO\General\AlerterV2\AlerterV2;

final class AlerterTest extends TestCase
{
    private $event = null;
    private $matchup = null;
    private $bookie_id = null;
    private $template_id = null;
    private $alerter = null;

    public function setUp(): void
    {
        //Create a temporary event
        $event_name = 'Test Event Create Odds ' . ((string) time());
        $event_date = date('Y-m-d');
        $this->event = EventHandler::addNewEvent(new Event(0, $event_date, $event_name, true));

        $matchup = new Fight(0, 'Fighter OpOne', 'Fighter OpTwo', $this->event->getID());
        $matchup_id = EventHandler::createMatchup($matchup);
        $this->matchup = EventHandler::getMatchup($matchup_id);

        $this->bookie_id = BookieHandler::getAllBookies()[0]->getID();
        $this->op = new OddsProcessor(new NullLogger, $this->bookie_id, new RuleSet());

        //Create a temporary prop template
        $template = new PropTemplate(0, $this->bookie_id, 'UNITTEST OP FIGHT GOES TO DECISION - <T> vs. <T>', 'UNITTEST OP FIGHT DOES NOT GO TO DECISION', 1, 1, '');
        $this->template_id = BookieHandler::addNewPropTemplate($template);

        $this->alerter = new AlerterV2(new NullLogger);
        //Initialization: Clear all alerts
        $alerts = $this->alerter->getAllAlerts();
        foreach ($alerts as $alert) {
            $this->alerter->deleteAlert($alert->getID());
        }
    }

    public function tearDown(): void
    {
        EventHandler::removeEvent($this->event->getID());
        BookieHandler::deleteTemplate($this->template_id);
        $alerts = $this->alerter->getAllAlerts();
        foreach ($alerts as $alert) {
            $this->alerter->deleteAlert($alert->getID());
        }
    }

    public function testMatchupAlertDupe(): void
    {
        /*$aPropTypes = OddsHandler::getAllPropTypes();
        foreach ($aPropTypes as $oPropType) {
            if (!$oPropType->isEventProp()) {
                //If prop contains <T> then this is a team prop so we need to add both 1 and 2
                if (strpos($oPropType->getPropDesc(), '<T>') !== false) {
                    translateResult($this->alerter->addAlert('csacsa@gmail.com', 2, '{"matchup_id": 12435, "proptype_id": ' .  $oPropType->getID()  . ', "team_num": 1}'));
                    translateResult($this->alerter->addAlert('csacsa@gmail.com', 2, '{"matchup_id": 12435, "proptype_id": ' .  $oPropType->getID()  . ', "team_num": 2}'));
                } else {
                    translateResult($this->alerter->addAlert('csacsa@gmail.com', 2, '{"matchup_id": 12435, "proptype_id": ' .  $oPropType->getID()  . ', "team_num": 0}'));
                }
            }
        }
        */

        $this->assertEquals(
            true,
            $this->alerter->addAlert('testmail@bestfightodds.com', 2, '{"matchup_id": 12435}')     //Ok (Matchup 11816 Show)
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Duplicate entry');
        $this->assertNotEquals(
            true,
            $this->alerter->addAlert('testmail@bestfightodds.com', 2, '{"matchup_id": 12435}')    //Fail: Should be treated as dupe
        );
    }

    public function testPropAlertDupe()
    {
        $this->assertEquals(
            true,
            $this->alerter->addAlert('testmail@bestfightodds.com', 2, '{"event_id": 1164, "proptype_id": 35, "team_num": 0}') //Ok (Prop matchup in event 1164, proptype Over/Under 4½) - Should trigger
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Duplicate entry');
        $this->assertNotEquals(
            true,
            $this->alerter->addAlert('testmail@bestfightodds.com', 2, '{"event_id": 1164, "proptype_id": 35, "team_num": 0}') //Fail: Should be dupe
        );
    }

    public function testValidAlerts()
    {
        $this->assertEquals(
            true,
            $this->alerter->addAlert('testmail@bestfightodds.com', 2, '{"matchup_id": 12435, "bookie_id": 1}')     //Ok (Matchup 11816 Show, Bookie 1)
        );
        $this->assertEquals(
            true,
            $this->alerter->addAlert('testmail2131@bestfightodds.com', 2, '{"matchup_id": 12435, "line_limit":150, "team_num": 1}') //Ok (Limit not met yet)
        );
        $this->assertEquals(
            true,
            $this->alerter->addAlert('testmail2131@bestfightodds.com', 2, '{"matchup_id": 12435, "line_limit":-200, "team_num": 1}') //Ok (Limit met)
        );
        $this->assertEquals(
            true,
            $this->alerter->addAlert('testmail@bestfightodds.com', 2, '{"event_id": 1164, "proptype_id": 34, "team_num": 0}') //Ok (Prop matchup in event 1164, proptype Over/Under 3½) - Should not trigger
        );
        $this->assertEquals(
            true,
            $this->alerter->addAlert('testmail@bestfightodds.com', 2, '{"event_id": 1164, "proptype_id": 2, "team_num": 0}') //Ok (Prop matchup in event 1164, proptype Over/Under 3½) - Should trigger
        );
        $this->assertEquals(
            true,
            $this->alerter->addAlert('testmail@bestfightodds.com', 2, '{"matchup_id": 12435, "proptype_id": 8, "team_num": 1}') //Ok ($result = $this->alerter->addAlert('testmail@bestfightodds.com', 2, '{"event_id": 1164, "proptype_id": 35, "team_num": 0}'); //Fail: Should be dupe matchup in matchup 12435, proptype <T> wins by TKO) - Should trigger
        );
    }

    public function testInvalidAlerts(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid e-mail format');
        $this->assertNotEquals(
            true,
            $this->alerter->addAlert('fdsklsfsf.com', 2, '{"matchup_id": 12435}')    //Fail: Invalid e-mail
        );
    }

    public function testAlertMet(): void
    {
        //Create alert
        $this->assertEquals(
            true,
            $this->alerter->addAlert('testmail@bestfightodds.com', 2, '{"event_id": 1164, "proptype_id": 34, "team_num": 0}') //Ok (Prop matchup in event 1164, proptype Over/Under 3½) - Should not trigger
        );

        $alerts = $this->alerter->checkAlerts();
        
        //Add odds
        //Check alert
    }
}
