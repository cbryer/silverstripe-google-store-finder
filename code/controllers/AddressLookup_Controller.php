<?php

class AddressLookup_Controller extends ContentController
{

    public static $allowed_actions = array(
        'findLatLongForAddress'
    );

    /**
     * retrieves latitude and longitude for a supplied address.
     * @returns array of Latitude and Longitude
     */
    public function findLatLongForAddress()
    {
        if ($address=$_POST['address']) {
            $prepAddr = str_replace(' ', '+', $address);
            $geocode=file_get_contents('http://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false');
            $output= json_decode($geocode);
            $latitude = $output->results[0]->geometry->location->lat;
            $longitude = $output->results[0]->geometry->location->lng;
            return json_encode(array("Latitude" => $latitude, "Longitude" => $longitude));
        } else {
            $this->response->setStatusCode(500);
            die("no address supplied");
        }
    }
}
