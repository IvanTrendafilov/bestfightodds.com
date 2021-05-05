<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once 'bfo/bootstrap.php';

use BFO\General\EventHandler;
use BFO\DataTypes\Fight;
use BFO\DataTypes\Event;

final class CreateMatchupTest extends TestCase
{
    private $event = null;

    public function setUp(): void 
    {
        //Create a temporary event
        $event_name = 'Test Event ' . ((string) time());
        $event_date = date('Y-m-d');
        $this->event = EventHandler::addNewEvent(new Event(0, $event_date, $event_name, true));
    }

    public function tearDown(): void
    {
        $result = EventHandler::removeEvent($this->event->getID());
    }
    
    public function testCanCreateAndDeleteMatchup(): void
    {
        $matchup = new Fight(0, 'Test Fighter One', 'Test Fighter Two', $this->event->getID());
        $matchup_id = EventHandler::addNewFight($matchup);
        $this->assertThat($matchup_id, $this->logicalAnd(
            $this->isType('int'), 
            $this->greaterThan(0)
        ));

        $matchup_obj = EventHandler::getFightByID($matchup_id);
        $this->assertInstanceOf(
            Fight::class,
            $matchup_obj
        );
        $this->assertEquals(
            strtoupper('Test Fighter One'),
            $matchup_obj->getTeam(1)
        );
        $this->assertEquals(
            strtoupper('Test Fighter Two'),
            $matchup_obj->getTeam(2)
        );
        $this->assertEquals(
            $this->event->getID(),
            $matchup_obj->getEventID()
        );

        $result = EventHandler::removeFight($matchup_obj->getID());
        $this->assertEquals(
            true,
            $result
        );
        $matchup_obj = EventHandler::getFightByID($matchup_id);
        $this->assertEquals(
            false,
            $matchup_obj
        );
    }

    public function testCannotCreateMatchupWithInvalidInput(): void
    {
        $matchup = new Fight(0, '', 'Test Fighter Two', $this->event->getID());
        $matchup_id = EventHandler::addNewFight($matchup);
        $this->assertEquals(
            false,
            $matchup_id
        );
        $matchup = new Fight(0, '', '', $this->event->getID());
        $matchup_id = EventHandler::addNewFight($matchup);
        $this->assertEquals(
            false,
            $matchup_id
        );
        $matchup = new Fight(0, 'Test Fighter One', '', $this->event->getID());
        $matchup_id = EventHandler::addNewFight($matchup);
        $this->assertEquals(
            false,
            $matchup_id
        );
        $matchup = new Fight(0, 'Test Fighter One', 'Test Fighter Two', 48924892);
        $matchup_id = EventHandler::addNewFight($matchup);
        $this->assertEquals(
            false,
            $matchup_id
        );
    }

    public function testCannotCreateDuplicateMatchup(): void
    {
        $matchup = new Fight(0, 'Test Fighter One', 'Test Fighter Two', $this->event->getID());
        $matchup_id = EventHandler::addNewFight($matchup);
        $this->assertThat($matchup_id, $this->logicalAnd(
            $this->isType('int'), 
            $this->greaterThan(0)
        ));
        $second_matchup_id = EventHandler::addNewFight($matchup);
        $this->assertEquals(
            false,
            $second_matchup_id
        );
        $result = EventHandler::removeFight($matchup_id);
        $this->assertEquals(
            true,
            $result
        );
        $matchup_obj = EventHandler::getFightByID($matchup_id);
        $this->assertEquals(
            false,
            $matchup_obj
        );

    }
    
}
