<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Book Sample</title>
    <script type="text/javascript" src="Ajax.js"></script>
    <script type="text/javascript" src="Find.js"></script>
    <link href="style.css" rel="stylesheet" type="text/css" />
    <?php require_once("SoapClient.php"); ?>
</head>
<body>
	<a href="index.htm">Index</a><br />
	<p>
		Search for an availability in the Livebookings Eat Food Test Restaurant.  After an availability is found, book it!
	</p>
	<p>
		The SearchAvailability function is limited to searching for availability on 100 restaurants.
		If more than 100 restaurants is matched in the search criteria, only the first 100 restaurants will have their availabilities searched!
		The best way to implement a search on availability is to ensure the user has limited the number of restaurants to a small subset.
		One way of doing this is to first search for restaurant content via GetRestaurants, and search for availability only on the restaurants shown on the current page.
	</p>
	<form>
		<label>Date & Time:</label>
		<select id='mDay'>
		<?php
		 $tomorrowDate = getdate(mktime(0, 0, 0, date("m"), date("d")+1, date("y")));
		 for($i=1; $i<32; $i++)
		 {
            $value="";
            if ($i < 10)
                $value="0".$i;
            else
                $value=$i;
		 	if ($tomorrowDate[mday] == $i)
				echo "<option value='".$value."' selected>".$i."</option>";
			else
				echo "<option value='".$value."'>".$i."</option>";
		 }
		?>
		</select>
		/
		<select id="mMonth">
		<?php
			for($i=1; $i<13; $i++)
			{   $value="";
                if ($i < 10)
                    $value="0".$i;
                else
                    $value=$i;
				if ($tomorrowDate[mon] == $i)
					echo "<option value='".$value."' selected>".$i."</option>";
				else
					echo "<option value='".$value."'>".$i."</option>";
			}
		?>
		</select>
		/
		<select id="mYear">
		<?php
			echo "<option value='".$tomorrowDate[year]."' selected>".$tomorrowDate[year]."</option>";
			echo "<option value='".($tomorrowDate[year]+1)."'>".($tomorrowDate[year]+1)."</option>";
		?>
		</select>
		<select id="mHour">
		<?php
			for($i=0; $i<24; $i++)
			{
				if ($i == 18)
					echo "<option value='".$i."' selected>".$i."</option>";
				else
					echo "<option value='".$i."'>".$i."</option>";
			}
		?>
		</select>
		:
		<select id="mMinute">
			<option value='00'>00</option>
			<option value='15'>15</option>
			<option value='30' selected>30</option>
			<option value='45'>45</option>
		</select>
		<br />
		<label for="mSessionSelect">Session:</label> <select id='mSessionSelect'>
		<?php
			// Get the available sessions
			$request = array("Languages"=>$mLanguages,"PartnerCode"=>$mPartnerCode);
			$sessions = $mSoapClient->GetSessions($request);

			CreateSessions($sessions->Session);

			function CreateSessions($sessions)
			{
				foreach ($sessions as $s)
				{

					echo("<option value='".$s->id."'");
					if ($s->id == "DINNER")
						echo(" selected>");
					else
						echo(">");
					echo($s->Name->_);
					echo("</option>");
				}
			}
		?>
		</select>
		<br />
		<label for="mPartySize">Party Size:</label> <select id="mPartySize"><option value="1">1</option><option value="2" selected>2</option><option value="3">3</option><option value="4">4</option></select><br />
		<label for="mRestaurantName">Restaurant:</label> <input type="text" id="mRestaurantName" Readonly="true" value="Eat Food" /> This value is readonly.<br />
        <label for="mPromotion">Get Promotion:</label> <input type="checkbox" id="mPromotion" checked="checked" /><br />
		<input type="button" value="Find Availability" id="mButton"
			onclick="FindAvailability(document.getElementById('mDay').value,document.getElementById('mMonth').value,document.getElementById('mYear').value,document.getElementById('mHour').value,document.getElementById('mMinute').value,document.getElementById('mSessionSelect').value,document.getElementById('mPartySize').value,document.getElementById('mRestaurantName').value,document.getElementById('mPromotion').checked);" />
		<div id="mResult">
		</div>
		<div id="loadingPanel" class="asyncPostBackPanel" style="display: none;">
            <img src="indicator.gif" alt="" />&nbsp;&nbsp;Loading...
        </div>
	</form>
</body>
</html>
