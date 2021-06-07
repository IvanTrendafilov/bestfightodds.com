<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once 'bfo/bootstrap.php';
require_once 'bfo/config/Ruleset.php';

use BFO\General\OddsHandler;
use BFO\General\EventHandler;
use BFO\General\BookieHandler;
use BFO\DataTypes\Fight;
use BFO\DataTypes\Event;
use BFO\DataTypes\PropTemplate;
use BFO\Parser\OddsProcessor;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;
use BFO\Parser\ParsedSport;
use Psr\Log\NullLogger;

final class OddsProcessorTest extends TestCase
{
    private $event = null;
    private $matchup = null;
    private $bookie_id = null;
    private $op = null;
    private $template_id = null;

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
    }

    public function tearDown(): void
    {
        EventHandler::removeEvent($this->event->getID());
        BookieHandler::deleteTemplate($this->template_id);
    }

    public function testRemoveMoneylineDupes(): void
    {
        $parsed_sport = new ParsedSport('MMA');
        $parsed_sport->addParsedMatchup(new ParsedMatchup('Fighter One', 'Fighter Two', -250, 125));
        $parsed_sport->addParsedMatchup(new ParsedMatchup('Fighter One', 'Fighter Two', -250, 125)); //Straight dupe
        $parsed_sport->addParsedMatchup(new ParsedMatchup('Fighter One', 'Fighter Two', -350, 105)); //Worse than previous on both sides
        $parsed_sport->addParsedMatchup(new ParsedMatchup('Fighter One', 'Fighter Two', -350, 125)); //Worse than previous on side 1
        $parsed_sport->addParsedMatchup(new ParsedMatchup('Fighter One', 'Fighter Two', -250, 105)); //Worse than previous on side 2

        $parsed_sport = $this->op->removeMoneylineDupes($parsed_sport);

        $this->assertEquals(
            1,
            count($parsed_sport->getParsedMatchups())
        );       
        ;
        $this->assertEquals(
            -250,
            $parsed_sport->getParsedMatchups()[0]->getMoneyLine(1)
        );       
        $this->assertEquals(
            125,
            $parsed_sport->getParsedMatchups()[0]->getMoneyLine(2)
        );      
    }

    public function testRemovePropDupes(): void
    {
        $parsed_sport = new ParsedSport('MMA');
       
        $parsed_sport->addFetchedProp(new ParsedProp('Fighter OpOne vs Fighter Optwo - Fight goes the distance', 'Fight does not go the distance', -250, 200));
        $parsed_sport->addFetchedProp(new ParsedProp('Fighter OpOne vs Fighter Optwo - Fight goes the distance', 'Fight does not go the distance', -250, 200)); //Straight dupe
        $parsed_sport->addFetchedProp(new ParsedProp('Fighter OpOne vs Fighter Optwo - Fight goes the distance', 'Fight does not go the distance', -350, 150)); //Both sides worse
        $parsed_sport->addFetchedProp(new ParsedProp('Fighter OpOne vs Fighter Optwo - Fight goes the distance', 'Fight does not go the distance', -350, 200)); //One side worse
        $parsed_sport->addFetchedProp(new ParsedProp('Fighter OpOne vs Fighter Optwo - Fight goes the distance', 'Fight does not go the distance', -250, 100)); //Other side worse

        $parsed_sport = $this->op->removePropDupes($parsed_sport);

        $this->assertEquals(
            1,
            count($parsed_sport->getFetchedProps())
        );
        $this->assertEquals(
            200,
            $parsed_sport->getFetchedProps()[0]->getMoneyLine(1)
        );       
        $this->assertEquals(
            -250,
            $parsed_sport->getFetchedProps()[0]->getMoneyLine(2)
        );      
    }

    public function testMatchMatchups() 
    {
        $parsed_sport = new ParsedSport('MMA');
        $parsed_matchup = new ParsedMatchup('Fighter OpOne', 'Fighter OpTwo', -400, 300);
        $parsed_sport->addParsedMatchup($parsed_matchup);

        $match_results = $this->op->matchMatchups($parsed_sport->getParsedMatchups());

        $this->assertEquals(
            true,
            $match_results[0]['match_result']['status']
        );
        $this->assertEquals(
            $parsed_matchup->getTeamName(1),
            $match_results[0]['matched_matchup']->getTeam(1)
        );
        $this->assertEquals(
            $parsed_matchup->getTeamName(2),
            $match_results[0]['matched_matchup']->getTeam(2)
        );
    }

    public function testUpdateMatchedMatchups()
    {
        $parsed_sport = new ParsedSport('MMA');
        $parsed_matchup = new ParsedMatchup('Fighter OpOne', 'Fighter OpTwo', -400, 300);
        $parsed_sport->addParsedMatchup($parsed_matchup);

        $match_results = $this->op->matchMatchups($parsed_sport->getParsedMatchups());
        
        $odds = OddsHandler::getAllLatestOddsForFight($this->matchup->getID());
        $this->assertEquals(
            0,
            count($odds)
        );
        $this->op->updateMatchedMatchups($match_results);
        $odds = OddsHandler::getAllLatestOddsForFight($this->matchup->getID());
        $this->assertEquals(
            1,
            count($odds)
        );
        $this->assertEquals(
            $this->matchup->getID(),
            $odds[0]->getFightID()
        );
        $this->assertEquals(
            $odds[0]->getOdds(1),
            $parsed_matchup->getMoneyline(1)
        );
        $this->assertEquals(
            $odds[0]->getOdds(2),
            $parsed_matchup->getMoneyline(2)
        );
        $this->op->updateMatchedMatchups($match_results);
        $odds = OddsHandler::getAllLatestOddsForFight($this->matchup->getID());
        $this->assertEquals(
            1,
            count($odds)
        );
    }

    
}
