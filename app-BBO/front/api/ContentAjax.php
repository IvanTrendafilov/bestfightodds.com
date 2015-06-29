<?php
    try
    {
        require_once("SoapClient.php");

        $regionCode = $_REQUEST['regioncode'];
        $lat = (float)$_REQUEST['lat'];
        $long = (float)$_REQUEST['long'];
        $rad = (int)$_REQUEST['rad'];
        $cuisineCode = $_REQUEST['cuisinecode'];
        $name = $_REQUEST['name'];

        //SearchRestuarantsRequest object
        $searchRestuarantsRequest = array("Languages"=>$mLanguages,"PartnerCode"=>$mPartnerCode,
            "MaximumResults"=>5,
            "MaximumCharactersInDescription"=>0, // won't return description for this sample
            //"ResultStartIndex"=>5 // can do paging
            //"ContentUpdatedAfter"=> // can refine by newly added or updated restaurants
            //"RestaurantIds"=> // can supply specific restaurant Ids
            //"DisabledDisposition"=> // value can be  OnlyEnabled, OnlyDisabled, Both - this only applies if ContentUpdatedAfter is passed
            "ReturnAddress"=>false
            );
        if (!is_null($cuisineCode))
            $searchRestuarantsRequest["CuisineCode"] = $cuisineCode;
        if (!is_null($name))
            $searchRestuarantsRequest["RestaurantName"] = "%".$name."%"; // add % to match any string before or after

        if (!is_null($regionCode))
            $searchRestuarantsRequest["Geographical"] = array("RegionCode"=>$regionCode); // Search by region
        else
        {
            // by GeoCode
            $searchRestuarantsRequest["Geographical"] = array("LatitudeAndLongitude"=>array("latitude"=>$lat,"longitude"=>$long,"radius"=>$rad));
        }
        // you can also search by PostalCode

        $response = $mSoapClient->GetRestaurants($searchRestuarantsRequest);

        //var_dump($response);

        echo "<div>Total number of restaurants found: ".$response->TotalNumberOfResults."</div>";
        echo "<div style='display:none; border:solid 1px black; margin:6px;' id='mRestaurantResult'></div>";
        // php does not create array if count is only 1
        if (count($response->Restaurant) > 1)
        {
            foreach ($response->Restaurant as $r)
                PrintRestaurant($r, !is_null($regionCode));
        }
        else if (!is_null($response->Restaurant))
        {
            PrintRestaurant($response->Restaurant, !is_null($regionCode));
        }
    }
    catch(Exception $e)
    {
        echo 'Error occured: ' .$e->getMessage();
    }
    function PrintRestaurant($r, $byRegionCode)
    {
        echo "<div>";
        echo "<a href='javascript:GetRestaurantDetail(".$r->id.");'>".$r->Name."</a><br />";
        echo "Latitude: ".$r->Geo->Latitude." Longitude: ".$r->Geo->Longitude;
        if (!$byRegionCode)
            echo " Distance from input: ".$r->Geo->Distance;
        echo "</div>";
    }
?>