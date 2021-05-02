<?php

namespace BFO\Utils\Twitter;

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Class used to integrate with Twitter API
 */
class Twitterer
{
    private $m_bDebug = false;
    private $m_sConsumerKey;
    private $m_sConsumerSecret;
    private $m_sOAuthToken;
    private $m_sOAuthTokenSecret;

    private $logger;

    /**
     * Constructor
     *
     * @param String $a_sConsumerKey Consumer Key
     * @param String $a_sConsumerSecret Consumer Secret
     * @param String $a_sOAuthToken OAuth Token
     * @param String $a_sOAuthTokenSecret OAuth Token Secret
     */
    public function __construct($a_sConsumerKey, $a_sConsumerSecret, $a_sOAuthToken, $a_sOAuthTokenSecret)
    {
        $this->logger = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::DEBUG, ['filename' => 'twitter.log']);

        $this->m_sConsumerKey = $a_sConsumerKey;
        $this->m_sConsumerSecret = $a_sConsumerSecret;
        $this->m_sOAuthToken = $a_sOAuthToken;
        $this->m_sOAuthTokenSecret = $a_sOAuthTokenSecret;
    }

    /**
     * Updates Twitter status
     *
     * @param string $a_sMessage Message to set status to
     * @return boolean If update was successful or not
     */
    public function updateStatus($a_sMessage)
    {
        if ($this->m_bDebug == true) {
            $this->logger->debug("Simulating posting (" . strlen($a_sMessage) . "): " . $a_sMessage);
            return true;
        }

        $rConnection = new TwitterOAuth(
            $this->m_sConsumerKey,
            $this->m_sConsumerSecret,
            $this->m_sOAuthToken,
            $this->m_sOAuthTokenSecret
        );
        $rResult = $rConnection->post('statuses/update', array('status' => $a_sMessage));

        if (isset($rResult->errors)) {
            if ($rResult->errors[0]->code == '187') { //Duplicate, treated as success
                $this->logger->info("Duplicate post (OK): (" . strlen($a_sMessage) . "): " . $a_sMessage);
                return true;
            }
            $this->logger->info("Error for tweet " . $a_sMessage . " (" . strlen($a_sMessage) . "): " . $rResult->errors[0]->code . ': ' . $rResult->errors[0]->message);
            return false;
        }

        $this->logger->info("Posted (" . strlen($a_sMessage) . "): " . $a_sMessage);
        return true;
    }

    public function setDebugMode($a_bDebug)
    {
        $this->m_bDebug = $a_bDebug;
    }
}
