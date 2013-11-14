<?php
class AvailabilityCalendarPage extends Page {
	
	private static $db = array(
		'DisplayMonths' => 'Int'
	);

	private static $has_one = array(
	
	);
	
	private static $has_many = array(
		"BookedData" => "BookedData"
	);
	
	private static $defaults = array(
    	'DisplayMonths' => 12
  	);
	
	public static $singular_name = 'Availability Calendar';
 	public static $plural_name = 'Availability Calendar';
	public static $description = "Availability Calendar Page";
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();	
		
		$ItemTable = $this->BookedData();
		
		$config = GridFieldConfig::create();
		
		$config->addComponent(new GridFieldToolbarHeader());
		$config->addComponent(new GridFieldAddNewButton('toolbar-header-right')); 
		$config->addComponent(new GridFieldPaginator(15));
		$config->addComponent(new GridFieldDataColumns());
		$config->addComponent(new GridFieldEditButton());
		$config->addComponent(new GridFieldSortableHeader()); 
		$config->addComponent(new GridFieldDetailForm()); // needed to ensure edit form is available
		$config->addComponent(new GridFieldDeleteAction());
		$config->addComponent(new GridFieldFilterHeader());
					
		$ItemTable = new GridField(
			'BookedData',
			'Booking Information',
			$ItemTable,
			$config
		);
		
		$fields->addFieldToTab("Root.Availability",$ItemTable);
		
		$columns = $ItemTable->getConfig()->getComponentByType('GridFieldDataColumns');
			
		//Set the formatting of the grid field columns
		$columns->setDisplayFields(array(
			'Title' => 'Title',
			'dateStart' => 'Date of arrival',
			'dateEnd' => 'Date of departure'
		));
		$columns->setFieldCasting(array(
			'dateStart' => "Date->Nice",
			'dateEnd' => "Date->Nice"
		));
		
		$fields->addFieldToTab("Root.Main", new DropdownField('DisplayMonths', 'Number of months to display', array('2'=>'2', '6'=>'6','12'=>'12','18'=>'18','24'=>'24','36'=>'36')));
		
		return $fields;
	}
}

class AvailabilityCalendarPage_Controller extends Page_Controller {
	
	public function init() {
		parent::init();	
		Requirements::css("availability/css/style.css");
	}
	
	public function showCalendar() {
		$year = date('Y');
		$month = date('n');
		
		$output = "";
		
		for($m=0; $m < $this->DisplayMonths; $m++) {
			// Get today, reference day, first day and last day info
			
		    if (($year == 0) || ($month == 0)){
		       $referenceDay = getdate();
		    }
			else {
		       $referenceDay = getdate(mktime(0,0,0,$month,1,$year));
		    }
		    $firstDay = getdate(mktime(0,0,0,$referenceDay['mon'],1,$referenceDay['year']));
			$lastDay  = getdate(mktime(0,0,0,$referenceDay['mon']+1,0,$referenceDay['year']));
			$today    = getdate();
		    
			
			// Create a table with the necessary header informations
			$output .= '<div class="monthdiv"><div class="month">';
			$output .= '<div class="heading">'.$referenceDay['month']." - ".$referenceDay['year']."</div>";
			$output .= '<div class="days"><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div></div>';
			
			
			// Display the first calendar row with correct positioning
			$output .= '<div class="row">';
			if ($firstDay['wday'] == 0) $firstDay['wday'] = 7;
			for($i=1;$i<$firstDay['wday'];$i++){
				$output .= '<div>&nbsp;</div>';
			}
			$actday = 0;
			for($i=$firstDay['wday'];$i<=7;$i++){
				$actday++;
				if (($actday == $today['mday']) && ($today['mon'] == $month) && ($today['year'] == $year)) {
					$class = ' class="actday"';
				} else {
					$class = ' class="'.$this->isBooked($year, $month, $actday).'"';
				}
				$output .= "<div$class>$actday</div>";
			}
			$output .= '</div>';
			
			//Get how many complete weeks are in the actual month
			$fullWeeks = floor(($lastDay['mday']-$actday)/7);
			
			for ($i=0;$i<$fullWeeks;$i++){
				$output .= '<div class="row">';
				for ($j=0;$j<7;$j++){
					$actday++;
		    		if (($actday == $today['mday']) && ($today['mon'] == $month) && ($today['year'] == $year)) {
						$class = ' class="actday"';
					} else {
						$class = ' class="'.$this->isBooked($year, $month, $actday).'"';
					}
					
					$output .= "<div$class>$actday</div>";
				}
				$output .= '</div>';
			}
			
			//Now display the rest of the month
			if ($actday < $lastDay['mday']){
				$output .= '<div class="row">';
				
				for ($i=0; $i<7;$i++){
					$actday++;
		    		if (($actday == $today['mday']) && ($today['mon'] == $month) && ($today['year'] == $year)) {
						$class = ' class="actday"';
					} else {
						$class = ' class="'.$this->isBooked($year, $month, $actday).'"';
					}
					
					if ($actday <= $lastDay['mday']){
						$output .= "<div$class>$actday</div>";
					}
					else {
						$output .= '<div>&nbsp;</div>';
					}
				}
				
				$output .= '</div>';
			}
			
			$output .= '</div></div>';	
			
			if($month == 12) {
				$month = 1;
				$year++;	
			}
			else {
				$month++;
			}
		}
		return $output;
	}
	
	
	
	public function isBooked($year, $month, $day) {	
		
		$actday = $year.'-'.$month.'-'.$day;
		
		$AllDates = $this->BookedData();
		
		$booked = DB::query("SELECT `BookedData`.`ID` FROM `BookedData` WHERE '{$actday}' BETWEEN `BookedData`.`dateStart` AND `BookedData`.`dateEnd`")->value();
		if($booked){
			
			if(DB::query("SELECT `BookedData`.`dateStart` FROM `BookedData` WHERE '{$actday}' = `BookedData`.`dateStart`")->value() && DB::query("SELECT `BookedData`.`dateEnd` FROM `BookedData` WHERE '{$actday}' = `BookedData`.`dateEnd`")->value()){ // Arrival and Departure date the same check
				return "DepartureArrival";
			}
			elseif(DB::query("SELECT `BookedData`.`dateStart` FROM `BookedData` WHERE '{$actday}' = `BookedData`.`dateStart`")->value()){ // Arrival date check
				return "Arrival";
			}
			elseif(DB::query("SELECT `BookedData`.`dateEnd` FROM `BookedData` WHERE '{$actday}' = `BookedData`.`dateEnd`")->value()){ // Departure date check
				return "Departure";
			} 
			else {
				return "booked";
			}	
		} else {
			return "available";	
		}
	}
}
