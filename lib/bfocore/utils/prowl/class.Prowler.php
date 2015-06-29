<?php

require "lib/bfocore/utils/prowl/class.php-prowl.php";
define("DEMO_EOL",isset($_SERVER['HTTP_USER_AGENT']) ? "<br />" : "\n");

$oPr = new Prowler();
$oPr->notifyUnmatched();

class Prowler
{
	public function notifyUnmatched()
	{
		
		
		try {
			$prowl = new Prowl();
			$prowl->setProviderKey("11f76443c9f32863f6e5369c51675aca2e441b21");
			$prowl->setApiKey('bda8cebf48eee3ce7913a056e4a53a95618e2aa4');
			//$prowl->setDebug(true);

			$application = "BFO";
			$event = "Unmatched entries";
			$description = "glover texeira vs stefan struve";
			$url = "http://www.bestfightodds.com/cnadm";
			$priority = 0;

			$message = $prowl->add($application,$event,$priority,$description,$url);
		} 
		catch (Exception $message) 
		{
			//echo "Failed: ".$message->getMessage().DEMO_EOL;
			return false;
		}
		return true;
	}
}

?>