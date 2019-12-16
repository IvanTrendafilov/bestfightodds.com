<?php

require 'vendor/autoload.php';
require_once('config/inc.config.php');
require_once('lib/bfocore/dao/class.FacebookDAO.php');

class FacebookHandler
{
	private $logger;
	private $fb;

	public function __construct() 
	{
		$this->logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::DEBUG, ['filename' => 'facebook.log']);

		if (FACEBOOK_DEV_MODE == false)
		{
			$this->fb = new Facebook\Facebook([
			  'app_id' => FACEBOOK_APP_ID,
			  'app_secret' => FACEBOOK_APP_SECRET,
			  'default_graph_version' => 'v2.6',
			  'default_access_token' => FACEBOOK_ACCESS_TOKEN
			]);
		}
	}

	public function postToFeed($message, $link)
	{
		if (FACEBOOK_DEV_MODE == true)
		{
			echo 'Posted: ' . $message . ' (' . $link . ')
';
			return true;
		}
		$response = null;
		try 
		{
		  $response = $this->fb->post('/' . FACEBOOK_PAGEID . '/feed', ['message' => $message, 'link' => $link]);
		} 
		catch(Facebook\Exceptions\FacebookResponseException $e) 
		{
		  // When Graph returns an error
			$this->logger->error('Graph returned an error: ' . $e->getMessage());
			return false;
		} 
		catch(Facebook\Exceptions\FacebookSDKException $e) 
		{
		  // When validation fails or other local issues
		  	$this->logger->error('Facebook SDK returned an error: ' . $e->getMessage());
		  	return false;
		}
		$this->logger->info('Posted: ' . $message . ' (' . $link . ')');
		return true;
	}

	public function saveMatchupAsPosted($matchup_id, $skipped = false)
	{
		return FacebookDAO::saveMatchupAsPosted($matchup_id, $skipped);
	}

	public function saveEventAsPosted($event_id, $skipped = false)
	{
		return FacebookDAO::saveEventAsPosted($event_id, $skipped);
	}

	public function getUnpostedMatchups()
	{
		return FacebookDAO::getUnpostedMatchups();
	}
	
	public function getUnpostedEvents()
	{
		return FacebookDAO::getUnpostedEvents();
	}
}

/*Example:

$message = 'Peter Werdum opens as a -400 betting favourite over Ben Rothwell (+200) at UFC 197: Werdum vs. Rothwell, set for October 5th';
$link = 'https://www.bestfightodds.com/events/ufc-on-fox-20-holm-vs-shevchenko-1114';

$fh = new FacebookHandler();
$fh->postToFeed($message, $link);

*/

?>