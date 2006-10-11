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

/**
 * Class for generating HTML tables of lists from a database
 *
 * @package view
 * @subpackage html
 */
class Table {
	
	/**
	 * The controller containing the table
	 *
	 * @var Controller
	 */
	var $controller;
	
	/**
	 * The html id for the table
	 *
	 * @var string
	 */
	var $id;
	
	/**
	 * The html class name for the table
	 * 
	 * @var string
	 */
	var $class_name = 'list_table';
	
	/**
	 * A page to post to
	 *
	 * @var string
	 */
	var $action;
	
	/**
	 * An iterator.
	 *
	 * @var SeedIterator
	 */
	var $result;
	
	/**
	 * Array of columns to display. Keys are the field names, values are the titles to display.
	 *
	 * @var array
	 */
	var $columns;
	
	/**
	 * Array of actions to display for each row. Keys are the action names, values are the titles to display.
	 *
	 * @var array
	 */
	var $row_actions;
	
	/**
	 * Array of buttons to display at top and bottom of table
	 *
	 * @var array
	 */
	var $buttons;
	
	/**
	 * The field that uniquely identifies each row
	 *
	 * @var string
	 */
	var $id_field = 'id';
	
	/**
	 * Set to true to make the table sortable by clicking on the column names
	 *
	 * @var bool
	 */
	var $sortable = true;
	
	/**
	 * The field the display is currently sorted by.
	 *
	 * @var string
	 */
	var $sort_field;
	
	/**
	 * The current sort direction. Should be either ASC or DESC.
	 *
	 * @var string
	 */
	var $sort_dir;
	
	/**
	 * The field in the table to use as a status field. The name of the field and it's value
	 * are combined into a class name and applied to each row
	 *
	 * @var string
	 */
	var $status_field;
	
	/**
	 * Constructor
	 *
	 * @param SeedIterator $iterator
	 * @param Controller $controller
	 * @return Table
	 */
	function Table($iterator, $controller = null) {
		if (!is_a($iterator, 'SeedIterator')) {
			trigger_error("Parameter 1 is not an Iterator", E_USER_WARNING);
			return false;
		}

		if (!is_null($controller) && !is_a($controller, 'Controller')) {
			trigger_error("Parameter 2 is not a Controller", E_USER_WARNING);
			return false;
		}
		
		
		$this->result = $iterator;
	
		$this->columns = array();
		$this->row_actions = array();
		$this->buttons = array();
		
		if (isset($controller)) {
			$this->controller = $controller;
			$this->sort_field = assign($controller->params['sortby']);
			$this->sort_dir = assign($controller->params['sortdir']);
		}
		
		if (!$this->id) {
			$this->id = Inflector::underscore(get_class($this));
		}
		
		$this->setup();
		
	}
	
	function & factory($type, $iterator, $controller = null) {
		$class_name = Inflector::camelize($type).'Table';
		$object = false;		
		
		if (class_exists($class_name)) {
			$object = & new $class_name($iterator, $controller);
		}	
		
		return $object;
			
	}

	/**
	 * default setup
	 */
	function setup() {
		$this->add_column('text', 'title', 'Title');
		$this->row_actions = array('Edit'=>'edit', 'Delete'=>'delete');

	}
	
	function row_actions($param) {
		$this->row_actions = func_get_args();	
	}
	
	function add_row_action($label, $target_options, $link_actions = null, $only = null, $except = null) {
		if (is_null($link_actions)) {
			$link_actions = array();	
		}
		
		$this->row_actions[$label] = array($target_options, $link_actions, $only, $except);	
		
	}
	
