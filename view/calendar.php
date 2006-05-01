<?php 
/**
 * calendar.php, part of the seed framework
 *
 * @author Mateo Murphy
 * @copyright Mateo Murphy
 * @package view
 */

// Constants for the various days of the week
define('MONDAY', '1');
define('TUESDAY', '2');
define('WEDNESDAY', '3');
define('THURSDAY', '4');
define('FRIDAY', '5');
define('SATURDAY', '6');
define('SUNDAY', '7');

/**
 * A class for displaying event calendars
 * @author Mateo Murphy
 * @copyright Mateo Murphy
 * @package view
 * @subpackage calendar
 */
class EventCalendar {
	/**
	 * @var int
	 */
	var $month;
	
	/**
	 * @var int
	 */
	var $year;
	
	/**
	 * @var array
	 */
	var $events;
	
	/**
	 * The day of the week to start the calendar on.
	 *
	 * @var int
	 */
	var $week_start = SUNDAY;
	
	/**
	 * The classname for the table.
	 * 
	 * @var string;
	 */
	var $css_table_class = 'calendar';
	
	/**
	 * The value of the cellspacing attribute of the table.
	 * 
	 * @var int
	 */
	var $cell_spacing = 0;
	
	/**
	 * The link to use for the monthname. If empty, no link will appear.
	 * 
	 * @var string
	 */
	var $month_name_link = '';

	/**
	 * The link to use for the previous month button. If empty, there will be
	 * no previous month button.
	 * 
	 * @var string
	 */
	var $prev_month_link = '';

	/**
	 * The link to use for the next month button. If empty, no next month
	 * button will appear.
	 * 
	 * @var string
	 */
	var $next_month_link = '';
	
	/**
	 * Set this to false to skip showing the day name row
	 *
	 * @var bool
	 */
	var $show_day_names = true;
	
	/**
	 * Set this to false to skip showing the header row (month name)
	 *
	 * @var unknown_type
	 */
	var $show_header = true;
	
	/**
	 * Constructor
	 */
	function EventCalendar($date = null) {
		list($this->year, $this->month) = $this->split_date($date);
		
		$this->events = array();
	}
	
	function next_month_time_stamp() {
		return $this->time_stamp(+1);
	}
	
	function time_stamp($month_offset = 0) {
		return mktime(12, 0, 0, $this->month + $month_offset, 1, $this->year);	
	}
	
	function previous_month_time_stamp() {
		return $this->time_stamp(-1);
	}
	
	/**
	 * Adds an event to a given date
	 */
	function add_event($date, $title = null, $link = null, $category = null) {
		$event = new CalendarEvent($date, $title, $link, $category);
		
		$this->append_event($event);
		
		return $event;
	}
	
	/**
	 * Returns the events for a given date
	 *
	 * @param mixed $date Either a full date, or a year, if the month and day are given
	 * @param int $month
	 * @param int $day
	 * @return array
	 */
	function get_events($date, $month = null, $day = null) {
		if (is_null($month) && is_null($day)) {
			list ($date, $month, $day) = $this->split_date($date);	
		}
		
//		print "$date, $month, $day";
		
		if (isset($this->events[$date][$month][$day])) {
			return $this->events[$date][$month][$day];
			
		} else {
			return null;
			
		}
	}
	
	/**
	 * Appends a given CalendarEvent object to the collection of events
	 *
	 * @param CalendarEvent $event
	 */
	function append_event($event) {
		$this->events[$event->year][$event->month][$event->day][] = $event;
		
	}
	
	/**
	 * Generate a table row with the name of the month
	 *
	 * @return string
	 */
	function generate_header() {
		$return = "<th colspan='7' class='header'>";
		
		if ($this->prev_month_link) {
			$return .= "<a href='$this->prev_month_link'>&laquo;</a>";
		}
	
		if ($this->month_name_link) {
			$return .= "<a href='$this->month_name_link'>";
		}
		
		$return .= " ".$this->month_name()." ".$this->year." ";

		if ($this->month_name_link) {
			$return .= "</a>";	
		}
		
		if ($this->next_month_link) {
			$return .= "<a href='$this->next_month_link'>&raquo;</a>";
		}
		
		$return .= "</th>";
		
		return $return;
	}

	/**
	 * Generate a table row for the days of the week
	 *
	 * @param int $week_day the number of the week to display
	 * @return string
	 */
	function generate_week_day($week_day) {
		return "<th>".$this->day_name($week_day, true)."</th>";
	}
	
	/**
	 * Generate a cell for a given weekday
	 *
	 * @param int $day The day to display, 0 for blank calendar days
	 * @param array $events An array of CalendarEvents for a given day
	 * @param bool $today True if the current date is today's date
	 */
	function generate_day($day = 0, $events = null, $today = false) {
				
		$class = '';
		
		if (isset($events)) {	// for days that have events
			// link to the first event
			$event = current($events);
			$value .= "<a href='".$event->link."'>$day</a>";
		
		} elseif ($day > 0) {	// for blank days	
			$value .= "<span>$day</span>";	
		
		} else {				// for regular days
			$class = ' class="empty"';
			$value .= '&nbsp;';

		}
		
		if ($today) {
			$class = ' class="today"';
		}
		
		$return .= "<td$class>$value</td>";
		
		return $return;		
	}
	
