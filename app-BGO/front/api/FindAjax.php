<?php
	try
	{
		require_once("SoapClient.php"); 
		
		$day = $_REQUEST['day'];
		$month = $_REQUEST['month'];
		$year = $_REQUEST['year'];
		$hour = $_REQUEST['hour'];
		$minute = $_REQUEST['minute'];
		$session = $_REQUEST['session'];
		$partySize = $_REQUEST['partySize'];
		$name = $_REQUEST['name'];
        $getPromotion;
        if ($_REQUEST['promotion']=="true")
            $getPromotion = true;
        else 
            $getPromotion = false;
		
        // format the dateString from the input
        $dateString = $year."-".$month."-".$day."T".$hour.":".$minute.":00";

        $geographical = array("RegionCode"=>"169"); // Search in London
        // by GeoCode
        // $geographical = array("LatitudeAndLongitude"=>array("longitude"=>$long,"latitude"=>$lat,"radius"=>$this->searchRadius));
        
		$requestObject = array("Languages"=>$mLanguages,"PartnerCode"=>$mPartnerCode,
			"SessionId"=>$session,
			"DiningDateAndTime"=>$dateString,
			"Size"=>(int) $partySize,
			//"CuisineCode"=>"",
            //"RestaurantInternalIds"=>$internalIds,
			"RestaurantName"=>"%Eat Food%", // use % to match any text
            //"RestaurantSalesForceCustomerIds"=>salesForceIds,
			"Geographical"=>array("RegionCode"=>"169"),
			"MaximumResults"=>5,
			"MaximumCharactersInDescription"=>0, // won't return description for this sample
            //"SortOrder"=>"Default", // or DistanceAscending or Distance Descending
			//"PromotionId"=>0, // a specific promotion
			//"PackageId"=>0, // a pspecific package deal
			//"ReturnNonPromotions"=>true,
			//"ReturnSessionMessage"=>false,
			//"ReturnReturnMessage"=>false,
			//"IncludeRestaurantWithNoAvailability"=>false,
			"ReturnPromotions"=>$getPromotion
			);
			
		$availabilities = $mSoapClient->SearchAvailability($requestObject);
        
        //var_dump($availabilities);
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
    function PrintLocation($l, $session, $partySize, $promotionId)
    {
        echo "<div>".$l->Name;
        if (!is_null($l->Result))
        {
            foreach ($l->Result as $r)
            {
                echo "&nbsp;-&nbsp;";
                $timestamp = strtotime($r->time);
                echo "<a href='book.php?time=".$r->time."&session=".$session."&size=".$partySize.
                    "&locationid=".$l->id."&cdata=".$r->correlationData."&promotionid=".$promotionId."'>"
                    .date("H:i", $timestamp)."</a>";
            }
        }
        echo "</div>"; 
    }
?> 