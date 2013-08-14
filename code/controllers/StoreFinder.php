<?php
class StoreFinder extends Page {

    public static $MarkerClass = 'StoreLocation';

	public static $db = array(
		"StartLat" => 'Decimal',
		"StartLong" => 'Decimal',
		"StartZoom" => 'Int(2)',
        "GeolocatedZoom" => 'Int(8)',
        "MapType" => 'enum("Roadmap,Satellite","Roadmap")'
	);

	public static $has_one = array(
	);

    /**
     * TODO: add these to the config file so NZ developers can make USA not the center of the world. or make it interactive.
     *
     */
    public static $defaults = array(
        "StartLat" => '40',
        "StartLong" => '-100',
        "StartZoom" => '2',
        "GeolocatedZoom" => '8',
        "MapType" => 'Roadmap'
    );

    public function getCMSFields() {

        Requirements::javascript('google-store-finder/javascript/store-creator.js');


        $fields = parent::getCMSFields();

        // Create a gridfield to hold the student relationship
        $latField = new NumericField('StartLat', 'Starting Latitude',40);
        $longField = new NumericField('StartLong','Starting Longitude',-100);
        $zoomField = new NumericField('StartZoom','Starting Zoom',2);

        $geoLocatedZoomField = new NumericField('GeolocatedZoom', "Geolocated Zoom", 8);

        $mapType = new DropdownField('MapType', 'Map Type', array("RoadMap" => "Road Map", "Satellite" => "Satellite"), "RoadMap");

        // Create a tab named "Students" and add our field to it
        $fields->addFieldToTab('Root.Map', new LiteralField("geolocation-explanation", '<p>This Module will attempt to detect users location if their device supports it.  if not, please enter the starting latitude and longitude. (Default values center North and South America in the map).</p>'));
        $fields->addFieldToTab('Root.Map', $latField);
        $fields->addFieldToTab('Root.Map', $longField);
        $fields->addFieldToTab('Root.Map', $zoomField);
        $fields->addFieldToTab('Root.Map', $geoLocatedZoomField);

        $fields->addFieldToTab('Root.Map', $mapType);

        $markerClassObject = Injector::inst()->create(self::$MarkerClass);

        // Create a default configuration for the new GridField, allowing record editing
        $config = GridFieldConfig_RelationEditor::create();
        // Set the names and data for our gridfield columns
        $config->getComponentByType('GridFieldDataColumns')->setDisplayFields(array(
            'Name' => 'Name'//,
            //'Project.Title'=> 'Project' // Retrieve from a has-one relationship
        ));
        // Create a gridfield to hold the student relationship
        $locationField = new GridField(
            self::$MarkerClass, // Field name
            DataObject::get_static(self::$MarkerClass, "singular_name"), // Field title
            DataObject::get(self::$MarkerClass), // List of all related students
            $config
        );
        // Create a tab named "Students" and add our field to it
        $fields->addFieldToTab('Root.Locations', $locationField);

        return $fields;
    }



}

class StoreFinder_Controller extends Page_Controller {

	public static $allowed_actions = array (
		'locationSearch',
        'findLatLongForAddress'
	);

    public function init() {
        parent::init();

        $script = "var startLat = ".$this->dataRecord->StartLat.";\n";
        $script .= "var startLong = ".$this->dataRecord->StartLong.";\n";
        $script .= "var startZoom = ".$this->dataRecord->StartZoom.";\n";
        $script .= "var geoLocatedZoom = ".strtoupper($this->dataRecord->GeolocatedZoom).";\n";
        $script .= "var mapType = '".strtoupper($this->dataRecord->MapType)."';";


        Requirements::customScript($script);
        Requirements::javascript('http://maps.googleapis.com/maps/api/js?sensor=false');
        Requirements::javascript('framework/thirdparty/jquery/jquery.js');
        Requirements::javascript('google-store-finder/javascript/store-finder.js');
        Requirements::css('google-store-finder/css/store-finder.css');
    }

	
	/**
	 * url segment for searching for locations
	 */
	public function locationSearch(){
		$center_lat = isset($_GET["lat"]) ? $_GET["lat"] : 40;
		$center_lng = isset($_GET["lng"]) ? $_GET["lng"] : -100;
		$radius = isset($_GET["radius"]) ? $_GET["radius"] : 500;
		$locations = $this->getLocationsByLatLong($center_lat, $center_lng, $radius, null);

		return $this->customise(array("locations" => $locations))->renderWith(StoreFinder::$MarkerClass."_XML");
	}
	
	/**
	 *  used by template to display results of marker search
	 */
	public function getLocationsByLatLong($lat=37, $long=-122, $distance=25, $limit=null){
		$result = $this->getLocationSQLResultsByLatLong($lat, $long, $distance, $limit);
		
		$arr = array();

        $markerClass = StoreFinder::$MarkerClass;

		// Iterate over results
		foreach($result as $row) {
			$do = Injector::inst()->create($markerClass, $row, false, $markerClass);
			$do->setDistance($row['Distance']);
			//echo $row['ID']." ".$row['Distance']."<br/>";
		  array_push($arr, $do);
		}
		
		$arrData = new ArrayList($arr);

		return $arrData;
	}
	
	/**
	 * Retrieves Locations by lat, long, distance, and optionally a limit.
	 */
	public function getLocationSQLResultsByLatLong($lat=37, $long=-122, $distance=25, $limit=null){
		//$data = DB::query('SELECT "ID" FROM "Marker" LIMIT 0 , '.$limit.';')->value();
		//$query = 'SELECT "ID", ( 3959 * acos( cos( radians('.$lat.') ) * cos( radians( Latitude ) ) * cos( radians( Longitude ) - radians('.$long.') ) + sin( radians('.$lat.') ) * sin( radians( Latitude ) ) ) ) AS "Distance" FROM "Marker" HAVING "Distance" < '.$distance.' ORDER BY "Distance" LIMIT 0 , '.$limit.';';

        $markerClass = StoreFinder::$MarkerClass;
		$sqlQuery = new SQLQuery();
		$sqlQuery->setFrom($markerClass);
		$sqlQuery->selectField('*');
		$sqlQuery->selectField('( 3959 * acos( cos( radians('.$lat.') ) * cos( radians( Latitude ) ) * cos( radians( Longitude ) - radians('.$long.') ) + sin( radians('.$lat.') ) * sin( radians( Latitude ) ) ) )', 'Distance');
		$sqlQuery->setHaving("Distance < ".$distance);

		$sqlQuery->setOrderBy('Distance');
		$sqlQuery->setLimit($limit);

        if($markerClass != 'Marker'){
            $sqlQuery->addLeftJoin("Marker", 'Marker.ID = '.$markerClass.'.ID');
        }
        $this->extraSQL($sqlQuery);

		// Execute and return a Query object
		$result = $sqlQuery->execute();

		return $result;
	}
	
	/**
	 * hook for sub classes.
	 */
	protected function extraSQL($sqlQuery){
		
	}

}