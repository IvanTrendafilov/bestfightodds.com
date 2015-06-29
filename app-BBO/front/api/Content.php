<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Content Sample</title>
    <script type="text/javascript" src="Ajax.js"></script> 
    <script type="text/javascript" src="Content.js"></script>
    <link href="style.css" rel="stylesheet" type="text/css" />
    <?php require_once("SoapClient.php"); ?> 
</head>
<body>
    <a href="index.htm">Index</a><br />
    <h5>
        Please note that the Livebookings External Web Service API was not designed as a content server. 
        As such, there's no sorting and a limit of 100 restaurants are returned per query. 
        To use this feature effectively, please download the content and cache it on your system.
    </h5>
    <p>
        Pick an area and view the first 5 restaurants in the query. No paging of results is provided in this sample.
    </p>
    <form>
        <input type="radio" name="area" id="mRegionCheck" checked="checked" />
        Regions:
        <div>
            
	        <select id='mRegionSelect'> 
	        <?php
		        // Get the regions in Great Britain
		        $request = array("Languages"=>$mLanguages,"PartnerCode"=>$mPartnerCode,"CountryCode"=>"SWE");
		        $regions = $mSoapClient->GetRegions($request);
		        
		        CreateRegions($regions->Region, 0);

		        function CreateRegions($regions, $depth)
		        {
			        foreach ($regions as $r)
			        {
				        echo("<option value='".$r->Code."'>");
				        for ($i=0; $i<$depth; $i++)
				        {
					        echo("-");
				        }
				        echo($r->Name->_);
				        echo("</option>");
                        if (!is_null($r->SubRegion))
				            CreateRegions($r->SubRegion, $depth+1);
			        }
		        }
	        ?>
	        </select>
        </div>
        <input type="radio" name="area" id="mGeoCheck" />
        Latitude and Longitude: 
	    <div>
            <label for="mLatitude">Latitude:</label> <input id="mLatitude" />
            <label for="mLongitude">Longitude:</label> <input id="mLongitude" />
            <label for="mDisatance">Search radius (in meters):</label> <input id="mRadius" />
        </div>
        Cuisines: 
        <div>
	        <select id='mCuisineSelect'>
                <option value="">[Select a cuisine]</option>
	        <?php
		        // Get the cuisines in Great Britain
		        $request = array("Languages"=>$mLanguages,"PartnerCode"=>$mPartnerCode,"CountryCode"=>"GBR");
		        $cuisines = $mSoapClient->GetCuisines($request);
		        
		        CreateCuisines($cuisines->Cuisine);

		        function CreateCuisines($cuisines)
		        {
			        foreach ($cuisines as $c)
			        {
				        echo("<option value='".$c->Code."'>");
				        echo($c->Name->_);
				        echo("</option>");
			        }
		        }
	        ?>
	        </select>
	    </div>
        Restaurant Name:
        <div>
            <input type="text" id="mRestaurantName" />
        </div>    
        <input type="button" value="Find Restaurants" id="mButton" 
            onclick="FindRestaurants();" />
        <div id="mResult">
        </div>
        <div id="loadingPanel" class="asyncPostBackPanel" style="display: none;">
            <img src="indicator.gif" alt="" />&nbsp;&nbsp;Loading...
        </div>
    </form>
</body>
</html>
