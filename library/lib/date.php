<?php

/**
 * date.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */

/**
 * Utility class for working with dates.
 *
 * Loosely based on the javascript Date object
 *
 * @package library
 *
 */

class Date {

	/**
	 * The current date as a timestamp
	 *
	 * @var int
	 */
	var $date;
	
	/**
	 * Constructor
	 *
	 * There are four ways to construct a date object:
	 * 1) Leave $date null, which sets the object to the current date
	 * 2) Set $date to a string representation of a date
	 * 3) Set $date to a timestamp
	 * 4) Use all the arguments to make the date; this method is used when the month param isn't null
	 *
	 * @param mixed $date
	 * @param int $month
	 * @param int $day
	 * @param int $hour
	 * @param int $minute
	 * @param int $second
	 * @return Date
	 */
	function Date($date = null, $month = null, $day = null, $hour = null, $minute = null, $second = null) {
		
		if (is_null($date) || intval($date) == 0) {
			$this->date = time();
			
		} elseif (isset($month)) {
			$this->date = mktime($hour, $minute, $second, $month, $day, $date);
			
		} elseif (is_numeric($date)) {
			$this->date = $date;
			
		} else {
			$this->date = strtotime($date);
			
		}
	}

	/**
	 * Returns the timestamp of the current date
	 *
	 * @return int
	 */
	function get_time() {
		return $this->date;
	}
	
	/**
	 * Returns the year as a four digit number
	 *
	 * @return int
	 */
	function get_year() {
		return date('Y', $this->date);
	}
	
	/**
	 * Returns the month as a value from 01 to 12 
	 *
	 * @return string
	 */
	function get_month() {
		return date('m', $this->date);
	}
	
	/**
	 * Returns the day of the month
	 *
	 * @return int
	 */
	function get_date() {
		return date('d', $this->date);		
	}
	
	/**
	 * Returns the day of the week as a value from 0 to 6,
	 * with 0 being sunday.
	 *
	 * @return int
	 */
	function get_day() {
		return date('w', $this->date);		
	}
	
	/**
	 * Returns the current hours
	 *
	 * @return int
	 */
	function get_hours() {
		return date('h', $this->date);		
	}
	
	/**
	 * Returns the current minutes as a value from 00 to 59
	 *
	 * @return string
	 */
	function get_minutes() {
		return date('i', $this->date);		
	}
	
	/**
	 * Returns the current seconds as a value from 00 to 59,
	 * although it may return 60 or 61 as leapseconds.
	 *
	 * @return string
	 */
	function get_seconds() {
		return date('s', $this->date);		
	}
	
	/**
	 * Returns the name of the current month
	 *
	 * @param int $abbreviated 0 = full name, 1 = 3 letter name, 2 = 1 letter name
	 * @return string
	 */
	function get_month_name($abbreviated = false) {
		return Date::month_name($this->get_month(), $abbreviated);
		
	}
	
	/**
	 * Returns the weekday of the first day of the current month
	 *
	 * @return int
	 */
	function get_first_of_month() {
		return Date::first_of_month($this->get_year(), $this->get_month());
	}
	
	/**
	 * Returns the number of days in the current month
	 *
	 * @return int
	 */
	function get_days_in_month() {
		return Date::days_in_month($this->get_year(), $this->get_month());	
	}
	
	
	/**
	 * Returns the name of a given day for the current locale
	 *
	 * @param int $day 0 = sunday to 6 = saturday
	 * @param int $abbreviated 0 = full name, 1 = 3 letter name, 2 = 1 letter name
	 * @return string
	 */
	function day_name($day, $abbreviated = 0) {
		$format = $abbreviated ? '%a' : '%A';
		
		$val = strftime($format, mktime(12, 0, 0, 8, $day, 2005));
		
		if ($abbreviated == 2) {
			return substr($val, 0, 1);
		} else {
			return $val;
		}
	}
	
	/**
	 * Returns the name of a given month for the current locale
	 *
	 * @static 
	 * @param int $month
	 * @param int $abbreviated 0 = full name, 1 = 3 letter name, 2 = 1 letter name
	 * @return string
	 */
	function month_name($month, $abbreviated = false) {
		$format = $abbreviated ? '%b' : '%B';
				
		$val = strftime($format, mktime(12, 0, 0, $month, 1, 2000));
		
		if ($abbreviated == 2) {
			return substr($val, 0, 1);
		} else {
			return $val;
		}

	}
	
	/**
	 * Returns the number of the weekday for the first of a given month
	 *
	 * @static 
	 * @param int $year
	 * @param int $month
	 * @return int
	 */
	function first_of_month($year, $month) {
		return strftime('%u', mktime(12, 0, 0, $month, 1, $year));
		
	}

	/**
	 * Returns the number of days in a given month
	 *
	 * @static 
	 * @param int $year
	 * @param int $month
	 * @return int
	 */
	function days_in_month($year, $month) {
		return strftime('%d', mktime(12, 0, 0, $month + 1, 0, $year)); 
		
	}
	
	/**
	 * Returns the names of all the months as an array
	 *
	 * @static 
	 * @param bool $abbreviated
	 * @return array
	 */
	function month_names($abbreviated = false) {
		$return = array();
		
		for ($x = 1; $x <= 12; $x ++) {
			$return["$x"] = Date::month_name($x, $abbreviated);
		}
		
		return $return;
		
	}
	
	/**
	 * Returns an array containing all the days in a month
	 *
	 * @todo function currently unfinished
	 * @static 
	 * @return array
	 */
	function days_array($year, $month) {
		$return = array();
		$max = Date::days_in_month($year, $month);
		
		for ($x = 1; $x <= $max; $x ++) {
			$return[$x] = $x;
		}
		
		return $return;
		
	}
	
}

?>