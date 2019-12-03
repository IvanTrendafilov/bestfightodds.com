<?php


require_once('config/inc.config.php');
require_once('lib/bfocore/utils/aws-ses/class.SESMailer.php');

if ($_GET['run'] == 'true')
{
    $mailer = new SESMailer(MAIL_SMTP_HOST, MAIL_SMTP_PORT, MAIL_SMTP_USERNAME, MAIL_SMTP_PASSWORD);
    $result = $mailer->sendMail(ALERTER_MAIL_SENDER_MAIL, ALERTER_SITE_NAME, ALERTER_ADMIN_ALERT, 'Test mail from ' . ALERTER_SITE_NAME, '<b>This is a test mail</b><br /><br />Just testing', 'This is test mail\n\r\n\rJust testing');
    echo 'Result: ' . $result . '<br/><br/>';
}

?>

<a href="/cnadm/?p=testMail&run=true">Send test mail</a>
