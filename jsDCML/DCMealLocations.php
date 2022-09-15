<?php 
/*

William Field

This file uses the Google Maps javascript API to create a map of summer meal locations in Washington, DC. summer_meals is a sql database
containing data from https://opendata.dc.gov/datasets/DCGIS::summer-meals-sites/about. To run this file you will need to have a locally hosted server setup that supports
mysql. I used XAMPP with apache and phpmyadmin to manage mysql. IF going that route then put this file in htdocs, start your apache and mysql server,
 and pull up localhost/DCMealLocations.php in your browser. The google API key should work for anyone running this file

*/


//This PHP code connects to the database and encodes the data as JSON objects

  $dsn = "mysql:host=localhost;dbname=u632350852_summer_meals";
  $username = "root";
  $password = "";

  try{//try catch ensures successful connection
	  $pdo = new PDO($dsn, $username, $password);
	  #echo "connection successful!";
	 
	$stmt = $pdo->query('SELECT * FROM summer_meals_sites');#making a query to mysql in php
	#Above line must contain the table within summer meals db that has info
	$locations = json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));#storing each row as a json object

  }catch(PDOException $e){
	  $error_message = $e->getMessage();
	  echo $error_message;
	  exit();
  }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
	
    <title>DC Meal Locations</title>
    <meta name="viewport" content="initial-scale=1.0">
    <meta charset="utf-8">
    <style>
      /* Always set the map height explicitly to define the size of the div
       * element that contains the map. */
      #map {
        height: 100%;
      }
      html, body {
        height: 100%;
        margin: 0;
        padding: 0;
      }
    </style>
	
	
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
	<Link rel="stylesheet" href="dcml.css">
</head>
  
  <body>


  <p1>
