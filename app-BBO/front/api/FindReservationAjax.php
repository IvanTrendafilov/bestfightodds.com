<?php
    // FindReservation throws livebookings business exception
    // please consult the Livebookings .NET example too see how to handle exceptions.
    try
    {
        require_once("SoapClient.php"); 
        
        $email = $_REQUEST['email'];
        $mobile = $_REQUEST['mobile'];
        $reference = $_REQUEST['reference'];
        $found = false;
        
        // locate the reservation        
        $response = null;
        // only provide email or mobile, but not both!    
        if (!is_null($email) && $email != '') 
        {
            $searchByEmail = true;
        }    
        else
        {
             $searchByEmail = false;
        }
        $response = FindReservation($mSoapClient, $mLanguages, $mPartnerCode, $email, $mobile, $reference, $searchByEmail); 
        
        if (!is_null($response))
        {
            $found = true;
        }
    }
    catch(Exception $e)
    {
        $found = false;
    }
    try
    {
        // try the other input if provided
        if ($searchByEmail && !is_null($mobile) && $mobile != '')
        {
            $searchByEmail = false;
        }
        $response = FindReservation($mSoapClient, $mLanguages, $mPartnerCode, $email, $mobile, $reference, $searchByEmail);
        if (!is_null($response))
        {
            $found = true;
        }
    }
    catch(Exception $e)
    {
        $found = false;
    }
    if ($found)
    {
        $r = $response->Reservation;
        if ($r->AllowedToCancelOnline)    
        {
            echo "Dining Date Time: ".$r->DiningDateAndTime."<br />";
            echo "Name: ".$r->FirstName." ".$r->LastName."<br />"; 
            echo "Party Size: ".$r->Size."<br />";   
            echo "Restaurant: ".$r->Restaurant->Name."<br />";
            echo "<input id='mCancelButton' type='button' value='Cancel' onclick='CancelBooking(".$r->id.");' />";   
        }
        else
            echo "Restaurant does not accept online cancellation.";
    }
    else
    {
        echo 'Reservation not found.';
        return;
    }    
    function FindReservation($mSoapClient, $mLanguages, $mPartnerCode, $email, $mobile, $reference, $searchByEmail)
    {
        // the SearchForReservationRequest
        $requestObject = array("Languages"=>$mLanguages,"PartnerCode"=>$mPartnerCode,
            "ConfirmationNumber"=>$reference
            );   
        
        // only provide email or mobile, but not both!    
        if ($searchByEmail) 
        {
            $requestObject['EMail'] = $email;    
        }    
        else
        {
             $requestObject['PhoneNumber'] = $mobile;    
        }
            
        return $mSoapClient->SearchForReservation($requestObject);
    }
?> 