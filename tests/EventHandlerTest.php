<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once 'bfo/bootstrap.php';
require_once 'bfo/config/Ruleset.php';

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
use BFO\DB\EventDB;

final class EventHandlerTest extends TestCase
{
    private $event = null;
    private $matchup = null;
    private $bookie_id = null;
    private $op = null;
    private $template_id = null;

    public function setUp(): void
    {

    }

    public function tearDown(): void
    {

    }
}