	function add_column($type = 'text', $name = null, $label = null, $params = null, $options = null) {
		if (is_null($name)) {
			$name = $type;
			$type = 'text';
		}
		
		$column = TableColumn::factory($type, $name);

		if (!$column) {
			trigger_error("No control found for '$type'", E_USER_WARNING);
			return false;
		}
		
		if (isset($label)) {
			$column->label = $label;
		} else {
			$column->label = Inflector::humanize($name);	
		}
		
		if (isset($params['sort_field'])) {
			$column->sort_field = $params['sort_field'];
			unset($params['sort_field']);	
		}
		
		$column->params = $params;
		$column->options = $options;

		$this->append_column($column);
		
		return $column;
	}
	
	/**
	 * Adds an existing form object to the collection
	 *
	 * @param TableColumn $control
	 */
	function append_column(& $column) {
		$column->table = & $this;
		$this->columns[$column->name] = $column;
	}	
	
	/**
	 * Adds a button with the given name and value to the form
	 *
	 * @param string $name
	 * @param string $value
	 */
	function add_button($name, $value) {
		$button = FormControl::factory('submit');
		
		$button->name = $name;
		$button->value = $value;
		
		$this->append_button($button);
		
		return $button;	
	}	
	
	function append_button(& $button) {
		$this->buttons[] = $button;	
		
	}	
	
	/**
	 * Generate the colgroup tags for the table
	 *
	 * @return string
	 */
	function generate_colgroup() {
		$return = "<colgroup>";
		
		foreach ($this->columns as $field => $display) {
			$return .= "<col id='col_$field' />";
			
		}
		
		$return .= "</colgroup>\n";
		
		if (count($this->row_actions) > 0) {
			$return .= "<colgroup id='col_actions'>";
			
			foreach($this->row_actions as $action => $display) {
				$return .= "<col id='col_".Inflector::linkify($action)."' />";
			}
			
			$return .= "</colgroup>";
			
		}		
		
		return $return;
	}
	
	/**
	 * Generates the header row of the table
	 *
	 * @return string
	 */
	function generate_header() {
		$return = "<tr>";
		
		// $link_array = $this->link_array;
		$link_options = array();
		
		foreach($this->columns as $field => $data) {
			
			$return .= "<th id='th_$field'>";
	
			$link_options['sortby'] = $data->sort_field;
			
			$display = $data->label;
			
			if ($this->sortable && $display) {
				// if this is the currently sorted field, assign the class
				if ($this->sort_field == $data->sort_field) {
					if ($this->sort_dir == 'ASC') {
						$class = " class='sort_asc'";
						$link_options['sortdir'] = 'DESC';
					} else {
						$class = " class='sort_desc'";
						$link_options['sortdir'] = 'ASC';
					
					}
				} else {
					$link_options['sortdir'] = 'ASC';
					$class = '';
				}
				
				$return .= "<a href='".$this->escape($this->controller->url_for(null, $link_options))."' $class>$display</a>";
				
			} elseif (!$display) {
				$return .= "&nbsp;";
				
			} else {
				$return .= $display;
				
			}
		
			$return .= "</th>";
			
		}
		
		if (count($this->row_actions) == 1) {
			$return .= "<th id='th_actions'>Action</th>";			
		} else if (count($this->row_actions) > 1) {
			$return .= "<th id='th_actions' colspan='".count($this->row_actions)."'>Actions</th>";			
		}
		
		$return .= "</tr>\n";
		
		return $return;		
		
	}
	
	function generate_link($options, $row_id) {
		if (!is_array($options)) {
			return sprintf($options, $row_id);
			
			// $options = array('action' => $options);
		}
		
		$options['id'] = $row_id;
		
		return $this->controller->url_for($options);
	} 
	
