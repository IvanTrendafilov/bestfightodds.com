function FindRestaurants()
{
    document.getElementById("mResult").innerHTML="";
    var url="ContentAjax.php";
    if (document.getElementById('mGeoCheck').checked)
    {
        var lat=document.getElementById('mLatitude').value;
        var long=document.getElementById('mLongitude').value;
        var rad=document.getElementById('mRadius').value;
        if (lat == "" || long == "" || rad == "")
        {
            document.getElementById("mResult").innerHTML = "<span style='color:red;'>Must provide latitude, longitude, and radius.</span>";
            return;
        }
        url=url+"?lat="+lat+"&long="+long+"&rad="+rad;
    }
    else
    {
        url=url+"?regioncode="+document.getElementById('mRegionSelect').value;
    }
    var cuisineCode=document.getElementById('mCuisineSelect').value;
    if (cuisineCode!='')
        url=url+"&cuisinecode="+cuisineCode;
    var name=document.getElementById('mRestaurantName').value;
    if (name!='')
        url=url+"&name="+name.replace(" ", "+");
        
    document.getElementById('loadingPanel').style.display = 'block';
    document.getElementById('mButton').disabled=true;
    xmlHttp=GetXmlHttpObject();
    if (xmlHttp==null)
     {
         alert ("Browser does not support HTTP Request");
         return;
     } 
              
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

function GetRestaurantDetail(Id)
{
    var result=document.getElementById("mRestaurantResult");
    result.innerHTML="";
    result.style.display = 'none';
    document.getElementById('loadingPanel').style.display = 'block';
    document.getElementById('mButton').disabled=true;               
    var url="detail.php?id="+Id;
    xmlHttp=GetXmlHttpObject();
    xmlHttp.onreadystatechange=stateChanged2;
    xmlHttp.open("GET",url,true);
    xmlHttp.send(null);
}
function stateChanged2() 
{ 
    if (xmlHttp.readyState==4 || xmlHttp.readyState=="complete")
    { 
        document.getElementById('mButton').disabled=false;
        document.getElementById('loadingPanel').style.display='none';
        var result=document.getElementById("mRestaurantResult");
        result.innerHTML=xmlHttp.responseText;
        result.style.display = 'block';
    } 
}