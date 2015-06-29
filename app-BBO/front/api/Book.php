<?php header("Expires: 0"); header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); header("cache-control: no-store, no-cache, must-revalidate"); header("Pragma: no-cache");?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Book Sample</title>
    <script type="text/javascript" src="Book.js"></script>
    <link href="style.css" rel="stylesheet" type="text/css" />
    <?php require_once("SoapClient.php"); ?> 
</head>
<body>
    <a href="index.htm">Index</a><br />
    <a href="javascript:history.back();">Back to search</a>
    <form action="confirm.php" method="post">
    <?php
        try
        {
            // Prepare booking - Check to see if it is still available
            $time = $_REQUEST['time']; 
            $session = $_REQUEST['session']; 
            $locationId = $_REQUEST['locationid'];
            $correlationData = $_REQUEST['cdata'];
            $size = $_REQUEST['size'];
            $promotionId = $_REQUEST['promotionid'];
            
            // store these variables in hidden inputs
            echo "<input type='hidden' id='mTime' name='mTime' value='".$time."' />";
            echo "<input type='hidden' id='mSession' name='mSession' value='".$session."' />";
            echo "<input type='hidden' id='mLocationId' name='mLocationId' value='".$locationId."' />";
            echo "<input type='hidden' id='mCData' name='mCData' value='".$correlationData."' />";
            echo "<input type='hidden' id='mSize' name='mSize' value='".$size."' />";
            echo "<input type='hidden' id='mPromotionId' name='mPromotionId' value='".$promotionId."' />";
            
            // PrepareBookRequest object
            $prepareBookRequest = array("Languages"=>$mLanguages,"PartnerCode"=>$mPartnerCode,
                "SessionId"=>$session,
                "DiningDateAndTime"=>$time,
                "Size"=>(int) $size,
                "RestaurantLocationId"=>(int) $locationId,
                //"PackageId"=>
                "CorrelationData"=>$correlationData
                );    
            
            // if promotionId supplied
            if (!is_null($promotionId) && $promotionId != "")
            {
                $prepareBookRequest["PromotionId"] = (int)$promotionId;
            }
            
            $response = $mSoapClient->PrepareBookReservation($prepareBookRequest);
            
            $stillAvailable = $response->StillAvailable;
            $requiresCreditCard = $response->RequiresCreditCard;
            $acceptsSpecialRequests = $response->AcceptsSpecialRequests;
        }
        catch(Exception $e)
        {
            echo 'Error occured: ' .$e->getMessage();
            return;
        }
        // if it is stillAvailable, allow the booking
        if ($stillAvailable)
        {            
    ?> 
        <p>
            Click the button to book this time.
        </p>       
                    
        <label for="mFirst">First Name:</label> <input id="mFirst" name='mFirst' /><br />
        <label for="mLast">Last Name:</label> <input id="mLast" name='mLast' /><br />
        <label for="mEmail">Email:</label> <input id="mEmail" name='mEmail' /> Email confirmation will be sent here.<br />
        <label for="mMobile">Mobile:</label> <input id="mMobile" name='mMobile' /> Provide a valid mobile number with country code, e.g. +44 123 4567 890 and a text will be received<br />
        <input id="mButton" type="submit" onclick="return checkInput();" />
        <div id="mResult">
        </div>
    <?php
        }
        else  // if it is no longer available, the time was previously cached.
        {
    ?>   
    <p>
        PrepareBook failed, time is probably no longer available.
    </p>
    <?php
        }
    ?> 
    </form> 
</body>
</html>
