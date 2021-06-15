<?php

/**
 * BestFightOdds Instance of BFO\Jobs\OddsJob. See BFO\Jobs\OddsJob for more details
 */
require_once __DIR__ . "/../bootstrap.php";

$logger = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::INFO, ['filename' => 'cron.oddsjob.' . time() . '.log']);

$oj = new BFO\Jobs\OddsJob($logger);
$oj->run();