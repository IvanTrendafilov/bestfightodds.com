<?php

require_once('config/inc.redditConfig.php');


/*$rReddit = new Redditor();*/

/*$rReddit->login();*/
//$rReddit->createPost('Testing', 'Just testing 123');
/*$rReddit->updatePost('t3_296olk', 'Test 123 Updated 2');*/

/**
 * Class used to integrate with Reddit
 * TODO: Fix logging
 *
 * @author Christian Nordvaller
 */
class Redditor
{
	private $rModHash = null;
	private $rSession = null;
	private $sAPIURL = 'http://www.reddit.com';
	private $bLoggedIn = false;

	public function login()
	{
		$sURL = $this->sAPIURL . '/api/login';
		$aPostData = array('api_type' => 'json',
							'rem' => 'true',
							'user' => REDDIT_LOGIN,
							'passwd' => REDDIT_PASSWORD);

		$resp = null;
		try
		{
			$resp = $this->callURL($sURL, $aPostData);	
			$this->rModHash = $resp->json->data->modhash;
			$this->rSession = $resp->json->data->cookie;
			$this->bLoggedIn = true;
			echo 'Logged in ';
			return true;
		}
		catch (Exception $e)
		{
			echo 'Error!: ' . $e;
			$this->bLoggedIn = false;
			return false;
		}
	}

	public function isLoggedIn()
	{
		return $this->bLoggedIn;
	}

	public function createPost($a_sTitle, $a_sText)
	{
		$sURL = $this->sAPIURL . '/api/submit';
		$aPostData = array('api_type' => 'json',
							'kind' => 'self',
							'extension' => '',
							'iden' => '',
							'resubmit' => 'false',
							'save' => 'false',
							'sendreplies' => 'false',
							'sr' => REDDIT_SUBREDDIT,
							'text' => $a_sText,
							'then' => 'comments',
							'title' => $a_sTitle,
							'url' => '');

		$resp = null;
		try
		{
			$resp = $this->callURL($sURL, $aPostData);
			var_dump($resp);
			return true;
		}
		catch (Exception $e)
		{
			echo 'Error!: ' . $e;
			return false;
		}
	}

	public function updatePost($a_sPostID, $a_sNewText)
	{
		$sURL = $this->sAPIURL . '/api/editusertext';
		$aPostData = array('api_type' => 'json',
							'text' => $a_sNewText,
							'thing_id' => $a_sPostID);

		$resp = null;
		try
		{
			$resp = $this->callURL($sURL, $aPostData);
			echo 'Post updated ok ';
			var_dump($resp);
			return true;
		}
		catch (Exception $e)
		{
			echo 'Error!: ' . $e;
			return false;
		}

	}

    private function callURL($a_sURL, $a_aPostFields) 
    {
    	if (REDDIT_DEV_MODE == true)
    	{
    		echo $a_sURL . ' ? ' . http_build_query($a_aPostFields);
    		return true;
    	}
        $ch = curl_init($a_sURL);
      
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIE => "reddit_session={$this->rSession}",
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'bestfightodds bot/1.0'
        );
        
        if ($a_aPostFields != null){
            $options[CURLOPT_POSTFIELDS] = http_build_query($a_aPostFields);
            $options[CURLOPT_CUSTOMREQUEST] = "POST";  
        }
        if ($this->rModHash != null)
        {
        	$options[CURLOPT_HTTPHEADER] = array('X-Modhash: ' . $this->rModHash);
        }
        
        curl_setopt_array($ch, $options);
        $res = curl_exec($ch);
        $response = json_decode($res);
        if (count($response->json->errors) > 0)
        {
        	var_dump($response);
        	throw new Exception("Error!");
        }
        curl_close($ch);
        return $response;
    }

}

?>
