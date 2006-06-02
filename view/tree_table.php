<?php

/**
 * part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package view
 * @subpackage html
 */

require_once('table');

/**
 * Class for displaying nested set models in a table
 *
 * @package view
 * @subpackage html
 */
class TreeTable extends Table {
	
	var $level_end_actions = array();
	
	var $hide_root = false;
	
	var $sortable = false;
	
	/**
	 * Generates the table
	 *
	 * @return string
	 */
	function generate()	{
		$return =  "<table class='$this->class_name' id='$this->id' cellspacing='0'>\n";
		
		$return .= $this->generate_colgroup();
		
		$return .= $this->generate_header();
		
		$row_number = 1;
		
		$level = 0;
		$ends = array();
		
		while (false !== ($model = $this->result->next())) { 
			$last_row = !$this->result->has_next();
			
			while($level >= $model->get('level')) {
				$return .= array_pop($ends);
				$level --;	
				
			}
			
			$level = $model->get('level');
			
			if (!$model->is_root() || !$this->hide_root) {
				$return .= $this->generate_row($model, $row_number ++, $last_row);
			}
			
			array_push($ends, $this->generate_level_end($model, $row_number, $last_row));
		}		
	
		$return .= implode('', array_reverse($ends));
		
		$return .= "</table>";
		
		return $return;
	}
	
	
	/**
	 * Generates a data row for the table
	 *
	 * @param model $row
	 * @param int $row_number
	 * @return string
	 */
	function generate_row($row, $row_number, $last_row = false) {
		if ($this->status_field && $row->is_set($this->status_field)) {
			$classname = $this->status_field.'_'.Inflector::underscore($row->get($this->status_field));
			$return = "<tr class='$classname'>";
			
		} else {
			$return = "<tr>";
			
		}
	
		$link_options['id'] = $row->get_id();
		
		$indent = $this->generate_indent($row->get('level'));
		
		foreach($this->columns as $field => $column) {
			//$column->link_array = $link_array;			
			$return .= "<td>".$indent.$column->generate($row->get($field), $row->get_id(), $row_number, $last_row)."</td>";
			$indent = '';
		}
		
		// add row actions
		
		$return .= $this->generate_row_actions($row);

		$return .= "</tr>\n";
		
		return $return;		
	}	
	
	
	
	/**
	 * @param Model $row
	 */
	
	function generate_level_end($row, $row_number, $last_row = false) {
		if (!count($this->level_end_actions)) {
			return false;	
		}
		
		$indent = $this->generate_indent($row->get('level') + 1);
		
		$return = '<tr>';
		$return .= "<td colspan='".count($this->columns)."'>{$indent}";
		
		if (count($this->level_end_actions) > 0) {
			foreach ($this->level_end_actions as $display => $options) {
				$return .= "<a href='".$this->generate_link($options, $row->get_id())."'>$display</a> ";				
				
//				die(debug($return));
			}
		}
		
		$return .= "</td>";
		
		foreach ($this->row_actions as $row_action) {
			$return .= "<td>&nbsp;</td>";
		}
		
		$return .= "</tr>\n";
		
		return $return;
		
	}
	
	function generate_indent($level) {
		return str_repeat('&nbsp;', ($level - intval($this->hide_root)) * 5);
	}
	
}

?>