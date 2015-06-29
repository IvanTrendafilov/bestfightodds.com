<?php header("Expires: 0"); header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); header("cache-control: no-store, no-cache, must-revalidate"); header("Pragma: no-cache");?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Book Sample</title>
    <link href="style.css" rel="stylesheet" type="text/css" />
    <?php require_once("SoapClient.php"); ?> 
</head>
<body>
    <a href="index.htm">Index</a><br />
    <?php
        try
        {
            // Book the time!
            $time = $_REQUEST['mTime']; 
            $session = $_REQUEST['mSession']; 
            $locationId = $_REQUEST['mLocationId'];
            $correlationData = $_REQUEST['mCData'];
            $size = $_REQUEST['mSize'];
            $first = $_REQUEST['mFirst'];
            $last = $_REQUEST['mLast'];
            $email = $_REQUEST['mEmail'];
            $mobile = $_REQUEST['mMobile'];
            $promotionId = $_REQUEST['mPromotionId'];
            
            // booking may be done by the guest or by a friend
            $booker = array(
                        // "UserId"=>1 if site allows user info to be saved
                        "UserWithoutALogin"=>array(
                            "FirstName"=>$first,
                            "LastName"=>$last,
                            "EMail"=>$email,
                            "MobilePhoneNumber"=>$mobile,
                            // "AlternativePhoneNumber"=> an alternative phone number may also be provided, instead of the mobile
                            "Title"=>"Mr" // title may or may not be required, by partner configuration
                        )
                    );
            // example credit card
            $creditCard = array(
                            "Type"=>"Visa", // may be Visa, MasterCard, Diners, AmericanExpress
                            "Number"=>"1234567890123456",
                            "ExpirationDate"=>"2009-09-01T00:00:00",
                            "SecurityNumber"=>"123"
                        );        
            // BookReservationRequest object
            $bookReservationRequest = array("Languages"=>$mLanguages,"PartnerCode"=>$mPartnerCode,
                "SessionId"=>$session,
                "DiningDateAndTime"=>$time,
                "Size"=>(int) $size,
                "RestaurantLocationId"=>(int) $locationId,
                "CorrelationData"=>$correlationData,
                "Booker"=>$booker,
                // "Guest"=>$booker, 
                // "CancelLink"=>  provide a cancel link on partner site to be emailed to the user
                "SuppressCustomerConfirmations"=>false,
                "SuppressRestaurantConfirmations"=>false,
                // "SpecialRequests"=> if restaurant allows special requests, seen when preparing booking, this can be added here
                "GuestAcceptsEmailMarketingFromRestaurant"=>false,
                // if sending credit card information for prepay restaurants, https must be used!
                //"CreditCard"=>$creditCard,
                //"PartnerGuestIdentifier"=> // set if there's a prearranged partnerGuestIdentifier between partner and livebookings
                "PerformPrePay"=>false // set to true if supplying credit card info
                //"PackageId"=>
                ); 
            // if promotionId supplied
            if (!is_null($promotionId) && $promotionId != "")
            {
                $bookReservationRequest["PromotionId"] = (int)$promotionId;
            }
                   
            $response = $mSoapClient->BookReservation($bookReservationRequest);
            
            $allowedToCancelOnline = $response->AllowedToCancelOnline;
            $confirmationNumber = $response->ConfirmationNumber;
            $reservationId = $response->ReservationId; // internal id
            
            echo "Reservation created, confirmation number ".$confirmationNumber." with internal id ".$reservationId;
        }
        catch(Exception $e)
        {
            echo 'Error occured: ' .$e->getMessage();
            return;
        }
    ?>
</body>
</html>
