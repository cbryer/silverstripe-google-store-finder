/**
 * required for google map location search.
 */
var map;
var markers = [];
var infoWindow;
var locationSelect;

/**
 * required for geo-location
 */
var browserSupportFlag =  new Boolean();

function initializeGeoLocation() {
    // Try W3C Geolocation (Preferred)
    if(navigator.geolocation) {
        browserSupportFlag = true;
        navigator.geolocation.getCurrentPosition(function(position) {
            var initialLocation = new google.maps.LatLng(position.coords.latitude,position.coords.longitude);
            map.setCenter(initialLocation);
            map.setZoom(geoLocatedZoom);
        }, function() {
            handleNoGeolocation(browserSupportFlag);
        });
    }
    // Browser doesn't support Geolocation
    else {
        browserSupportFlag = false;
        handleNoGeolocation(browserSupportFlag);
    }
}

/**
 * handles situations where the browser does not support geolocation.
 * @param errorFlag
 */
function handleNoGeolocation(errorFlag) {
//    if (errorFlag == true) {
//        alert("Geolocation service failed.");
//    } else {
//        alert("Your browser doesn't support geolocation.");
//    }
    var defaultLocation = new google.maps.LatLng(startLat, startLong);
    map.setCenter(defaultLocation);
    map.setZoom(startZoom);
}


/**
 * loads the original map.
 * assumes the following variables are included from the controller:
 *      startLat
 *      startLong
 *      startZoom
 *      geoLocatedZoom
 *      mapType
 */
function load() {
	map = new google.maps.Map(document.getElementById("map"), {
    	//center: new google.maps.LatLng(startLat, startLong),
		zoom: startZoom,
		mapTypeId: google.maps.MapTypeId[mapType],
		mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU}
	});
	infoWindow = new google.maps.InfoWindow();

	locationSelect = document.getElementById("locationSelect");
	locationSelect.onchange = function() {
    	var markerNum = locationSelect.options[locationSelect.selectedIndex].value;
		if (markerNum != "none"){
			google.maps.event.trigger(markers[markerNum], 'click');
		}
	};
}

/**
 * event handler bound to click event of the 'search for locations by zip code' button
 */
function searchLocations() {
	var address = document.getElementById("addressInput").value;
	var geocoder = new google.maps.Geocoder();
	geocoder.geocode({address: address}, function(results, status) {
    	if (status == google.maps.GeocoderStatus.OK) {
			searchLocationsNear(results[0].geometry.location);
		} else {
			alert(address + ' not found');
		}
	});
}

/**
 * function called after user enters a zipcode and google geocodes the zipcode to latlong coords.
 * this function takes the lat long and does an ajax call to retrieve locations close to the supplied parameters.
 * @param center
 */
function searchLocationsNear(center) {
    clearLocations();
    console.log(center.lat());
    console.log(center.lng());
    var radius = document.getElementById('radiusSelect').value;
    var searchUrl = 'StoreFinder_Controller/locationSearch?lat=' + center.lat() + '&lng=' + center.lng() + '&radius=' + radius;
    downloadUrl(searchUrl, function(data) {
        var xml = parseXml(data);
        var markerNodes = xml.documentElement.getElementsByTagName("marker");
        if(markerNodes.length > 0){
            var bounds = new google.maps.LatLngBounds();
            for (var i = 0; i < markerNodes.length; i++) {
                var name = markerNodes[i].getAttribute("name");
                var address = markerNodes[i].getAttribute("address");
                var distance = parseFloat(markerNodes[i].getAttribute("distance"));
                var latlng = new google.maps.LatLng(
                    parseFloat(markerNodes[i].getAttribute("lat")),
                    parseFloat(markerNodes[i].getAttribute("lng"))
                );

                createOption(name, distance, i);
                createMarker(latlng, name, address);
                bounds.extend(latlng);
            }
            map.fitBounds(bounds);
        } else {
            alert("no locations found.");
        }
    });
}


/**
 * removes locations from the current google map.
 */
function clearLocations() {
	infoWindow.close();
	for (var i = 0; i < markers.length; i++) {
		markers[i].setMap(null);
	}
	markers.length = 0;

	locationSelect.innerHTML = "";
	var option = document.createElement("option");
	option.value = "none";
	option.innerHTML = "See all results:";
	locationSelect.appendChild(option);
	locationSelect.style.visibility = "visible";
}

/**
 * googles method to perform ajax call, and callback a function when done.
 * used in searching for locations near a lat & long.
 * @param url
 * @param callback
 */
function downloadUrl(url,callback) {
	var request = window.ActiveXObject ? new ActiveXObject('Microsoft.XMLHTTP') : new XMLHttpRequest;
	
	request.onreadystatechange = function() {
		if (request.readyState == 4) {
			request.onreadystatechange = doNothing;
			callback(request.responseText, request.status);
		}
	};

	request.open('GET', url, true);
	request.send(null);
}



/**
 * creates a marker on the google map for a location found in the database after a zipcode search.
 * @param latlng
 * @param name
 * @param address
 */
function createMarker(latlng, name, address) {
	var html = "<b>" + name + "</b> <br/>" + address;
	var marker = new google.maps.Marker({
    	map: map,
		position: latlng
	});
	google.maps.event.addListener(marker, 'click', function() {
    	infoWindow.setContent(html);
		infoWindow.open(map, marker);
	});
	markers.push(marker);
}

/**
 * adds a location as a dropdown menu option after user searches by zipcode.
 * @param name
 * @param distance
 * @param num
 */
function createOption(name, distance, num) {
	var option = document.createElement("option");
	option.value = num;
	option.innerHTML = name + "(" + distance.toFixed(1) + ")";
	locationSelect.appendChild(option);
}

/**
 * parse xml returned by location zipcode search.
 * @param str
 * @returns {*}
 */
function parseXml(str) {
	if (window.ActiveXObject) {
		var doc = new ActiveXObject('Microsoft.XMLDOM');
		doc.loadXML(str);
		return doc;
	} else if (window.DOMParser) {
		return (new DOMParser).parseFromString(str, 'text/xml');
	}
}


/**
 * function to null out ajax request
 */
function doNothing() {}


/**
 * document ready event handling.
 */
jQuery(document).ready(function($){
	load();
    $("#searchLocations").click(searchLocations);
    initializeGeoLocation();
});
