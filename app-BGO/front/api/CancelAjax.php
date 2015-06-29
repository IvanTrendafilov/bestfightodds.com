<?php
    try
    {
        require_once("SoapClient.php"); 
        
        // Cancel the booking!
        $reservationId = $_REQUEST['id'];   
        
        //CancelReservationRequest Object
        $requestObject = array("Languages"=>$mLanguages,"PartnerCode"=>$mPartnerCode,
            "ReservationId"=>$reservationId,
            "SuppressCustomerConfirmations"=>false,
            "SuppressRestaurantConfirmations"=>false
            );
        $response = $mSoapClient->CancelReservation($requestObject);
        
        echo "Reservation cancelled.";    
    }
    catch(Exception $e)
    {
        echo 'Error occured: ' .$e->getMessage();
    }
?> 