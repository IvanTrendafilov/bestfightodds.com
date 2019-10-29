<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <link href="js/nvd3/nv.d3.css" rel="stylesheet" type="text/css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.2/d3.min.js" charset="utf-8"></script>
    <script src="js/nvd3/nv.d3.js"></script>

    <style>
        text {
            font: 12px sans-serif;
        }
        svg {
            display: block;
        }
        html, body, svg {
            margin: 0px;
            padding: 0px;
            height: 200px;
            width: 500px;
        }
    </style>
</head>
<body>
<img src="http://localhost:8080/ajax/getGraph.php?bookieID=1&fighter=1&fightID=7235&oddsType=1" /><br />

<?php


require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/caching/class.CacheControl.php');
require_once('lib/bfocore/utils/graphtool/class.GraphTool.php');
require_once('config/inc.generalConfig.php');

$iFightID = 7235;
$iBookieID = 1;



$oMatchup = EventHandler::getFightByID($iFightID);
$oEvent = EventHandler::getEvent($oMatchup->getEventID());
$sEventDate = $oEvent->getDate();
$aFightOdds = EventHandler::getAllOddsForFightAndBookie($iFightID, $iBookieID);

$sJSON = '[{"key": "Line", "values": [ ';

foreach ($aFightOdds as $oFO)
{
    $sJSON .= '[ ' . (strtotime($oFO->getDate()) * 1000) .  ' , ' . $oFO->getFighterOddsAsDecimal(1) . '],';
}
$sJSON = substr($sJSON, 0, -1) . ']}]';


?>

<svg id="chart1"></svg>

<script>


function singleDecimalToML(odds)
{
    if (odds >= 2)
    {
        return '+' + Math.round(100 * (odds - 1));
    }
    else if (odds < 2)
    {
        return '' + Math.round(-100/(odds - 1));
    }
    else
    {
        return 'error';
    }
}

<?php

echo 'var data = ' . $sJSON;

?>
   
  nv.addGraph(function() {
    var chart = nv.models.lineChart()
                  .x(function(d) { return d[0] })
                  .y(function(d) { return d[1] })
                  .color(d3.scale.category10().range())
                  .useInteractiveGuideline(true)
                  .interpolate("step-after")
                  ;

     chart.xAxis
    .tickFormat(function(d) { 
          return d3.time.format('%d %b')(new Date(d)) 
    });

    chart.yAxis
        //.tickFormat(d3.format('.3r'));
    .tickFormat(function(d) {

          return singleDecimalToML(d);
    });

    d3.select('#chart1')
        .datum(data)
        .call(chart);

    //TODO: Figure out a good way to do this automatically
    nv.utils.windowResize(chart.update);

    return chart;
  });


</script>
</body>
</html>