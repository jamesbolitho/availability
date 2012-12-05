<?php
class BookedData extends DataObject {
	
	static $singular_name = 'Booking Information';
    static $plural_name = 'Booking Information';
	
	public static $db = array(
		"Title" => "Text",
		"dateStart" => "Date",
		"dateEnd" => "Date"
	);

	public static $has_one = array(
		"AvailabilityCalendarPage" => "AvailabilityCalendarPage"
	);
	
	public static $has_many = array(
	);
	
	static $default_sort = 'dateStart ASC';
	
	public function getCMSFields(){
		$fields = new FieldList();
		
		$fields->push(new TextField('Title'));
		$fields->push($arrivalDateField = new DateField('dateStart', 'Date of arrival'));
      	$fields->push($departureDateField = new DateField('dateEnd', 'Date of departure'));
		
		$arrivalDateField->setConfig('dateformat', 'dd-MM-YYYY');
		$arrivalDateField->setConfig('showcalendar', true);
	  	$arrivalDateField->setConfig('showdropdown', true);
		
		$departureDateField->setConfig('dateformat', 'dd-MM-YYYY');
		$departureDateField->setConfig('showcalendar', true);
	  	$departureDateField->setConfig('showdropdown', true);
		
		return $fields;
	}
}