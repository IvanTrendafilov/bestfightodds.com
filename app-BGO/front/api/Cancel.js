function FindBooking()
{
    var email=document.getElementById('mEmail').value;
    var mobile=document.getElementById('mMobile').value.replace("+", "%2B");
    var reference=document.getElementById('mReference').value;
    var resultDiv=document.getElementById('mResult');
    var button=document.getElementById('mButton');
    if ((email!='' || mobile !='') && reference != '')
    {
        resultDiv.innerHTML = "";
        xmlHttp=GetXmlHttpObject();
        if (xmlHttp==null)
        {
            alert ("Browser does not support HTTP Request");
            return;
        }
        button.disabled=true;
        document.getElementById('loadingPanel').style.display = 'block';
        var url="FindReservationAjax.php";
        url=url+"?email="+email;
        url=url+"&mobile="+mobile;
        url=url+"&reference="+reference; // make sure we allow pluses
        xmlHttp.onreadystatechange=stateChanged;
        xmlHttp.open("GET",url,true);
        xmlHttp.send(null);
    }
    else
    {
        resultDiv.innerHTML="<span style='color:red;'>Must provide an email or mobile and the reference number.</span>";
        button.disabled=false;
    }
}
function CancelBooking(reservationId)
{
     document.getElementById('mCancelButton').disabled=true;
     document.getElementById('loadingPanel').style.display = 'block';
     document.getElementById('mButton').disabled=true;
     xmlHttp=GetXmlHttpObject();
    if (xmlHttp==null)
    {
        alert ("Browser does not support HTTP Request");
        return;
    }
    var url="CancelAjax.php";
    url=url+"?id="+reservationId;
    xmlHttp.onreadystatechange=stateChanged;
    xmlHttp.open("GET",url,true);
    xmlHttp.send(null);  
}
function stateChanged() 
{ 
    if (xmlHttp.readyState==4 || xmlHttp.readyState=="complete")
    { 
        document.getElementById('mButton').disabled=false;
        document.getElementById('loadingPanel').style.display='none';
        document.getElementById("mResult").innerHTML=xmlHttp.responseText;
    } 
}