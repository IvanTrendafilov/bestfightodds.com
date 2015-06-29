<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Cancel Sample</title>
    <script type="text/javascript" src="Ajax.js"></script> 
    <script type="text/javascript" src="Cancel.js"></script> 
    <link href="style.css" rel="stylesheet" type="text/css" />
    <?php require_once("SoapClient.php"); ?> 
</head>
<body>
    <a href="index.htm">Index</a><br />
    <p>
        Cancel a previously booked booking.<br />
        Please supply the email or mobile you used to confirm the booking and the reference number on your confirmation receipt.
    </p>
    <form>
        <label for="mEmail">Email:</label> <input id="mEmail" /><br />
        <label for="mMobile">Mobile:</label> <input id="mMobile" /><br />
        <label for="mReference">Reference:</label> <input id="mReference" /><br />
        <input id="mButton" type="button" value="Continue" onclick="FindBooking()" />
        <div id="mResult">
        </div>
        <div id="loadingPanel" class="asyncPostBackPanel" style="display: none;">
            <img src="indicator.gif" alt="" />&nbsp;&nbsp;Loading...
        </div>
    </form>
</body>
</html>
