<?php

namespace BFO\Utils\AWS_SES;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class SESMailer
{
    private $mailer;
    private $logger;

    public function __construct($smtp_host, $smtp_port, $smtp_username, $smtp_password)
    {
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host       = $smtp_host;
        $this->mailer->Port       = $smtp_port;
        $this->mailer->Username   = $smtp_username;
        $this->mailer->Password   = $smtp_password;
        $this->mailer->SMTPAuth   = true;
        $this->mailer->SMTPSecure = 'tls';
        //$this->mailer->addCustomHeader('X-SES-CONFIGURATION-SET', $configurationSet);

        $this->logger = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::DEBUG, ['filename' => 'sesmailer.log']);
    }

    public function sendMail($sender_mail, $sender_name, $recipient_mail, $subject, $body_html, $body_text)
    {
        try {
            $this->mailer->setFrom($sender_mail, $sender_name);

            // Specify the message recipients.
            $this->mailer->addAddress($recipient_mail);

            // Specify the content of the message.
            $this->mailer->isHTML(false);
            $this->mailer->Subject    = $subject;
            $this->mailer->Body       = $body_text;
            //$this->mailer->AltBody    = $body_text;
            $this->mailer->Send();
            $this->logger->info('Mail sent to ' . $recipient_mail . ': ' . $subject);
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $this->logger->error("An error occurred. {$e->errorMessage()}"); //Catch errors from PHPMailer.
            return false;
        } catch (Exception $e) {
            $this->logger->error("An error occurred. {$this->mailer->ErrorInfo}"); //Catch errors from SES
            return false;
        }
    }
}
