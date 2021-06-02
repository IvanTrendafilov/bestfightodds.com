<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use BFO\General\GraphHandler;
use BFO\General\BookieHandler;
use BFO\General\EventHandler;
use BFO\General\Alerter;
use BFO\Caching\CacheControl;

class APIController
{
    // constructor receives container instance
    public function __construct()
    {
    }

    public function __invoke(Request $request, Response $response)
    {
        return $response;
    }

    public function addAlert(Request $request, Response $response)
    {
        $args = $request->getParsedBody();

        //Validate input
        if (!isset($args['alertFight'], $args['alertFighter'], $args['alertMail'], $args['alertOdds'], $args['alertBookie'], $args['alertOddsType'])) {
            $response->getBody()->write('invalid input');
            return $response;
        }

        $result = Alerter::addNewAlert($args['alertFight'], $args['alertFighter'], $args['alertMail'], $args['alertOdds'], $args['alertBookie'], $args['alertOddsType']);
        $response->getBody()->write((string) $result);
        return $response;
    }

    public function getGraphData(Request $request, Response $response)
    {
        $args = $request->getQueryParams();

        //Validate input, only numeric values allowed
        foreach ($args as &$arg) {
            if (!is_numeric($arg)) {
                $response->getBody()->write('invalid input');
                return $response;
            } else {
                $arg = intval($arg);
            }
        }

        $cache_key = $this->getGraphCacheKey($args);
        $return_content = '';

        if (CacheControl::isPageCached($cache_key)) {
            //Cached, serve cached copy
            $return_content = CacheControl::getCachedPage($cache_key);
        } else {
            //Not cached, process
            $return_content = '[]';
            if ($args['p']) {
                $odds = null;
                if (isset($args['pt'])) {
                    //For prop
                    if (isset($args['b'])) {
                        //For specific bookie
                        if (isset($args['e'])) {
                            //Event prop
                            $odds = GraphHandler::getEventPropData((int) $args['e'], (int) $args['b'], (int) $args['pt']);
                        } else {
                            //Regular prop
                            $odds = GraphHandler::getPropData((int) $args['m'], (int) $args['b'], (int) $args['pt'], (int) $args['tn']);
                        }
                    } else {
                        //Mean
                        if (isset($args['e'])) {
                            //Event prop
                            $odds = GraphHandler::getEventPropIndexData((int) $args['e'], (int) $args['p'], (int) $args['pt']);
                        } else {
                            //Regular prop
                            $odds = GraphHandler::getPropIndexData((int) $args['m'], (int) $args['p'], (int) $args['pt'], (int) $args['tn']);
                        }
                    }
                } else {
                    //For normal matchup
                    if (isset($args['b'])) {
                        //For specific bookie
                        $odds = GraphHandler::getMatchupData((int) $args['m'], (int) $args['b']);
                    } else {
                        $odds = GraphHandler::getMatchupIndexData((int) $args['m'], (int) $args['p']);
                    }
                }

                if ($odds != null) {
                    //Convert to JSON and return
                    $bookie_name = 'Mean';
                    if (isset($args['b'])) {
                        $bookie_name = BookieHandler::getBookieByID((int) $args['b'])->getName();
                    }

                    $return_data = [
                        'name' => $bookie_name,
                        'data' => []
                    ];

                    if (isset($args['e'])) {
                        $event = EventHandler::getEvent($args['e'], true);
                    } else {
                        $event = EventHandler::getEvent(EventHandler::getMatchup((int) $args['m'])->getEventID(), true);
                    }

                    foreach ($odds as $odds_key => $odds_obj) {
                        //TODO: Temporary measure to prevent bot data scraping
                        if ($_SERVER['HTTP_USER_AGENT'] == 'python-requests/2.24.0' || $_SERVER['REMOTE_ADDR'] == '77.4.141.237' || $_SERVER['REMOTE_ADDR'] == '77.2.84.76' || $_SERVER['REMOTE_ADDR'] == '77.4.124.22' || $_SERVER['REMOTE_ADDR'] == '77.9.20.120') {
                            $scale = pow(10, 3);
                            $dummy = mt_rand(1 * $scale, 3 * $scale) / $scale;

                            $return_data['data'][] = [
                                'x' => (new DateTime($odds_obj->getDate()))->getTimestamp() * 1000,
                                'y' => $dummy
                            ];
                        //error_log('Giving bogus data');
                        } else {
                            $return_data['data'][] = [
                                'x' => (new DateTime($odds_obj->getDate()))->getTimestamp() * 1000,
                                'y' => $odds_obj->moneylineToDecimal($odds_obj->getOdds($args['p']), true)
                            ];
                        }

                        if ($odds_key == 0) {
                            $return_data['data'][0]['dataLabels'] = ['x' => 9];
                        }

                        if ($odds_key == count($odds) - 1) {
                            $return_data['data'][$odds_key]['dataLabels'] = ['x' => -9];
                        }
                    }
                    //Add last odds with current date if this is an upcoming event
                    if ($event != null) {
                        $current_time = (new DateTime(''));
                        $return_data['data'][] = [
                            'x' => $current_time->getTimestamp() * 1000,
                            'y' => $odds[count($odds) - 1]->moneylineToDecimal($odds[count($odds) - 1]->getOdds($args['p']), true),
                            'dataLabels' => ['x' => -9]
                        ];
                    }

                    //"Encrypt" with ROT47 + base64 before returning
                    $return_content = $this->encryptResponse('[' . json_encode($return_data) . ']');
                    CacheControl::cachePage($return_content, $cache_key . '.php');
                }
            }
        }
        $response->getBody()->write($return_content);
        return $response;
    }

    private function getGraphCacheKey($args)
    {
        //Determine cache key. Format used is:
        //graphdata-m-b-p-pt-tn-e
        //Matchup-Bookie-Posprop-Proptype-TeamNum-Event
        $key = 'graphdata-';
        if (isset($args['pt'])) {
            //For prop
            if (isset($args['b'])) {
                //For specific bookie
                if (isset($args['e'])) {
                    //Event prop
                    $key .= 'x-' . $args['b'] . '-' . $args['p'] . '-' . $args['pt'] . '-x-' . $args['e'];
                } else {
                    //Regular prop
                    $key .= $args['m'] . '-' . $args['b'] . '-' . $args['p'] . '-' . $args['pt'] . '-' . $args['tn'];
                }
            } else {
                //Mean
                if (isset($args['e'])) {
                    $key .= 'x-x-' . $args['p'] . '-' . $args['pt'] . '-x-' . $args['e'];
                } else {
                    $key .= $args['m'] . '-x-' . $args['p'] . '-' . $args['pt'] . '-' . $args['tn'];
                }
            }
        } else {
            //For normal matchup
            if (isset($args['b'])) {
                //For specific bookie
                $key .= $args['m'] . '-' . $args['b'] . '-' . $args['p'] . '-x-';
            } else {
                $key .= $args['m'] . '-x-' . $args['p'] . '-x-';
            }
        }
        return $key;
    }

    private function encryptResponse($sResponse)
    {
        //"Encrypts" the value with ROT47 + base64. The equivalent decrypt is performed in frontend javascript. This does not secure the data but just makes it harder for scrapers
        return base64_encode(strtr($sResponse, '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~', 'PQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNO'));
    }
}
