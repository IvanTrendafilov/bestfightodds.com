<?php

namespace BFO\Utils\Twitter;

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Class used to integrate with Twitter API
 */
class Tweeter
{
    private $dev_mode = false;
    private $consumer_key;
    private $consumer_secret;
    private $oauth_token;
    private $oauth_token_secret;

    private $logger;

    /**
     * Constructor
     *
     * @param String $consumer_key Consumer Key
     * @param String $consumer_secret Consumer Secret
     * @param String $oauth_token OAuth Token
     * @param String $oauth_token_secret OAuth Token Secret
     */
    public function __construct($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret)
    {
        $this->logger = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::DEBUG, ['filename' => 'twitter.log']);

        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->oauth_token = $oauth_token;
        $this->oauth_token_secret = $oauth_token_secret;
    }

    /**
     * Updates Twitter status
     *
     * @param string $message Message to set status to
     * @return boolean If update was successful or not
     */
    public function updateStatus($message)
    {
        if ($this->dev_mode == true) {
            $this->logger->debug("Simulating posting (" . strlen($message) . "): " . $message);
            return true;
        }

        $connection = new TwitterOAuth(
            $this->consumer_key,
            $this->consumer_secret,
            $this->oauth_token,
            $this->oauth_token_secret
        );
        $result = $connection->post('statuses/update', array('status' => $message));

        if (isset($result->errors)) {
            if ($result->errors[0]->code == '187') { //Duplicate, treated as success
                $this->logger->info("Duplicate post (OK): (" . strlen($message) . "): " . $message);
                return true;
            }
            $this->logger->info("Error for tweet " . $message . " (" . strlen($message) . "): " . $result->errors[0]->code . ': ' . $result->errors[0]->message);
            return false;
        }

        $this->logger->info("Posted (" . strlen($message) . "): " . $message);
        return true;
    }

    public function setDebugMode($debug_on)
    {
        $this->dev_mode = $debug_on;
    }
}
