<?php

require_once('lib/bfocore/general/class.Alerter.php');

require_once('app/front/cnadm/pages/viewUnmatched.php');

echo '<br />';

$iAlertCount = Alerter::getAlertCount();
echo '<a href="?p=showAlerts">Alerts stored: ' . $iAlertCount . '</a>';

?>