	/**
	 * Generates a data row for the table
	 *
	 * @param array $row
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
	
		if (is_array($row)) {
			$id = $row[$this->id_field];
		} else {
			$id = $row->get_id();
		}
		
		$link_options['id'] = $id;
		
		foreach($this->columns as $field => $column) {
			//$column->link_array = $link_array;

			if (is_array($row)) {
				$value = $row[$field];	
			} else {
				$value = $row->get($field);
			}
			
			if (is_a($value, 'Model')) {
				$value = $value->to_string();
				
/*				if (!$value) {
					$value = '(Object)';	
				} */
				
			}
				
			$return .= "<td class='$field'>".$column->generate($value, $id, $row_number, $last_row)."</td>";
		
		}
		
		$return .= $this->generate_row_actions($row);		
		
		$return .= "</tr>\n";
		
		return $return;		
	}
	
	function generate_row_actions($row) {
		if (is_array($row)) {
			$id = $row[$this->id_field];	
		} else {
			$id = $row->get_id();	
		}
		
		// add row actions
		if (!count($this->row_actions)) {
			return '';
			
		}
	
		$return = '';
		
		foreach ($this->row_actions as $display => $options) {
			list($target_options, $link_options, $only, $except) = $options;
			
			if (isset($link_options['confirm'])) {
				$javascript = " onclick=\"return confirm('".$link_options['confirm']."')\"";
				
			} else {
				$javascript = '';
				
			}
			
			$return .= "<td class='action_".Inflector::linkify($display)."'>";
			
			$skip = false;
			
			if (isset($only)) {
				$skip = true;
				
				foreach ($only as $field => $value) {
					if ($row->get($field) == $value) {
						$skip = false;	
					}
				}
			}
			
			if (isset($except)) {
				foreach ($except as $field => $value) {
					if ($row->get($field) == $value) {
						$skip = true;	
					}
				}					
			}
			
			if ($skip) {
				$return .= "&nbsp;";	
			} else {
				$return .= "<a href='".$this->generate_link($target_options, $id)."'$javascript>$display</a>";
			}
			
			$return .= "</td>";
		}
	
		return $return;
	}
	
	function generate_buttons() {
		if (!count($this->buttons)) {
			return false;
		}

		$return = array();
		
		foreach($this->buttons as $button) {
			$return[] = $button->generate();
		}
		
		return "<div class='table_buttons'>".implode("&nbsp;", $return)."</div>\n";
		
		
	}
	
	/**
	 * Generates the table
	 *
	 * @return string
	 */
	function generate()	{

		$return = '';
		
		if ($this->action) {
			$return .= "<form action='$this->action' method='post' enctype='multipart/form-data'>\n";
		}
		
		$return .= $this->generate_buttons();
		
		$return .=  "<table class='$this->class_name' id='$this->id' cellspacing='0'>\n";
		
		$return .= $this->generate_colgroup();
		
		$return .= $this->generate_header();
		
		$row_number = 1;
		
		while (false !== ($model = $this->result->next())) { 
			$return .= $this->generate_row($model, $row_number ++, !$this->result->has_next());

		}		
	
		$return .= "</table>\n";
		
		$return .= $this->generate_buttons();
		
		if ($this->action) {
			$return .= "</form>\n";	
			
		}

		return $return;
	}
	
	function escape($string) {
		return htmlspecialchars($string); //htmlentities(utf8_decode($string));
	}
	
}

/**
 * Base class for table columns
 *
 * @package view
 * @subpackage html
 */
class TableColumn {

	/**
	 * Reference to the parent table object
	 *
	 * @var Table
	 */
	var $table;
	
	/**
	 * The label to display next to the control.
	 *
	 * @var string
	 */
	var $label;
	
	/**
	 * The unique name of the control.
	 *
	 * @var string
	 */
	var $name;
	
	/**
	 * The value of the control.
	 *
	 * @var string
	 */
	var $value;
	
	/**
	 * Field to sort by, defaults to field name
	 *
	 * @var string
	 */
	var $sort_field;
	
	/**
	 * An array of parameters for the control. These are generally mapped directly to 
	 * form attributes.
	 *
	 * @var array
	 */
	var $params;
	
	/**
	 * An array of options to select from.
	 *
	 * @var array
	 */
	var $options;
	
	function TableColumn($name = '') {
		if ($name) {
			$this->name = $name;
			$this->label = ucfirst($name);
			$this->sort_field = $name;
		}
	}	
	
