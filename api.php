<?php //Copyright (c) 2022 Gary Strawn, All rights reserved
//Little Otter Take Home - Bike Rental API
require_once "{$_SERVER['DOCUMENT_ROOT']}/inc/common.php"; //private libraries
//x ob_start();  //cache HTML output, allows ob_end_clean for HTTP redirect

/*
DROP TABLE IF EXISTS `trips`;
CREATE TABLE `trips` (
`trip_id`       INT UNSIGNED  NOT NULL AUTO_INCREMENT,
`tripduration`  INT UNSIGNED  NOT NULL,
`start_time`    DATETIME      NOT NULL,
`stop_time`     DATETIME      NOT NULL,
`start_station` INT UNSIGNED  NOT NULL,
`stop_station`  INT UNSIGNED  NOT NULL,
`bike_id`       INT UNSIGNED  NOT NULL,
`user_type`     VARCHAR(255)  NOT NULL,
`birth_year`    INT UNSIGNED,
`gender`        BOOLEAN       NOT NULL,
PRIMARY KEY (`trip_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `stations`;
CREATE TABLE `stations` (
`station_id`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
`name`        VARCHAR(255) NOT NULL,
`latLong`     POINT        NOT NULL,
PRIMARY KEY (`station_id`),
KEY (`name`)
) ENGINE=InnoDB;
*/

function dbg($msg) { if(IsLocalHost()) echo $msg; }

//---------------------------------------------------------------------------
//BuildDB - read trips.json into SQL db
function BuildDB() {
	$sFile = file_get_contents("trips.json");
	if(!$sFile)  return "Unable to open trips.json";

	$json = json_decode($sFile, true);
	if($json === null)  return "Error parsing trips.json";

	if(!DB::Run('TRUNCATE TABLE `stations`')  ||  !DB::Run('TRUNCATE TABLE `trips`'))
		return 'Error trunating tables';

	$sErr = '';
	foreach($json as $i => $trip) {
		//start station (insert if not exists)
		$idStart = DB::Run('SELECT `station_id` FROM `stations` WHERE `name`=?', 
			[$trip['start_station_name']])->fetchColumn();
		if($idStart === false) {
			if(!DB::Run('INSERT INTO `stations` SET `name`=?, `latLong`=POINT(?,?)', 
					[$trip['start_station_name'], $trip['start station latitude'], $trip['start_station_longitude']])) {
				$sErr .= 'Unable to create new station: '. $trip['start_station_name'];
				continue;
			}
			$idStart = DB::lastInsertId();
		}

		//end station (insert if not exists)
		$idEnd = DB::Run('SELECT `station_id` FROM `stations` WHERE `name`=?', 
			[$trip['end_station_name']])->fetchColumn();
		if($idEnd === false) {
			if(!DB::Run('INSERT INTO `stations` SET `name`=?, `latLong`=POINT(?,?)', 
					[$trip['end_station_name'], $trip['end_station_latitude'], $trip['end_station_longitude']])) {
				$sErr .= 'Unable to create new station: '. $trip['end_station_name'];
				continue;
			}
			$idEnd = DB::lastInsertId();
		}

		//insert trip
		//TODO: validate .json data, or not because this is just a take-home assignment
		if(!DB::Run('INSERT INTO `trips` SET `tripduration`=?, `start_time`=?, `stop_time`=?, `start_station`=?, `stop_station`=?, `bike_id`=?, `user_type`=?, `birth_year`=?, `gender`=?', [$trip['tripduration'], $trip['start_time'], $trip['stop_time'], 
		$idStart, $idEnd, $trip['bike_id'], $trip['usertype'], $trip['birth year'], $trip['gender']]))
			$sErr .= "Error importing trip record[$i]: $trip\n";
	}
	return $sErr ? $sErr : (DB::lastInsertIde() .' trips created');
} //BuildDB


//---------------------------------------------------------------------------
//GetTrips - fetch #bikes rented
function GetTrips($stationID, $toFrom, $inventory) {
	//build query string
	$qry = 'SELECT COUNT(*) FROM `trips` WHERE '
			.'%1$s_time >= "2015-02-%2$02d %3$02d:00:00" AND '
			.'%1$s_time <= "2015-02-%2$02d %3$02d:59:59"';
	if($stationID) {
		$qry .= sprintf(' AND %s_station=?', $toFrom == 'from' ? 'start' : 'stop');
		$param[] = $stationID;
	} else {
		$param = [];
	}

	//build 28*24 array
	$sTrips = '';
	$num = 0; //running total
	for($day = 1;  $day <= 28;  $day++) {
		for($hour = 0;  $hour < 24;  $hour++) {
			$numOut = DB::Run(sprintf($qry, 'start', $day, $hour), $param)->fetchColumn();
			$numIn  = DB::Run(sprintf($qry, 'stop',  $day, $hour), $param)->fetchColumn();
// dbg("<pre>day=$day, hour=$hour, station=".($param[0] ?? 0) ."\n"
// 	."  numOut=$numOut = ". sprintf($qry, 'start', $day, $hour) ."\n"
// 	."   numIn=$numIn = ".  sprintf($qry, 'stop',  $day, $hour) ."</pre>\n");

			if($toFrom == 'to') {
				$num += $numIn; //add arrivals
				$sTrips .= $num; //total rented for this hour
				$num -= $numOut; //subtract departures
			} else {
				$num += $numOut; //add departures
				$sTrips .= $num .','; //total rented for this hour
				$num -= $numIn; //subtract arrivals
			}
		}
	}
	return '['. $sTrips .']';
//x	header('Content-Type: application/json');
//x	return json_encode($aTrips);
} //GetTrips

?>
