//Copyright (c) 2022 Gary Strawn, All rights reserved
//Little Otter Take Home - Bike Rental API

let tdCurr = null; //currently selected <table><td>


//---------------------------------------------------------------------------
//updateTable - Create <table> of hourly bike rentals
function updateTable(aTrips) {
	if(!aTrips  ||  aTrips.length != 24*28)  return;
	for(let day = 1, i=0;  day <= 28;  day++)
		for(let hour = 0;  hour < 24;  hour++, i++)
			document.getElementById('td'+i).textContent = aTrips[i] ? aTrips[i] : '';
} //updateTable


//---------------------------------------------------------------------------
//updateCanvas - Create heat-map of hourly bike rentals
function updateCanvas(aTrips) {
	if(!aTrips  ||  aTrips.length != 24*28)  return;

	//create properly sized canvas
	const tbl = document.getElementById('hourlyTable');
	const canvas  = document.createElement('canvas');
	canvas.width  = tbl.clientWidth;
	canvas.height = tbl.clientHeight;

	//clear canvas
	const ctx = canvas.getContext('2d');
	ctx.fillStyle = 'rgba(0,0,0,0)'; //top/left header areas are transparent
	ctx.clearRect(0, 0, canvas.width, canvas.height);

	//fill cells with green squares
	const maxRented = Math.max(...aTrips);
	for(let day = 1, i = 0;  day <= 28;  day++) {
		for(let hour = 0;  hour < 24;  hour++, i++) {
			const percent = aTrips[i] / maxRented;
			const green = percent  ?  Math.round((percent * 223) + 32)  :  0; //only 0 is dark
			ctx.fillStyle = `rgb(0, ${green}, 0)`;

			const td = tbl.querySelector('#td'+i);
			ctx.fillRect(td.offsetLeft, td.offsetTop, td.clientWidth, td.clientHeight);
		}
	}

	//set canvas as table's background image
	tbl.style.backgroundImage = 'url('+canvas.toDataURL()+')';
} //updateCanvas


//---------------------------------------------------------------------------
//updateStats - Calculate various statistics
function updateStats(aTrips) {
	if(!aTrips  ||  aTrips.length != 24*28)  return;
	let total = 0;
	let iBusy1 = -1, iBusy2 = -1, iBusy3 = -1; //1st, 2nd, 3rd busiest
	let aHour = Array(24).fill(0);
	for(let day = 1, i=0;  day <= 28;  day++) {
		for(let hour = 0;  hour < 24;  hour++, i++) {
			total += aTrips[i];
			aHour[hour] += aTrips[i];
			if(iBusy1 < 0  ||  aTrips[i] > aTrips[iBusy1]) {
				iBusy3 = iBusy2;
				iBusy2 = iBusy1;
				iBusy1 = i;
			}
		}
	}

	//add various statistics
	let aStats = [];
	function strHour(h) { return (h % 12 || 12) + ((h % 24) < 12 ? "am" : "pm"); }
	function strFloat(x) { return parseFloat(x.toFixed(1)); } //ex: 2.0 -> 2
	function addStat(hdr, val) {
		const dt = document.createElement('dt');
		dt.textContent = hdr +': ';
		aStats.push(dt);
		const dd = document.createElement('dd');
		dd.textContent = val;
		aStats.push(dd);
	}

	addStat('Average', strFloat(total / (28*24)) +' bikes / hour');

	//ex: Busiest: 20 Feb, 2am @ 23.4 bikes
	function sDay(i)  { return Math.floor(i / 24) + 1; }
	function sHour(i)  { return strHour(i); }
	function sAvg(i)   { return strFloat(aTrips[i]); }
	addStat(    'Busiest', `${sDay(iBusy1)} Feb, ${sHour(iBusy1)} @ ${sAvg(iBusy1)} bikes`);
	addStat('2nd busiest', `${sDay(iBusy2)} Feb, ${sHour(iBusy2)} @ ${sAvg(iBusy2)} bikes`);
	addStat('3rd busiest', `${sDay(iBusy3)} Feb, ${sHour(iBusy3)} @ ${sAvg(iBusy3)} bikes`);

	//ex: Best time: 5pm @ 3.1 bikes average
	let iTime1 = -1, iTime2 = -1, iTime3 = -1; //1st, 2nd, 3rd busiest
	for(let hour = 0;  hour < 24;  hour++)
		if(iTime1 < 0  ||  aHour[hour] > aHour[iTime1]) {
			iTime3 = iTime2;
			iTime2 = iTime1;
			iTime1 = hour;
		}
	addStat(    'Best time', `${strHour(iTime1)} @ ${strFloat(aHour[iTime1] / 24)} bikes`);
	addStat('2nd best time', `${strHour(iTime2)} @ ${strFloat(aHour[iTime2] / 24)} bikes`);
	addStat('3rd best rime', `${strHour(iTime3)} @ ${strFloat(aHour[iTime3] / 24)} bikes`);

	document.getElementById('hourlyStats').replaceChildren(...aStats);
} //updateStats


