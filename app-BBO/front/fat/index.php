<?php


//Global: INTL-LBDIRECTORY:19174


$timeslots = array(
  '17:00',
  '17:15',
  '17:30',
  '17:45',
  '18:00',
  '18:15',
  '18:30',
  '18:45',
  '19:00',
  '19:15',
  '19:30',
  '19:45',
  '20:00',
  '20:15',
  '20:30',
  '20:45',
  '21:00',
  '21:15',
  '21:30',
  '21:45',
  '22:00',
  '22:15',
  '22:30',
  '22:45',
  '23:00');


$restaurants = array(
  array('name' => 'Miss Voon',
      'code' => 'SE-RES-STUREGATAN_127465:27913',
      'areaid' => '22263'),
  array('name' => 'Sturehof',
      'code' => 'SE-RES-STUREHOF_100238:4268',
      'areaid' => '6158'),
  array('name' => 'Grill',
      'code' => '',
      'areaid' => '11290'));


//$res = fetchTimes('SE-RES-STUREHOF_100238:4268');

function fetchTimes($conn, $areaid)
{
  $conn = 'INTL-LBDIRECTORY:19174';
  $endpoint = 'http://webservices.livebookings.com/Ingrid/index.asmx?WSDL';
$mSoapClient = new SoapClient($endpoint, array('trace' => 1));
  $mLanguages = "en-GB";

    $dateString = '2013-09-21T20:00:00';
     $session = 'DINNER';

$requestObject = array('ConnectionId' => $conn,
  'DateAndTime' => $dateString,
  'Size' => 2,
  'RestaurantAreaId' => $areaid,
  'Session' => $session,
  'ByPassCache' => true);
      
    $availabilities = $mSoapClient->GetAvailability($requestObject);

    $times = array();
    foreach ($availabilities->Availability->Result as $result)
    {
      $times[] = substr($result->time, 11, 5);
    }
    return $times;

}


?>


<!DOCTYPE html>
<!--[if IE 8]> 				 <html class="no-js lt-ie9" lang="en" > <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en" > <!--<![endif]-->

<head>
	<meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <title>Find-a-table</title>

  
  <link rel="stylesheet" href="css/foundation.css">
  

  <script src="js/vendor/custom.modernizr.js"></script>

</head>
<body>

	<div class="row">
		<div class="large-12 columns">
			<h2>Find-a-table</h2>
      <p>2013-09-21 - Dinner</p>
			<hr />
		</div>
	</div>


  <?php

  foreach ($restaurants as $rest)
  {

    $restimes = fetchTimes($rest['code'], $rest['areaid']);

?>
  <div class="row">
   
     <div class="large-1 columns"><p style="float: left;"><?php echo $rest['name']; ?></p></div>
     <div class="large-11 columns">
      <div class="button-bar">
      <ul class="button-group">
      <?php

      foreach ($timeslots as $timeslot)
      {
        if (in_array($timeslot, $restimes))
        {
          echo '<li><a href="#" class="tiny button">' . substr($timeslot, 0, 5) . '</a></li>';
        }
        else
        {
         echo '<li><a href="#" class="tiny button disabled">' . substr($timeslot, 0, 5) . '</a></li>'; 
        }
      }

      ?>
      </ul>
       </div>
    </div>
      </div>
<?php
  }


  ?>


  <script>
  document.write('<script src=' +
  ('__proto__' in {} ? 'js/vendor/zepto' : 'js/vendor/jquery') +
  '.js><\/script>')
  </script>
  
  <script src="js/foundation.min.js"></script>
  <!--
  
  <script src="js/foundation/foundation.js"></script>
  
  <script src="js/foundation/foundation.alerts.js"></script>
  
  <script src="js/foundation/foundation.clearing.js"></script>
  
  <script src="js/foundation/foundation.cookie.js"></script>
  
  <script src="js/foundation/foundation.dropdown.js"></script>
  
  <script src="js/foundation/foundation.forms.js"></script>
  
  <script src="js/foundation/foundation.joyride.js"></script>
  
  <script src="js/foundation/foundation.magellan.js"></script>
  
  <script src="js/foundation/foundation.orbit.js"></script>
  
  <script src="js/foundation/foundation.reveal.js"></script>
  
  <script src="js/foundation/foundation.section.js"></script>
  
  <script src="js/foundation/foundation.tooltips.js"></script>
  
  <script src="js/foundation/foundation.topbar.js"></script>
  
  <script src="js/foundation/foundation.interchange.js"></script>
  
  <script src="js/foundation/foundation.placeholder.js"></script>
  
  <script src="js/foundation/foundation.abide.js"></script>
  
  -->
  
  <script>
    $(document).foundation();
  </script>
  <style>
  .row { min-width:100% !important; }
  </style>
</body>
</html>
