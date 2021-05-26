<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once 'bfo/bootstrap.php';

use BFO\General\EventHandler;
use BFO\DataTypes\Event;

final class CreateEventTest extends TestCase
{
    public function testCanCreateAndDeleteEvent(): void
    {
        //Test creation
        $event_name = 'Test Event ' . ((string) time());
        $event_date = date('Y-m-d');
        $event = EventHandler::addNewEvent(new Event(0, $event_date, $event_name, true));
        $event_id = $event->getID();
        $this->assertInstanceOf(
            Event::class,
            $event
        );
        $this->assertEquals(
            $event_name,
            $event->getName()
        );
        $this->assertEquals(
            $event_date,
            $event->getDate()
        );

        //Test retrieval by name
        $event = EventHandler::getEvents(event_name: $event_name);
        $this->assertEquals(
            $event_name,
            $event[0]->getName()
        );
        $this->assertEquals(
            $event_id,
            $event[0]->getID()
        );


        //Test update
        EventHandler::changeEvent($event_id, 'Renamed ' . $event_name);
        $event = EventHandler::getEvents(event_id: $event_id);
        $this->assertEquals(
            'Renamed ' . $event_name,
            $event[0]->getName()
        );
        EventHandler::changeEvent($event_id, '', '2032-10-23');
        $event = EventHandler::getEvents(event_id: $event_id);
        $this->assertEquals(
            '2032-10-23',
            $event[0]->getDate()
        );
        $event = EventHandler::getEvents(event_date: '2032-10-23');
        $this->assertEquals(
            '2032-10-23',
            $event[0]->getDate()
        );

        $result = EventHandler::removeEvent($event_id);
        $this->assertEquals(
            true,
            $result
        );

        //Ensure that event doesnt exist after deletion
        $event = EventHandler::getEvent($event_id);
        $this->assertEquals(
            false,
            $event
        );
    }

    public function testCannotCreateEventWithInvalidInput(): void
    {
        //Test invalid creation
        $event_name = 'Test Event ' . ((string) time());
        $event_date = date('Y-m-d');

        $obj = EventHandler::addNewEvent(new Event(0, '', $event_name, true));
        $this->assertEquals(
            false,
            $obj
        );

        $obj = EventHandler::addNewEvent(new Event(0, '2041-99-38', $event_name, true));
        $this->assertEquals(
            false,
            $obj
        );

        $obj = EventHandler::addNewEvent(new Event(0, 'Invalid date', $event_name, true));
        $this->assertEquals(
            false,
            $obj
        );

        $obj = EventHandler::addNewEvent(new Event(0, $event_date, '', true));
        $this->assertEquals(
            false,
            $obj
        );
    }
    
}
