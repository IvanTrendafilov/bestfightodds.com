<?php

require_once('JSON.php');
require_once('twitteroauth.php');

/**
 * Class used to integrate with Twitter
 * TODO: Fix logging
 *
 * @author Christian Nordvaller
 */
class Twitterer
{

    private $m_bDebug = false;
    private $m_sConsumerKey;
    private $m_sConsumerSecret;
    private $m_sOAuthToken;
    private $m_sOAuthTokenSecret;

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
        if ($this->m_bDebug == true)
        {
            echo "Posting (" . strlen($a_sMessage) . "): " . $a_sMessage . "\n\r
";
            return true;
        }

        $rConnection = new TwitterOAuth($this->m_sConsumerKey,
                        $this->m_sConsumerSecret,
                        $this->m_sOAuthToken,
                        $this->m_sOAuthTokenSecret);
        $rResult = $rConnection->post('statuses/update', array('status' => $a_sMessage));

        if (isset($rResult->error))
        {
            //If error returned is "Status is a duplicate." we treat this as a success
            if ($rResult->error == 'Status is a duplicate.')
            {
                return true;
            }
            echo "Error: " . $rResult->error . "\n\r<br />";
            return false;
        }

        return true;
    }

    public function setDebugMode($a_bDebug)
    {
        $this->m_bDebug = $a_bDebug;
    }

}

?>