	/**
	 * Returns a new TableColumn subclass based on the type paramter given. i.e. input will return
	 * an InputTableColumn.
	 *
	 * @param string $type
	 * @return FormControl
	 */
	function factory($type, $name = '') {
		$className = ucfirst(strtolower($type)).'TableColumn';
		
		if (class_exists($className)) {
			return new $className($name);
		} else {
			return false;
		}
		
	}
	
	function generate($value, $row_number, $last_row = false) {
		return $this->escape($value);
	}
	
	function escape($string) {
		return htmlspecialchars($string); //htmlentities(utf8_decode($string));
	}	
}


/**
 * Base class for table columns
 *
 * @package view
 * @subpackage html
 */
class TextTableColumn extends TableColumn {
	function generate($value, $id) {
		if (isset($this->params['max_length'])) {
			if (strlen($value) > $this->params['max_length']) {
				$value = substr($value, 0, $this->params['max_length']).'...';	
			}
					
		}
		
		if (isset($this->params['action_link'])) {
			$link = sprintf($this->params['action_link'], $id);
			$value = "<a href='$link'>$value</a>";
				
		}
		
		return $value;
	}	
	
}


class CheckboxTableColumn extends TableColumn {
	function generate($value, $id) {
		$value = "<input type='checkbox' name='{$this->name}[]' value='$id' />";
		
		return $value;
	}
	
}

class ImageTableColumn extends TableColumn {
	function generate($value, $id) {
		if ($value) {
			$return = "<img src='$value' alt='image $id' />";	
		} else {
			if ($this->params['missing_text']) {
				$return = $this->params['missing_text'];
			} else {
				$return = "&nbsp;";
			}
		}
		
		return $return;
	}	
	
}

/**
 * Base class for table columns
 *
 * @package view
 * @subpackage html
 */
class SelectTableColumn extends TableColumn {
	function generate($value) {
		if (isset($this->options[$value])) {
			$value = $this->options[$value];
		} else {
			$value = '&nbsp;';
		}
		
		return $value;
	}
	
}

/**
 * Base class for table columns
 *
 * @package view
 * @subpackage html
 */
class YesnoTableColumn extends TableColumn {
	function generate($value) {
		if ($value) {
			return "Yes";
		} else {
			return "No";	
		}	
	}	
}


/**
 * Base class for table columns
 *
 * @package view
 * @subpackage html
 */
class DateTableColumn extends TableColumn {
	
	function generate($value) {
		if (!$value) {
			return false;	
		}
		
		$value = strtotime($value);
		
		if (!isset($this->params['format'])) {
			$this->params['format'] = 'Y-m-d';	
		}
		
		return date($this->params['format'], $value);
	}
	
}

/**
 * Base class for table columns
 *
 * @package view
 * @subpackage html
 */
class StatusTableColumn extends TableColumn  {
	function generate($value) {
		trigger_error('Need to be refactored', E_USER_ERROR);
		$on = $this->params['on_image'];
		$off = $this->params['off_image'];
		
		$this->link_array['action'] = 'set_status';
		
		if ($value) {
			$this->link_array['status'] = '0';
			$tooltip = 'currently live, click to make not live';
			$return .= "<a href='".make_link($this->link_array)."' title='$tooltip'><img src='$on' alt='$tooltip' /></a>";
		} else {
			$this->link_array['status'] = '1';
			$tooltip = 'currently not live, click to make live';
			$return .= "<a href='".make_link($this->link_array)."' title='$tooltip'><img src='$off' alt='$tooltip' /></a>";			
		}
		
		return $return;
	}	
}

/**
 * Base class for table columns
 *
 * @package view
 * @subpackage html
 */
