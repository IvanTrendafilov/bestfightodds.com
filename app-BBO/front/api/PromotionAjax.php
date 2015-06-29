<?php
    try
    {
        require_once("SoapClient.php"); 
        
        $regionCode = $_REQUEST['regioncode'];
        $restaurantIds = $_REQUEST["restaurantids"];
        $categoryId = $_REQUEST['categoryid'];
        
        //GetPromotionsRequest object
        $getPromotionsRequest = array("Languages"=>$mLanguages,"PartnerCode"=>$mPartnerCode,
            "PrePay"=>"IncludePrePay", // OnlyPrePay or , NoPrePay also valid
            // "ActiveRange"=>array("From"=>"2008-01-01T00:00:00","To"=>"2008-02-01T00:00:00"), // date range
            // "MarketingRange"=>array("From"=>"2008-01-01T00:00:00","To"=>"2008-02-01T00:00:00"), // date range
            "ReturnRestaurantIds"=>true,
            "ReturnImage"=>false,
            "ReturnProperties"=>false, // internal properties, used by Livebookings to generate promotional text
            //"Property"=>array(array("Code"=>"code","Value"=>"value"),array("Code"=>"code","Value"=>"value")...), // properties to match
            "MaximumResults"=>10,
            "ResultStartIndex"=>0,
            "ReturnMarketingCategories"=>true
            );
 
        if (!is_null($regionCode))
            $getPromotionsRequest["RegionId"] = $regionCode; // Search by region
        else  // by RestaurantIds
            $getPromotionsRequest["RestaurantIds"] = $restaurantIds;
            
        if (!is_null($categoryId))    
            $getPromotionsRequest["MarketingCategoryId"] = (int)$categoryId;
            
        $response = $mSoapClient->GetPromotions($getPromotionsRequest);
        
        //var_dump($response);
        echo "<div>Total number of promotions found: ".$response->TotalNumberOfResults."</div>";

        // php does not create array if count is only 1
        if (count($response->Promotion) > 1)
        {
            foreach ($response->Promotion as $p)    
                PrintPromotion($p);
        }
        else if (!is_null($response->Promotion))
        {
            PrintPromotion($response->Promotion);
        }
    }
    catch(Exception $e)
    {
        echo 'Error occured: ' .$e->getMessage();
    }
    function PrintPromotion($p)
    {
        echo "<div style='margin:6px; border:solid 1px black;'>";
        echo $p->Name->_."<br />";
        echo "<div>".$p->Description->_."</div>";
        echo "<div>Restaurant Ids: ".$p->RestaurantIds."</div>";
        echo "<div>Marketing Categories: ".$p->MarketingCategories."</div>";        
        echo "</div>";
    }
?> 