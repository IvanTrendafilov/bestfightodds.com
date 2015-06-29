<?php
/**
 * Livebookings Web Service interface
 * - Fetches available restaurants and timeslots
 *
 * Sample usage:
 *
 * $li = new LiveInterface();
 * $result = $li->fetchAvailableTimes('2013-09-23T19:00:00', 'DINNER', 2, 11290);
 * $result = $li->getAllRestaurantIDs(167);
 *
 * Test data:
 *
 * Region: 167 (Stockholm)
 * Restaurant location IDs: 22263 (Miss Voon), 6158 (Sturehof), 11290 (Grill)
 *
 */
class LiveInterface 
{
	private static $endpoints = array('ingrid' => 'http://webservices.livebookings.com/Ingrid/index.asmx?WSDL',
									'lbdotnet' => 'http://integration.livebookings.net/webservices/external/contract/get.aspx');

	private static $connectionID = 'INTL-LBDIRECTORY:19174';
	private static $langCode = 'en-GB';
	private static $byPassCache = true;
	private static $soaptrace = 0;

	private static $timeslots = array('17:00','17:15','17:30','17:45','18:00','18:15','18:30',
									'18:45','19:00','19:15','19:30','19:45','20:00','20:15',
									'20:30','20:45','21:00','21:15','21:30','21:45','22:00',
									'22:15','22:30','22:45','23:00');
	private static $sessionTypes = array('BREAKFAST', 'LUNCH', 'DINNER');

	private $dummymode = false;

	public function __construct($a_dummymode = false)
	{
		$this->dummymode = $a_dummymode;
	}

	/**
	 * Fetches the available timeslots for reservation based on the input provided
	 *
	 * @param string $a_dateTime Date and time to check (time is ignored) in the format YYYY-MM-DDTHH:MM:SS (e.g. 2013-09-23T19:00:00)
	 * @param string $a_sessionType Session type (= BREAKFAST, LUNCH or DINNER)
	 * @param int $a_guests No of guests for the table (e.g. 2)
	 * @param int $a_restId Restaurant location ID. Can be fetched from getRestaurantIDs function
	 * @return array A list of available timeslots
	 */
	public function fetchAvailableTimes($a_dateTime, $a_sessionType, $a_guests, $a_restId)
	{
		if ($this->dummymode == true)
		{
			//Return randomized dummy values
			return array_rand(self::$timeslots);
		}

		//Input validations
		if (!preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}/', $a_dateTime))
		{
			//Date/time in invalid format
			return false;
		}
		if (!is_numeric($a_guests))
		{
			//Guests in wrong format
			return false;
		}
		if (!is_numeric($a_restId))
		{
			//Restaurant ID in wrong format
			return false;
		}
		if (!in_array($a_sessionType, self::$sessionTypes))
		{
			//Invalid session type
			return false;
		}

		$params = array('ConnectionId' => self::$connectionID,
						  'DateAndTime' => $a_dateTime,
						  'Size' => $a_guests,
						  'RestaurantAreaId' => $a_restId,
						  'Session' => $a_sessionType,
						  'ByPassCache' => self::$byPassCache);

		$resp = $this->callWS(self::$endpoints['ingrid'], 'GetAvailability', $params);

		$times = array();
	    foreach ($resp->Availability->Result as $result)
	    {
			$times[] = substr($result->time, 11, 5);
	    }
	    return $times;
	}

	/**
	 * Fetches all available restaurants with IDs for a specific region
	 *
	 * @param int $a_region The region ID (e.g. 167 for Stockholm)
	 * @return array A collection of available restaurants as objects
	 */
	public function getAllRestaurantIDs($a_region)
	{
		$resp = $this->getRestaurantIDs($a_region, 0);
		$maxresults = $resp['TotalNumberOfResults'];
		echo 'max res is :' . $maxresults . '   ';
		$restaurants = $resp['Restaurant'];
		while (count($restaurants) < $maxresults)
		{
			$resp = $this->getRestaurantIDs($a_region, count($restaurants));
			if (count($resp['Restaurant']) == 0)
			{
				break 1;
			}
			$restaurants = array_merge($restaurants, $resp['Restaurant']);
		}

		return $restaurants;
	}

	/**
	 * Fetches all available restaurants with IDs for a specific region based on a search offset
	 *
	 * @param int $a_region The region ID (e.g. 167 for Stockholm)
	 * @return array A collection of available restaurants as objects
	 */
	public function getRestaurantIDs($a_region, $a_offset = 0)
	{
		if ($this->dummymode == true)
		{
			//TODO: Return dummy values;
			return null;
		}

		if (!is_numeric($a_region))
		{
			//Invalid region
			return false;
		}

		$params = array('PartnerCode' => self::$connectionID,
						'Languages' => 'en-GB',
						'Geographical' => array('RegionCode' => $a_region),
						'MaximumResults' => 100,
						'MaximumCharactersInDescription' => 200,
						'ResultStartIndex' => $a_offset);

		$resp = $this->callWS(self::$endpoints['lbdotnet'], 'GetRestaurants', $params);

		return (array) $resp;
	}

	private function callWS($a_endpoint, $a_operation, $a_params)
	{
		if ($a_endpoint == '' || $a_operation == '')
		{
			return false;
		}

		$soapclient = new SoapClient($a_endpoint, array('trace' => self::$soaptrace));
		$ret = $soapclient->$a_operation($a_params);
		return $ret;
	}

}

?>