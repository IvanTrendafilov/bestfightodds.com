<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Promotion Sample</title>
    <script type="text/javascript" src="Ajax.js"></script> 
    <script type="text/javascript" src="Promotion.js"></script>
    <link href="style.css" rel="stylesheet" type="text/css" />
    <?php require_once("SoapClient.php"); ?> 
</head>
<body>
    <a href="index.htm">Index</a><br />
    <p>
        View the top 10 promotions given the input.
    </p>
    <form>
        <input type="radio" name="area" id="mRegionCheck" checked="checked" />  
        Regions:
        <div>
            
            <select id='mRegionSelect'> 
            <?php
                // Get the regions in Great Britain
                $request = array("Languages"=>$mLanguages,"PartnerCode"=>$mPartnerCode,"CountryCode"=>"GBR");
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
        <input type="radio" name="area" id="mIdsCheck" />
        RestaurantIds: 
        <div>
            <label for="mRestaurantIds">Restaurant Ids:</label> <input id="mRestaurantIds" />
        </div>
        Promotion Marketing Categories: 
        <div>
            <select id='mPromoMarketSelect'>
                <option value="">[Select a category]</option>
            <?php
                // Get the promotion Marketing Categories
                $request = array("Languages"=>$mLanguages,"PartnerCode"=>$mPartnerCode);
                $response = $mSoapClient->GetPromotionMarketingCategories($request);
                
                CreateCategories($response->Category, 0);

                function CreateCategories($categories, $depth)
                {
                    foreach ($categories as $c)
                    {
                        echo("<option value='".$c->id."'>");
                        for ($i=0; $i<$depth; $i++)
                        {
                            echo("-");
                        }
                        echo($c->Name->_);
                        echo("</option>");
                        if (!is_null($c->ChildCategory))
                            CreateCategories($c->ChildCategory, $depth+1);
                    }
                }
            ?>
            </select>
        </div>         
        <input type="button" value="Find Promotions" id="mButton" 
            onclick="FindPromotions();" />
        <div id="mResult">
        </div>
        <div id="loadingPanel" class="asyncPostBackPanel" style="display: none;">
            <img src="indicator.gif" alt="" />&nbsp;&nbsp;Loading...
        </div>
    </form>
</body>
</html>