/* //x
This was removed in favor of a server-side processing
//---------------------------------------------------------------------------
//fetchHourly - GET data for part 1) hourly bike rentals
function fetchHourly() {
	//show loading image, reset table
	document.getElementById('hourlyTableLoading').style.display = '';
	document.getElementById('hourlyTable').style.display = 'none';
	document.getElementById('hourlyToFrom').style.display = 'none';
	document.querySelectorAll('#hourlyNotes li').forEach(li => li.style.display = 'none');
	document.getElementById('hourlyStats').replaceChildren();
	if(tdCurr) {
		tdCurr.classList.remove('tdCurr');
		tdCurr = null;
	}

	//fetch new table data
	const station = Number(document.getElementById('station').value);
	const toFrom = document.querySelector('#hourlyToFrom input:checked').value;
	const url = new URL(document.location);
	url.search = new URLSearchParams({
		station: station,
		toFrom: toFrom
	});
	fetch(url).then(async resp => {
		//check for valid response
		if(!resp.ok)
			throw new Error(resp.status +' '+ resp.statusText); //treat 4xx/5xx as errors

		//HTTP OK (2xx);  trust the server's content-type
		if(resp.headers.get("content-type").indexOf('application/json') !== -1)
			return resp.json();

		//treat non-json as error; ex: show PHP error
		if(isLocalHost) //don't show server errors on live server
			throw new Error(await resp.text());
	}).then(aTrips => {
		//update table with new data
		document.getElementById('hourlyTableLoading').style.display = 'none';
		document.getElementById('hourlyTable').style.display = '';
		updateTable(aTrips);
		updateCanvas(aTrips);
		updateStats(aTrips);
		document.getElementById('noteNumber').style.display = '';
		if(station) { //if station (as opposed to 0 = all stations)
			document.getElementById('hourlyToFrom').style.display = '';
			document.getElementById('noteStation').style.display = '';
			document.getElementById('noteStationToFrom').textContent = 
				(toFrom == 'from'  ?  'START'  :  'STOP');
		}
	}).catch(err => {
		console.error(err);
	});
} //fetchHourly
*/

//---------------------------------------------------------------------------
//redirect - update GET parameters
function redirect() {
	document.getElementById('hourlyTableLoading').style.display = '';
	document.getElementById('hourlyTable').style.display = 'none';
	document.getElementById('hourlyNotes').style.display = 'none';
	document.getElementById('hourlyStats').style.display = 'none';
	if(document.getElementById('allAges'))
		document.getElementById('allAges').style.display = 'none';

	const url = new URL(document.location);
	url.search = new URLSearchParams({
		station: Number(document.getElementById('station').value),
		toFrom: document.querySelector('#hourlyToFrom input:checked').value
	});
	location.replace(url);
} //redirect


//---------------------------------------------------------------------------
//<select station>.onChange - refresh data with selected station info
document.getElementById('station')?.addEventListener('change', () => redirect() );


//---------------------------------------------------------------------------
//<radio to|from>.onClick - refresh data with to|from station
document.querySelectorAll('#hourlyInput input[type=radio]').forEach(btn => 
	btn.addEventListener('click', () => redirect()));


//---------------------------------------------------------------------------
//<input day|hour>.onChange - highlight correponding table cell
document.querySelectorAll('#hourlyInput input[type=number]').forEach(elem => {
	elem.addEventListener('change', ev => {
		const day  = Number(document.getElementById('hourlyDate').value);
		const hour = Number(document.getElementById('hourlyHour').value);
		if(tdCurr)  tdCurr.classList.remove('tdCurr');
		tdCurr = document.getElementById('td'+((day-1)*24 + hour));
		tdCurr.classList.add('tdCurr');
		document.getElementById('hourlyRented').textContent = 
			tdCurr.textContent  ?  tdCurr.textContent  :  0; //'' -> 0
	});
});


//---------------------------------------------------------------------------
//<table hourly>.onMouseOver - update header info to match highlighted table cell
document.getElementById("hourlyTable")?.addEventListener('mouseover', ev => {
	if(ev.target.id.slice(0,2) != 'td')  return;

	if(tdCurr)  tdCurr.classList.remove('tdCurr');
	tdCurr = ev.target;
	tdCurr.classList.add('tdCurr');

	const i = Number(ev.target.id.slice(2)); //<td id="tdXX">
	document.getElementById('hourlyDate').value = Math.floor(i / 24)+1;
	document.getElementById('hourlyHour').value = i % 24;
	document.getElementById('hourlyRented').textContent = 
		ev.target.textContent  ?  ev.target.textContent  :  0; //'' -> 0
});


document.getElementById('hourlyTableLoading').style.display = 'none';
document.getElementById('hourlyTable').style.display = '';
updateTable(g_aTrips);
updateCanvas(g_aTrips);
updateStats(g_aTrips);
