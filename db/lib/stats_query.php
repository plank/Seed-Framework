<?php

class StatsQuery {

	/**
	 * Connection to the db on which to execute queries
	 *
	 * @var DB
	 */
	var $db;
	
	var $scale = 'day';
	var $date_format = "%Y-%m-%d";	
	var $default_offset = '-1 week';

	/**
	 * The last query executed
	 *
	 * @var string
	 */
	var $last_query = '';
	
	var $from = null;
	var $to = null;
	
	function StatsQuery($db) {
		$this->db = $db;
	}
	
	function set_to_daily() {
		$this->scale = 'day';
		$this->default_offset = '-1 week';
		$this->date_format = "%Y-%m-%d";
	}
	
	function set_to_monthly() {
		$this->scale = 'month';
		$this->default_offset = '-1 year';
		$this->date_format = "%Y-%m";	
	}
	
	function set_date_range($from = null, $to = null) {
		$this->from = $from;
		$this->to = $to;			
	}
	
	function _fix_dates() {
		// default to current date
		$this->to = is_null($this->to) ? time() : strtotime($this->to);
		$this->from = is_null($this->from) ? strtotime($this->default_offset, $this->to) : strtotime($this->from);		
		
		// swap dates if needed
		if ($this->to < $this->from) {
			$tmp = $this->to; $this->to = $this->from; $this->from = $tmp;
		}
		
	}
	
	function query($table, $select, $conditions = null, $daily = true) {
		// date format
		if ($daily) {
			$this->set_to_daily();
		} else {
			$this->set_to_monthly();
		}

		if (is_null($conditions)) { $conditions = '1 = 1'; }
		
		$this->_fix_dates();
		
		$date_part = "DATE_FORMAT( created_at, '$this->date_format' )";
		
		// select part
		$sql = "SELECT $date_part AS date, $select, count(*) AS total FROM `$table` WHERE $conditions AND ";
		
		// date range
		$sql .= $date_part." >= '".strftime($this->date_format, $this->from)."' AND ".$date_part." <= '".strftime($this->date_format, $this->to)."' ";
		
		// group and order by
		$sql .= "GROUP BY $date_part ORDER BY $date_part";
		
		$this->last_query = $sql;
		
		// fetch and reorder results
		$result = $this->db->query_array($sql);		
		
		return $this->_order_results($result);
	}

	/**
	 * Orders a query result array into an array keyed by date
	 *
	 * @param array $result
	 * @return array
	 */
	function _order_results($result) {
		
		$final = array();
		
		if (!$result) return $final;
		
		foreach ($result as $item) {
			$final[$item['date']] = $item;
			
		}
		
		return $final;				
	}
	
}

?>