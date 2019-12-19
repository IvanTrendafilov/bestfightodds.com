<?php

require 'vendor/autoload.php';

class SendgridMailer 
{
    private $sendgrid;
    private $logger;

    public function __construct($key)
    {
        $this->sendgrid = new \SendGrid($key);
        $this->logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::DEBUG, ['filename' => 'mail.txt']);
    }

    public function sendMail($sender_mail, $sender_name, $recipient_mail, $subject, $body_html, $body_text)
    {
        $email = new \SendGrid\Mail\Mail(); 
        $email->setFrom($sender_mail, $sender_name);
        $email->setSubject($subject);
        $email->addTo("cnordvaller@gmail.com");
        $email->addContent("text/plain", $body_text);
        $email->addContent("text/html", $body_html);
        
        try {
            $response = $this->sendgrid->send($email);
            $this->logger->info('Mail sent to ' . $recipient_mail . ': ' . $subject . ' | Status: ' . $response->statusCode());
            return true;
        } catch (Exception $e) {
            $this->logger->error("An error occurred. {$e->getMessage()}");
            return false;
        }
    }

}

?>