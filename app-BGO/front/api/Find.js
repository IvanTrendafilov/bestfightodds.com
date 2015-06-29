function FindAvailability(day, month, year, hour, minute, session, partySize, name, getPromotion)
{
	document.getElementById('loadingPanel').style.display = 'block';
    document.getElementById('mButton').disabled=true;
	xmlHttp=GetXmlHttpObject();
	if (xmlHttp==null)
 	{
 		alert ("Browser does not support HTTP Request");
 		return;
 	} 
	var url="FindAjax.php";
	url=url+"?day="+day;
	url=url+"&month="+month;
	url=url+"&year="+year;
	url=url+"&hour="+hour;
	url=url+"&minute="+minute;
	url=url+"&session="+session;
	url=url+"&partySize="+partySize;
	url=url+"&name="+name.replace(" ", "+");
    url=url+"&promotion="+getPromotion;        
	xmlHttp.onreadystatechange=stateChanged ;
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