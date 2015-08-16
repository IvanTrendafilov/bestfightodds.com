<?php

header("Content-type: text/javascript");
//header("Cache-Control: max-age=900, must-revalidate");

//Libs
include_once('jquery-1.11.3.min.js');
include_once('jquery.cookie.js');
include_once('highcharts.js');
include_once('highcharts-more.js');
include_once('fastclick-min.js');

//BFO
include_once('bfo_main.js');
include_once('alerts_3.js');
include_once('bfo_charts.js');

//TODO: To replace above once compiler is running as expected
/*include_once('bfo_main_optimized.js'); 
include_once('bfo_charts_optimized.js'); */

?>
