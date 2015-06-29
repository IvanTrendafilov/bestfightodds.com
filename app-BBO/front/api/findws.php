
<?php
try {
	$mSoapClient = new SoapClient("http://integration.livebookings.net/webservices/external/service.asmx?WSDL", array('trace' => 1));
	$mPartnerCode = "SE-RES-STUREHOF_100238:4268"; // input your partnerCode here
	$mLanguages = "en-GB";

		$dateString = '2013-09-27T20:00:00';
        $session = 'DINNER';

$requestObject = array("Languages"=>$mLanguages,"PartnerCode"=>$mPartnerCode,
			"SessionId"=>$session,
			"DiningDateAndTime"=>$dateString,
			"Size"=>(int) 2,
			//"CuisineCode"=>"",
            //"RestaurantInternalIds"=>$internalIds,
			"RestaurantName"=>"%ture%", // use % to match any text
            //"RestaurantSalesForceCustomerIds"=>salesForceIds,
			//"Geographical"=>array("RegionCode"=>"169"),
			"MaximumResults"=>5,
			"MaximumCharactersInDescription"=>25, // won't return description for this sample
            //'RestaurantLocationId' => '100238',
            //"SortOrder"=>"Default", // or DistanceAscending or Distance Descending
			//"PromotionId"=>0, // a specific promotion
			//"PackageId"=>0, // a pspecific package deal
			//"ReturnNonPromotions"=>true,
			//"ReturnSessionMessage"=>false,
			//"ReturnReturnMessage"=>false,
            'RestaurantName' => 'Sturehof',
			//"IncludeRestaurantWithNoAvailability"=>false,
			"ReturnPromotions"=>false
			);
			
		$availabilities = $mSoapClient->SearchAvailability($requestObject);
		var_dump($availabilities);
		echo "Response:\n" . $mSoapClient->__getLastResponse() . "\n";
		exit;

		foreach ($availabilities as $a)
        {      
            echo "<div>";
            echo "<div>".$a->Name."</div>";
            // show the available promotions
            if (!is_null($a->Promotion))
            {
                echo "<div>Promotions</div>";
                
                // soap in php will not create an array unless more than 1 is returned
                if (count($a->Promotion) > 1)
                {
                    foreach ($a->Promotion as $p)
                    {
                        echo "<div>".$p->Name->_."</div>";
                        if (count($p->Location) > 1)
                        {
                            foreach ($p->Location as $l)
                            {
                                PrintLocation($l, $session, $partySize, $p->id);
                            }
                        }
                        else
                            PrintLocation($p->Location, $session, $partySize, $p->id);
                    }
                }
                else
                {
                    echo "<div>".$a->Promotion->Name->_."</div>";
                    if (count($a->Promotion->Location) > 1)
                    {
                        foreach ($a->Promotion->Location as $l)
                        {
                            PrintLocation($l, $session, $partySize, $a->Promotion->id);
                        }
                    }
                    else
                        PrintLocation($a->Promotion->Location, $session, $partySize, $a->Promotion->id);
                }
            }
            // show nonpromotions
            if (!is_null($a->NonPromotion) && !is_null($a->NonPromotion->Location))
            {
                echo "<div>Non-Promotions</div>";
                // soap in php will not create an array unless more than 1 is returned
                if (count($a->NonPromotion->Location) > 1)
                {
                    foreach ($a->NonPromotion->Location as $l)
                    {
                        PrintLocation($l, $session, $partySize, "");
                    }
                }
                else
                    PrintLocation($a->NonPromotion->Location, $session, $partySize, "");      
            }
            echo "</div>";
        }


}
    catch(Exception $e)
    {
        echo 'Error occured: ' .$e->getMessage();
    }


?>


