function FindPromotions()
{
    document.getElementById("mResult").innerHTML="";
    var url="PromotionAjax.php";
    if (document.getElementById('mIdsCheck').checked)
    {
        var RestaurantIds=document.getElementById('mRestaurantIds').value;
        if (RestaurantIds == "")
        {
            document.getElementById("mResult").innerHTML = "<span style='color:red;'>Must provide comma separated Restaurant Ids</span>";
            return;
        }
        url=url+"?restaurantids="+RestaurantIds;
    }
    else
    {
        url=url+"?regioncode="+document.getElementById('mRegionSelect').value;
    }
    var categoryId=document.getElementById('mPromoMarketSelect').value;
    if (categoryId!='')
        url=url+"&categoryid="+categoryId;
        
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
