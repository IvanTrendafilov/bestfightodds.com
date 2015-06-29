<?php
    try
    {
        require_once("SoapClient.php"); 
        
        $Id = $_REQUEST['id'];
        
        //GetRestaurantDetailRequest object
        $getRestaurantDetailRequest = array(
            "PartnerCode"=>$mPartnerCode,
            "Languages"=>$mLanguages,
            "Id"=>(int)$Id // by internal Id
            //"SalesForceCustomerId"=>"12345"
            );
                                     
        $response = $mSoapClient->GetRestaurantDetail($getRestaurantDetailRequest);
        
        //var_dump($response);
        echo "<div>".$response->Restaurant->Name."</div>";
        echo "<div>".$response->Restaurant->Description->_."</div>";
        
        
    }
    catch(Exception $e)
    {
        echo 'Error occured: ' .$e->getMessage();
    }      
?> 