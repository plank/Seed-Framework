<?php

class DateTester extends UnitTestCase {

	/**
	 * Test the different constructor parameters
	 */
	function test_creation() {
		$date_as_string = '1975/06/30 12:00:00';
		$date_as_timestamp = strtotime($date_as_string);
		
		$date = new Date($date_as_string);
		$this->assertEqual($date->date, $date_as_timestamp);
		
		$date = new Date($date_as_timestamp);
		$this->assertEqual($date->date, $date_as_timestamp);
		
		$date = new Date(1975, 06, 30, 12, 0, 0);
		$this->assertEqual($date->date, $date_as_timestamp);		
		
	}

	/**
	 * Test the properties of a date object
	 */
	function test_properties() {
		$date_as_string = '1975/06/30 12:34:56';
		
		$date = new Date($date_as_string);
		
		$this->assertEqual($date->get_year(), 1975);
		$this->assertEqual($date->get_month(), 6);
		$this->assertEqual($date->get_date(), 30);
		$this->assertEqual($date->get_day(), 1);
		
		$this->assertEqual($date->get_hours(), 12);
		$this->assertEqual($date->get_minutes(), 34);
		$this->assertEqual($date->get_seconds(), 56);

		$this->assertEqual($date->get_month_name(), 'June');
		
	}
	
	/**
	 * Test some of the static methods of the date class
	 */
	function test_static_methods() {
		$this->assertEqual(Date::day_name(1, 0), 'Monday');
		$this->assertEqual(Date::days_in_month(1975, 06), 30);
	}
	
	/**
	 * If the object is passed a date string of zeros, it should default to the
	 * current date
	 */
	function test_blank_sql_date() {
		$value = intval('0000-00-00 00:00:00');
		$date = new Date($value);
		$current = time();

		$this->assertEqual($date->date, $current);
		
	}
	
	function test_time_between() {
		$old_date = new Date('1980/01/01 12:00:00');	
		
		$new_date = new Date('1990/01/01 12:00:00');
		
//		$this->dump($old_date->get_seconds_between($new_date));
		
//		$this->dump($old_date->get_days_between($new_date));
	}
	
}


?>