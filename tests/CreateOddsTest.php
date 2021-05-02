<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once 'bfo/bootstrap.php';

use BFO\General\EventHandler;
use BFO\General\BookieHandler;
use BFO\DataTypes\Fight;
use BFO\DataTypes\Event;
use BFO\DataTypes\FightOdds;

final class CreateOddsTest extends TestCase
{
    private $event = null;
    private $matchup = null;
    private $bookie_id = null;

    public function setUp(): void
    {
        //Create a temporary event
        $event_name = 'Test Event Create Odds ' . ((string) time());
        $event_date = date('Y-m-d');
        $this->event = EventHandler::addNewEvent(new Event(0, $event_date, $event_name, true));

        $matchup = new Fight(0, 'Fighter One', 'Fighter Two', $this->event->getID());
        $matchup_id = EventHandler::addNewFight($matchup);
        $this->matchup = EventHandler::getFightByID($matchup_id);

        $this->bookie_id = BookieHandler::getAllBookies()[0]->getID();
    }

    public function tearDown(): void
    {
        EventHandler::removeEvent($this->event->getID());
    }

    public function testCanCreateAndDeleteOdds(): void
    {
        $ok_odds_variants = [
            ['-115', '+115'],
            ['-115', '-115'],
            ['-150', '+175'],
            ['EV', '-115'],
            ['-900', '+800'],
            ['-115', 'EVEN'],
            ['-3900', '+4800'],
            ['+223', '-175'],

        ];

        foreach ($ok_odds_variants as $odds) {
            $odds_obj = new FightOdds($this->matchup->getID(), $this->bookie_id, $odds[0], $odds[1], -1);
            $result = EventHandler::addNewFightOdds($odds_obj);
            $this->assertEquals(
                true,
                $result
            );
        }
    }

    public function testCannotCreateInvalidOdds(): void
    {
        $invalid_odds_variants = [
            ['-99', '+115'],
            ['-115', '+25'],
            ['+200', '+175'],
            ['-30', '+40'],
            ['ODDS', '-115'],
            ['Fighter One', 'Fighter Two'],
        ];

        foreach ($invalid_odds_variants as $odds) {
            $odds_obj = new FightOdds($this->matchup->getID(), $this->bookie_id, $odds[0], $odds[1], -1);
            $result = EventHandler::addNewFightOdds($odds_obj);
            $this->assertEquals(
                false,
                $result
            );
        }
    }

    public function testCannotCreateDuplicateOdds(): void
    {
        $this->expectException(Exception::class);

        $odds_obj = new FightOdds($this->matchup->getID(), $this->bookie_id, '-250', '+125', -1);
        $result = EventHandler::addNewFightOdds($odds_obj);
        $this->assertEquals(
            true,
            $result
        );
        $result = EventHandler::addNewFightOdds($odds_obj);
        $this->assertEquals(
            false,
            $result
        );
    }

    public function testCannotCreateOrphanOdds(): void
    {
        //No bookie specified
        $odds_obj = new FightOdds($this->matchup->getID(), null, '+300', '-250', -1);
        $result = EventHandler::addNewFightOdds($odds_obj);
        $this->assertEquals(
            false,
            $result
        );

        //No matchup specified
        $odds_obj = new FightOdds(null, $this->bookie_id, '+300', '-250', -1);
        $result = EventHandler::addNewFightOdds($odds_obj);
        $this->assertEquals(
            false,
            $result
        );

        //Invalid/unavailable matchup specified
        $odds_obj = new FightOdds(78913892, $this->bookie_id, '+300', '-250', -1);
        $result = EventHandler::addNewFightOdds($odds_obj);
        $this->assertEquals(
            false,
            $result
        );

        //Invalid/unavailable bookie specified
        $odds_obj = new FightOdds($this->matchup->getID(), 238, '+300', '-250', -1);
        $result = EventHandler::addNewFightOdds($odds_obj);
        $this->assertEquals(
            false,
            $result
        );
    }
}