class OrderTableColumn extends TableColumn {
	function generate($value, $id, $row_number, $last_row) {
		
		$down_action = $this->params['link_options'];
		$down_action['id'] = $id;
		
		$up_action = $down_action;
		
		$up_action['action'] = 'move_up';
		$down_action['action'] = 'move_down';
		
		$up_tooltip = 'click to move this item up';
		$down_tooltip = 'click to move this item down';		
				
		if (isset($this->params['up_image'])) {
			$up = $this->params['up_image']; 
			$up = "<img src='$up' alt='$up_tooltip' />";
		} else {
			$up = "&uarr;";
		}
		
		if (isset($this->params['down_image'])) {
			$down = $this->params['down_image'];
			$down = "<img src='$down' alt='$tooltip' />";
		} else {
			$down = "&darr;";	
		}
		
		if (isset($this->params['off_image'])) {
			$off = $this->params['off_image'];
			$up_off = $down_off = "<img src='$off' />";
		} else {
			$up_off = "&uarr;";
			$down_off = "&darr;";	
		}

		$up = "<a href='".$this->table->controller->url_for($up_action)."' title='$up_tooltip'>$up</a>";
		$down = "<a href='".$this->table->controller->url_for($down_action)."' title='$down_tooltip'>$down</a>";
		
		if ($last_row && $row_number == 1) {
			// only item
			$return = "$up_off $down_off";
			
		} elseif ($last_row) {
			// last item
			$return = "$up $down_off";
			
		} elseif ($row_number == 1) {
			// first item
			$return = "$up_off $down";
			
		} else {
			// middle item
			$return = "$up $down";
		}
		
		return $return;
	}
}

/**
 * Base class for table columns
 *
 * @package view
 * @subpackage html
 */
class WeightTableColumn extends TableColumn {
	function generate($value) {
		trigger_error('Need to be refactored', E_USER_ERROR);		
		$on = $this->params['on_image'];
		$off = $this->params['off_image'];		
		
		$return = '';

		$this->link_array['action'] = 'set_weight';		
		
		for($x = 0; $x < $this->params['size']; $x ++) {

			$this->link_array['weight'] = $x;
			
			$return .= "<a href='".make_link($this->link_array)."'><img src='";
			
			if ($x <= $value) {
				$return .= $on;
			} else {
				$return .= $off;
			}
			
			$return .= "' /></a>&nbsp;";
			
		}
			
		return $return;
		
	}
	
}

/**
 * Base class for table columns
 *
 * @package view
 * @subpackage html
 */
class LinkTableColumn extends TableColumn {
	function generate($value) {
		if (isset($this->params['link_format'])) {
			$link_format = $this->params['link_format'];
		} else {
			$link_format = '%s';	
		}
		
		if (isset($this->params['display_format'])) {
			$display_format = $this->params['display_format'];
		} else {
			$display_format = '%s';	
		}		
		
		$target = assign($this->params['target'], '');
		
		$value = "<a href='".sprintf($link_format, $value)."'>".sprintf($display_format, $value)."</a>";
		
		return $value;
	}	
	
}

/**
 * Returns a simple paging mechanism with 'previous' and 'next buttons'
 *
 * @param int $current_page The currently displayed page
 * @param int $num_pages The total number of pages
 * @param array $link_array An optional array of query string variables. The page to display wil
 * be added to this
 * @return string
 */ 

function simplePager($current_page, $num_pages, $link_array = null) {
	
	if ($num_pages == 1) {
		return "";
	}

	if (is_null($link_array)) {
		$link_array = array();	
	}
	
	if ($current_page == 1) {
		$return = "<span>&laquo; Previous Page</span>";
	} else {
		$link_array['page'] = $current_page - 1;
		$return = "<a href='".make_link($link_array)."'>&laquo; Previous Page</a>";
	}

	$return .= " | ";

	if ($current_page == $num_pages) {
		$return .= "<span>Next Page &raquo;</span>";
	} else {
		$link_array['page'] = $current_page + 1;
		$return .= "<a href='".make_link($link_array)."'>Next Page &raquo;</a>";
	}

	return $return;

}
?>