	/**
	 * Generate a calendar for the given month
	 *
	 * @return string
	 */
	function generate() {
		// Get the day of the week the calendar is starting on, as a number between 0 and 6
		$week_start = $this->weekStart % 7;
		
		$return = "<table class='$this->css_table_class' cellspacing='$this->cell_spacing' cellpadding='0'>\n";
		
		// Legend row
		if ($this->show_header) {
			$return .= "<tr>";		
			$return .= $this->generate_header();
			$return .= "</tr>\n";
		}
		
		// Weekday names row
		if ($this->show_day_names) {
			$return .= "<tr>";
			for($week_day = $week_start; $week_day <= $week_start + 6; $week_day ++) {
				$return .= $this->generate_week_day($week_day);
			}
			$return .= "</tr>\n";
		}

		// Calendar
		$day = 1 - $week_start - $this->first_of_month();
	
		if ($day <= -6) {
			$day += 7;
		}
		
		$days_in_month = $this->first_of_month();
		
		while($day <= days_in_month) {
			$return .= "<tr>";
			
			for($week_day = $week_start; $week_day <= $days_in_month + 6; $week_day ++) {
				
				$events = $this->get_events($this->year, $this->month, $day);
				
				$today = (date('Ymj') == $this->year.$this->month.$day);
												
				if ($day > 0 && $day <= $days_in_month) {		
					$return .= $this->generate_day($day, $events, $today);
				} else {
					$return .= $this->generate_day();
				}
				
				$day ++;
			}
			
			$return .= "</tr>\n";
		}
		
		$return .= "</table>\n";
		
		return $return;
	}
	
	/**
	 * Returns a given date as a numerical array containing the year, month, and day
	 *
	 * @param mixed $date Either a timestamp or a string representation of a date.
	 * if null, uses the current date
	 * @return array
	 */
	function split_date($date = null) {
		if (is_null($date)) {
			$date = time();
			
		} elseif (!is_int($date)) {
			$date = strtotime($date);
			
		}
		
		return explode('-', date('Y-m-j', $date));
		
	}
	
	/**
	 * Returns the name of a given month for the current locale
	 *
	 * @param int $month
	 * @param int $abbreviated 0 = full name, 1 = 3 letter name, 2 = 1 letter name
	 * @return string
	 */
	function month_name($month = null, $abbreviated = false) {
		if (is_null($month)) {
			$month = $this->month;	
		}
		
		$format = $abbreviated ? '%b' : '%B';
				
		$val = strftime($format, mktime(12, 0, 0, $month, 1, 2000));
		
		if ($abbreviated == 2) {
			return substr($val, 0, 1);
		} else {
			return $val;
		}

	}
	
	/**
	 * Returns the name of a given day for the current locale
	 *
	 * @param int $day
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
	 * Returns the number of the weekday for the first of a given month
	 *
	 * @param int $year
	 * @param int $month
	 */
	function first_of_month($year = null, $month = null) {
		if (is_null($year)) {
			$year = $this->year;
		}
		
		if (is_null($month)) {
			$month = $this->month;
		}
		
		return strftime('%u', mktime(12, 0, 0, $month, 1, $year));
		
	}

	/**
	 * Returns the number of days in a given month
	 *
	 * @param int $year
	 * @param int $month
	 */
	function days_in_month($year = null, $month = null) {
		if (is_null($year)) {
			$year = $this->year;
		}
		
		if (is_null($month)) {
			$month = $this->month;
		}

		return strftime('%d', mktime(12, 0, 0, $month + 1, 0, $year)); 
		
	}
	
}

class MultiEventCalendar extends EventCalendar {
	/**
	 * Generate a cell for a given weekday
	 *
	 * @param int $day The day to display, 0 for blank calendar days
	 * @param array $events An array of CalendarEvents for a given day
	 * @param bool $today True if the current date is today's date
	 */
	function generate_day($day = 0, $events = null, $today = false) {
				
		$class = '';
		
		if (isset($events)) {	// for days that have events
			$value .= "<span>$day<div>";	
					
			foreach($events as $event) {
				$value .= "<a href='#' title='$event->title'>&bull;</a>";
			}
			
			$value .= "</div></span>";
		
		} elseif ($day > 0) {	// for blank days	
			$value .= "<span>$day</span>";	
		
		} else {				// for regular days
			$class = ' class="empty"';
			$value .= '&nbsp;';

		}
		
		if ($today) {
			$class = ' class="today"';
		}
		
		$return .= "<td$class>$value</td>";
		
		return $return;		
	}	
	
	
}

class CalendarEvent {
	var $day;
	var $month;
	var $year;
	
	var $title;
	var $link;
	var $category;
	
	/**
	 * Constructor
	 */
	function CalendarEvent($date = null, $title = null, $link = null, $category = null) {
		list($this->year, $this->month, $this->day) = EventCalendar::split_date($date);
		
		$this->title = $title;
		$this->link = $link;
		$this->category = $category;
		
	}
	
	
	
}



?>