<button type="button" onclick="show(0)">Display locations serving breakfast</button>
<button type="button" onclick="show(1)">Display locations serving lunch</button>
<button type="button" onclick="show(2)">Display locations serving dinner</button>
<button type="button" onclick="show(3)">Display all locations</button>
<input class="timepicker" name="timepicker"/>
<button id="btn">What's Open?</button>

	</p1>
    <div id="map"></div>
	
    <script  src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script> 
	<script  src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>
    
	<script>
	$(document).ready(function(){
    $('input.timepicker').timepicker({
        timeFormat: 'HH:mm:ss',
        // year, month, day and seconds are not important
        minTime: new Date(0, 0, 0, 5, 0, 0),
        maxTime: new Date(0, 0, 0, 23, 0, 0),
        // time entries start being generated at 6AM but the plugin 
        // shows only those within the [minTime, maxTime] interval
        startHour: 6,
        // the value of the first item in the dropdown, when the input
        // field is empty. This overrides the startHour and startMinute 
        // options
        startTime: new Date(0, 0, 0, 8, 20, 0),
        // items in the dropdown are separated by at interval minutes
        interval: 10,
		
		dropdown: true,
		
		dynamic: true,
		
    });
	$('#btn').click(function(){
		var d = $('input.timepicker').val();
		show(3, true, d);//making markers for all locations open at the desired time
		});
	});
	
	//creates the map and handles the algorithmic operations associated with the buttons.
	
	const locations = <?php echo $locations; ?>;//taking in our location objects from server to frontend
		
	let map;
	let markers = [];	//3 functions below from google maps API
	let openInfoWindow;
	let sl = 3;//the current locationtype to be shown (0,1,2, or 3)
	let byTime = false;//are we checking by a time?
	
	// Sets the map on all markers in the array.
	function setMapOnAll(map) {
	  for (let i = 0; i < markers.length; i++) {
		markers[i].setMap(map);
	  }
	}

	// Removes the markers from the map, but keeps them in the array.
	function hideMarkers() {
	  setMapOnAll(null);
	}

	// Deletes all markers in the array by removing references to them.
	function deleteMarkers() {
	  hideMarkers();
	  markers = [];
	}
	
	//closes an open info window
	function closeOpenInfoWindow(){
		if (openInfoWindow){
			openInfoWindow.close();
		}
	}
	
	function makeMarker(i){//uses html buttons for content string
		
		const marker = new google.maps.Marker({
							position: new google.maps.LatLng(locations[i].LATITUDE, locations[i].LONGITUDE),
							map: map,
							label: locations[i].WARD.substr(-1),
							title: locations[i].NAME
						});
						markers.push(marker);//adds marker to marker array
						
						//creating content string
						
						let contentString = "This summer meal  location is " + locations[i].NAME + 
						". It is open " + locations[i].DAYS_OPEN + ". <br>";
						
						if(locations[i].BREAKFAST_TIME){
							contentString += "<br>Breakfast is from " + locations[i].BREAKFAST_TIME;
						}if(locations[i].LUNCH_TIME){
							contentString += "<br>Lunch is from " + locations[i].LUNCH_TIME;
						}if(locations[i].DINNER_SUP){
							contentString += "<br>Dinner is from " + locations[i].DINNER_SUP;
						}if(locations[i].SNACK_TIME){
							contentString += "<br>Snack time is " + locations[i].SNACK_TIME;
						}
						
						const infowindow = new google.maps.InfoWindow({
							content: contentString,
						  });
						  marker.addListener("click", () => {
							closeOpenInfoWindow();
							infowindow.open(map, marker);
							openInfoWindow = infowindow;
						  });	
	}
	
	function show(sl, byTime = false, dTime = 0) {//determines whether to show breakfast lunch dinner or all, uses the html buttons for input
			deleteMarkers();//clear our marker array and the map
			
			var infowindow = new google.maps.InfoWindow;
			
			var i;

			for (i = 0; i < locations.length; i++) {  //making each marker, put at the latitude and longitude of each location
				if(sl == 0 && locations[i].BREAKFAST_TIME||
					sl == 1 && locations[i].LUNCH_TIME||
					sl == 2 && locations[i].DINNER_SUP||
					sl == 3){ 
					
					if(byTime){
					
						if(locations[i].BREAKFAST_TIME && inTime(dTime, locations[i].BREAKFAST_TIME) ||
							locations[i].LUNCH_TIME && inTime(dTime, locations[i].LUNCH_TIME, true)||
							locations[i].DINNER_SUP && inTime(dTime, locations[i].DINNER_SUP, true)||
							locations[i].SNACK_TIME && inTime(dTime, locations[i].SNACK_TIME, true)){
							
							makeMarker(i);
							}
							
					}else if(!byTime){
						makeMarker(i);
						}
					}
			}
	  }
	
	function timeParse(dirtyTime, meal){//returns array containing both times, parses yucky time strings from sql into prettier strings that we can actually use
		var tTime = dirtyTime.toString().split('-');
		
		if(meal == true){
		for(var i = 0; i < tTime.length; i++){
			
			var q = tTime[i].substr(tTime[i].length - 2);
			if(q === "pm" || q ==="am"){
				console.log(q);
				tTime[i] = tTime[i].substr(0, tTime[i].length - 2);
				console.log(tTime[i].toString());
			}
			var g = tTime[i].split(':');
				if(parseInt(g[0]) < 8){
					var a = parseInt(g[0]);
					g[0] = a + 12;
					tTime[i] = g[0].toString() + ":" + g[1].toString();
				}
		}
		}
		
		return tTime;
	}
	function hrParse(tTime){//returns array containing hr and minutes
		try{
		var hrs = tTime.toString().split(':');
		return hrs;
		}catch(o){
			console.log(o.toString());
		}
	}
	
	function inTime(dTime, lTime, meal = false){//checks if a location is open at the desired time. used in Whats Open? button
		const SE = timeParse(lTime, meal);//lTime is the string containing the open times. this line splits those into two seperate times
		
		const cTime = hrParse(dTime);//splitting up our times (getting them to be ints
		const sT = hrParse(SE[0]);
		const eT = hrParse(SE[1]);
		
		
		//creating dates for the location times. The desired time has already been taken in as a date
		const start = new Date(0, 0, 0, sT[0], sT[1], 0, 0);
		const end = new Date(0, 0, 0, eT[0], eT[1], 0, 0);
		
		const tTime = new Date(0, 0, 0, cTime[0], cTime[1], 0, 0);
		
		if(tTime >= start && tTime <= end){
			return true;
		}else{
			return false;
		}
	}
	
	function initMap() {//making the map
	  const capitol = { lat: 38.889805, lng: -77.009056 };//capitol building
      map = new google.maps.Map(document.getElementById('map'), {
		zoom: 12,
		center: new google.maps.LatLng(38.89511,-77.03637)//centers on DC
		
	  });
	  show(sl, false);//by default show all locations
      }
    </script>
	
	<script src="https://maps.googleapis.com/maps/api/js?key=myKey&callback=initMap"
    async defer></script>
	

  </body>
</html>