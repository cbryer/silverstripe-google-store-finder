<?php

class StoreLocation extends Marker
{
    
    public $Distance = null;

    public static $singular_name = "Store Location";

    public static $db = array(
        'Phone' => 'Text',
        'Website' => 'Text'
    );
    
    public function getCMSFields()
    {
        return new FieldList(
            new TextField('Name'),
            new TextField('Address'),
            new TextField('Phone'),
            new TextField('Website'),
            new NumericField('Latitude'),
            new NumericField('Longitude')
        );
    }
}
