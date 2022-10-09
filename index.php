<?php //Copyright (c) 2022 Gary Strawn, All rights reserved
/* Little Otter Take Home - Bike Rental API
1. How many bikes are rented at every hour of the day?
	a. Context: To service demand for our bikes, we want to make sure we are supplying all of our starting locations with an adequate amount of bicycles. To ensure our riders won’t be without a bicycle, we must understand bike demand throughout the day.
	b. Based on: start_time

2. Understanding the age distribution of riders for each starting location
	a. Context: The marketing team is interested in creating relevant advertising for our riders. We want to make sure the ads are tailored to particular age demographics. They first want to verify what the average ages are of our riders, starting at each station.
	b. Based on: birth_year, start_station_id

3. Show us something interesting about bike usage
	a. This is more open ended. We’d like the interviewee to demonstrate their own creativity when coming up with metrics to demonstrate
	b. Example: how much mileage does the five most ridden bikes have?
	c. Context: maintenance team needs to know which bikes to service or retire, or swap with other bikes to balance the usage load
*/
require_once "api.php"; //otter api

//This is really only for admin
if(isset($_GET['build']))  echo BuildDB();

//GET parameters:
//station=<num>: 0 = default = all stations
//toFrom=<to|from>:
//  to             = show "Returned To" = trips with stop_station = <station>
//  from = default = show "Rented From" = trips with start_station = <station>
//inventory=<''|true|false>:
//  true = show running inventory (+/- bikes) at current station
//  false = default = show #bikes to/from current station
$stationID = isset($_GET['station'])  ?  (intval($_GET['station']) ?: 0)  :  0;
$toFrom    = $_GET['toFrom'] ?? 'from';
$inventory = (isset($_GET['inventory'])  &&  $_GET['inventory'] != 'false');

?><!-- Copyright © 2022-<?=date('Y')?> Gary Strawn, All rights reserved -->
<!DOCTYPE html>
<html lang="en-US">
<head>
  <title>Little Otter Takehome</title>
  <meta charset="UTF-8" />
  <meta name="copyright" content="Gary Strawn" />
  <meta name="description" content="Bikes, bikes, everywhere bikes" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link type="text/css" rel="stylesheet" href="index.css" />
  <script src="index.js" defer></script>
</head>

<body>
<h1 class="too_narrow">Sorry, your screen is too narrow.</h1>

<h1 title="How many bikes are rented at every hour of the day?">Bike Rentals</h1>
<header>
  <select id="station">
    <option value="0"<?= $stationID ? '' : 'selected'?>>All stations</option>
<?php //get all locations
$aStations = DB::Run('SELECT `station_id`, `name` from `stations`')->fetchAll(PDO::FETCH_KEY_PAIR);
foreach($aStations as $id => $name)
	echo "    <option value=\"$id\"". ($stationID == $id ? ' selected' : '') .">$name</option>\n";
?>
  </select>
  <section id="hourlyInput">
    <div id="hourlyToFrom">
      <label><input type="radio" name="hourlyToFrom" value="from"<?= 
			$toFrom == 'from' ? ' checked' : '' ?>> Rented from</label>
      <label><input type="radio" name="hourlyToFrom" value="to"<?=
			$toFrom == 'to' ? ' checked' : '' ?>> Returned to</label>
    </div>
    <label>Feb <input id="hourlyDate" type="number" min=1 max=28> 2015</label>
    <label>Hour: <input id="hourlyHour" type="number" min=0 max=23></label>
    <label>Rented: <span id="hourlyRented"></span></label>
  </section>
</header>

<!-- hourly table -->
<img id="hourlyTableLoading" src="/img/wait_lg.gif">
<table id="hourlyTable" style="display:none">
  <thead><tr><th></th><th>am</th><?php 
		for($h=1; $h<24; $h++)  echo '<th>'.(($h%12) ?: 'pm').'</th>'; ?></tr></thead>
  <tbody>
<?php
	for($day = 1, $id = 0;  $day <= 28;  $day++) {
		echo "        <tr data-day=\"$day\"><th>$day</th>";
		for($hour = 0;  $hour < 24;  $hour++, $id++)
			echo "<td id=\"td$id\"></td>";
		echo "</tr>\n";
	}
?>
  </tbody>
</table>

<!-- notes -->
<ul id="hourlyNotes">
  <li id="noteNumber">Numbers indicate bikes rented during each hour. For example, a bike rented from 2:59pm to 3:01pm (two minutes) will show as rented at both 2pm and 3pm.</li>
  <li id="noteStation">Only <span id="noteStationToFrom">START</span> station matters. The other end of the trip could be anywhere.</li>
</ul>

<!-- stats -->
<h4>Part 3 Statistics</h4>
<dl id="hourlyStats">
</dl>

<?php if(!$stationID) { ?>
<!-- Ages for all stations -->
<table id="agesTable">
  <caption title="Age in 2015">Age distributions</caption>
  <thead><tr><th>Min</th><th>Avg</th><th>Max</th><th>Station</th></tr></thead>
  <tbody><?php
	$aStations = DB::Run('SELECT * FROM `stations` ORDER BY ageAvg')->fetchAll();
	foreach($aStations as $station) {
		$aAge = DB::Run('SELECT MIN(2015 - `birth_year`) as `ageMin`, 
								MAX(2015 - `birth_year`) as `ageMax`, 
								AVG(2015 - `birth_year`) as `ageAvg` 
						FROM `trips` WHERE `start_station`='. $station['station_id'])->fetch();
		echo "<tr><td>{$aAge['ageMin']}</td>"
				."<td>". round($aAge['ageAvg'], 1) ."</td>"
				."<td>{$aAge['ageMax']}</td>"
				."<td>{$station['name']}</td></tr>\n";
	}
?>
  </tbody>
</table>
<?php } else {
	$aAge = DB::Run('SELECT MIN(2015 - `birth_year`) as `ageMin`, 
							MAX(2015 - `birth_year`) as `ageMax`, 
							AVG(2015 - `birth_year`) as `ageAvg` 
					FROM `trips` WHERE `start_station`='. $stationID)->fetch();
?>
  <dl id="ageStats">
    <dt>Minimum Age: </dt><dd><?= $aAge['ageMin'] ?></dd>
    <dt>Average Age: </dt><dd><?= $aAge['ageAvg'] ?></dd>
    <dt>Maximum Age: </dt><dd><?= $aAge['ageMax'] ?></dd>
  </dl>
<?php } ?>

<hr>
<div id="ftrCopyright">
  Copyright &copy; 2022-<?=date('Y')?> <a href="/">Gary Strawn</a> All rights reserved
</div>

<script>const g_aTrips = <?= GetTrips($stationID, $toFrom, $inventory) ?></script>
<?= IsLocalHost() ? '<script>window.isLocalHost = true</script>' : '' ?>
</body>
</html>
