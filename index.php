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
//view=<from (defualt) | to | inv[entory]>:
//  from = show "Rented From" = trips with start_station = <station>
//  to   = show "Returned To" = trips with stop_station = <station>
//  inv  = show running inventory (+/- bikes) at current station
$stationID = isset($_GET['station'])  ?  (intval($_GET['station']) ?: 0)  :  0;
$view = strtolower($_GET['view'] ?? 'from');
if($view == 'inventory')  $view = 'inv'; //allow 'inv' or 'inventory'
if(!in_array($view, ['from', 'to', 'inv']))  $view = 'from';

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
  <div id="hourlyView">
    <label><input type="radio" name="hourlyView" value="from"<?= 
			$view == 'from' ? ' checked' : '' ?>> Rented from</label>
    <label><input type="radio" name="hourlyView" value="to"<?=
			$view == 'to' ? ' checked' : '' ?>> Returned to</label>
    <label><input type="radio" name="hourlyView" value="inv"<?=
			$view == 'inv' ? ' checked' : '' ?>> Inventory</label>
  </div>
  <div id="hourlyInput">
    <label>Feb <input id="hourlyDate" type="number" min=1 max=28> 2015</label>
    <label>Hour: <input id="hourlyHour" type="number" min=0 max=23></label>
    <label>Rented: <span id="hourlyRented">-</span></label>
  </div>
</header>

<!-- hourly table -->
<img id="hourlyTableLoading" src="/img/wait_lg.gif">
<table id="hourlyTable" style="display:none">
  <caption><?php
	$aCaption = [
		'from' => 'Bikes rented during the hour',
		'to'   => 'Bikes returned during the hour',
		'inv'  => 'Bikes in-use during the hour<br>+1 when checked out,  -1 when returned'
	];
	echo $aCaption[$view];
?></caption>
  <thead><tr><th></th><th>am</th><?php 
		for($h=1; $h<24; $h++)  echo '<th>'.(($h%12) ?: 'pm').'</th>'; ?></tr></thead>
  <tbody id="hourlyTableBody">
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

<!-- stats -->
<dl id="hourlyStats">
</dl>

<!-- ages --><?php
$aAge = DB::Run('SELECT MIN(2015 - `birth_year`) as `ageMin`, 
						MAX(2015 - `birth_year`) as `ageMax`, 
						AVG(2015 - `birth_year`) as `ageAvg` 
				FROM `trips`'
		.($stationID ? " WHERE `start_station`=$stationID" : ''))->fetch();
?>
<dl id="ageStats">
  <dt>Minimum Age: </dt><dd><?= $aAge['ageMin'] ?></dd>
  <dt>Average Age: </dt><dd><?= round($aAge['ageAvg'], 1) ?></dd>
  <dt>Maximum Age: </dt><dd><?= $aAge['ageMax'] ?></dd>
</dl>

<?php if(!$stationID) { ?>
<!-- Ages for all stations -->
<table id="agesTable">
  <caption title="Age in 2015">Age distributions</caption>
  <thead><tr><th>Min</th><th>Avg</th><th>Max</th><th>Station</th></tr></thead>
  <tbody><?php
	$aStations = DB::Run('SELECT * FROM `stations` ORDER BY name')->fetchAll();
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
} ?>
  </tbody>
</table>


<div id="srcLink"><a href="https://github.com/g6strawn/littleotter">Source on GitHub</a></div>
<hr>
<div id="ftrCopyright">
  Copyright &copy; 2022-<?=date('Y')?> <a href="https://gstrawn.dev">Gary Strawn</a> All rights reserved
</div>

<script>const g_aTrips = <?= GetTrips($stationID, $view) ?></script>
<?= IsLocalHost() ? '<script>window.isLocalHost = true</script>' : '' ?>
</body>
</html>
