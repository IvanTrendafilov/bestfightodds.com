<?php

require_once('lib/bfocore/general/class.Alerter.php');

$iAlertCount = Alerter::getAlertCount();
echo '<a href="?p=showAlerts">Alerts stored: ' . $iAlertCount . '</a>';

echo '<br /><br />';

require_once('app/front/cnadm/pages/viewUnmatched.php');

?>