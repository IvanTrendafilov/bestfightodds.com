<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once 'bfo/bootstrap.php';

use Psr\Log\NullLogger;
use BFO\General\EventHandler;
use BFO\General\OddsHandler;
use BFO\General\BookieHandler;
use BFO\DataTypes\Fight;
use BFO\DataTypes\Event;
use BFO\DataTypes\FightOdds;
use BFO\DataTypes\PropTemplate;
use BFO\Parser\ParsedProp;
use BFO\Parser\PropProcessor;

final class CreatePropOddsTest extends TestCase
{
    private $event = null;
    private $matchup = null;
    private $bookie_id = null;

    private $temp_templates = null;

    public function setUp(): void
    {
        //Retrieve a bookie
        $this->bookie_id = BookieHandler::getAllBookies()[0]->getID();

        //Create a temporary event
        $event_name = 'Test Event Create Odds ' . ((string) time());
        $event_date = date('Y-m-d');
        $this->event = EventHandler::addNewEvent(new Event(0, $event_date, $event_name, true));

        //Create a temporary matchup with odds
        $matchup = new Fight(0, 'Propfighter Propone', 'Propfighter Proptwo', $this->event->getID());
        $matchup_id = EventHandler::createMatchup($matchup);

        $this->matchup = EventHandler::getMatchup($matchup_id);
        $odds_obj = new FightOdds($this->matchup->getID(), $this->bookie_id, '-150', '+200', -1);
        OddsHandler::addNewFightOdds($odds_obj);
    }

    public function tearDown(): void
    {
        EventHandler::removeEvent($this->event->getID());
        foreach ($this->temp_templates as $template) {
            BookieHandler::deletePropTemplate($template);
        }
        
    }

    public function testMatchPropToTemplate(): void
    {
        $proptype_id = 1; //Fight goes to decision
        $fieldstype_id = 1; //Lastname vs lastname

        //Create prop template
        $template = new PropTemplate(0, $this->bookie_id, 'UNITTEST FIGHT GOES TO DECISION - <T> vs. <T>', 'UNITTEST FIGHT DOES NOT GO TO DECISION', $proptype_id, 1, '');
        $new_template = BookieHandler::addNewPropTemplate($template);
        $this->temp_templates[] = $new_template;

        $this->assertThat($new_template, $this->logicalAnd(
            $this->isType('int'), 
            $this->greaterThan(0)
        ));

        //$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.testlog.' . time() . '.log']);
        $pp = new PropProcessor(new NullLogger, $this->bookie_id);
        $parsed_prop = new ParsedProp('UNITTEST FIGHT GOES TO DECISION - Propone vs. Proptwo', 'UNITTEST Fight does not go to decision', '-120', '+110', '');

        $result = $pp->matchSingleProp($parsed_prop);

        $this->assertEquals(
            true,
            $result['status']
        );
        $this->assertEquals(
            $new_template,
            $result['template']->getID()
        );

        $this->assertEquals(
            $this->matchup->getID(),
            $result['matchup']['matchup_id']
        );

        $this->assertEquals(
            'matchup',
            $result['matched_type']
        );

        $result = BookieHandler::deletePropTemplate($new_template);
        $this->assertEquals(
            true,
            $result
        );
    }

    public function testNoMatchPropToTemplate(): void
    {
        $proptype_id = 1; //Fight goes to decision
        $fieldstype_id = 1; //Lastname vs lastname

        //Create prop template
        $template = new PropTemplate(0, $this->bookie_id, 'UNITTEST FIGHT ENDS IN ROUND 1 - <T> vs. <T>', 'UNITTEST FIGHT DOES NOT END IN ROUND 1', $proptype_id, 1, '');
        $new_template = BookieHandler::addNewPropTemplate($template);
        $this->temp_templates[] = $new_template;
        $this->assertThat($new_template, $this->logicalAnd(
            $this->isType('int'), 
            $this->greaterThan(0)
        ));

        $pp = new PropProcessor(new NullLogger, $this->bookie_id);
        $parsed_prop = new ParsedProp('UNITTEST Fight is a draw - Propone vs. Proptwo', 'UNITTEST Fight does not go to decision', '-120', '+110', '');

        $result = $pp->matchSingleProp($parsed_prop);

        $this->assertEquals(
            false,
            $result['status']
        );
        $this->assertEquals(
            'no_template_found',
            $result['fail_reason']
        );

        $result = BookieHandler::deletePropTemplate($new_template);
        $this->assertEquals(
            true,
            $result
        );
    }

    public function testNoDuplicatePropTemplates(): void
    {
        $proptype_id = 1; //Fight goes to decision
        $fieldstype_id = 1; //Lastname vs lastname

        //Create prop template
        $template = new PropTemplate(0, $this->bookie_id, 'UNITTEST DUPE FIGHT ENDS IN ROUND 1 - <T> vs. <T>', 'UNITTEST DUPE FIGHT DOES NOT END IN ROUND 1', $proptype_id, 1, '');
        $new_template = BookieHandler::addNewPropTemplate($template);
        $this->temp_templates[] = $new_template;
        $this->assertThat($new_template, $this->logicalAnd(
            $this->isType('int'), 
            $this->greaterThan(0)
        ));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Duplicate entry');

        $template = new PropTemplate(0, $this->bookie_id, 'UNITTEST DUPE FIGHT ENDS IN ROUND 1 - <T> vs. <T>', 'UNITTEST DUPE FIGHT DOES NOT END IN ROUND 1', $proptype_id, 1, '');
        $new_template = BookieHandler::addNewPropTemplate($template);
        $this->temp_templates[] = $new_template;
        $this->assertIsNotInt($new_template);
        $this->assertEquals(
            null,
            $new_template
        );
    }
}
