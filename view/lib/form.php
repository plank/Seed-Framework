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
 * Class for generating html forms
 *
 * @package view
 * @subpackage html
 */
class Form {
	
	/**
	 * The controller containing the table
	 *
	 * @var Controller
	 */
	var $controller;	
	
	/**
	 * An array of fields to generate as hidden inputs
	 *
	 * @var array
	 */
	var $hidden_fields;

	/**
	 * The array of controls to display
	 *
	 * @var array
	 */
	var $controls;

	/**
	 * The array of buttons to display at the bottom of the form
	 *
	 * @var array
	 */
	var $buttons;

	/**
	 * The data to display
	 *
	 * @var array
	 */
	var $data;
	
	/**
	 * The class name to use for the table
	 *
	 * @var string
	 */
	var $class_name = 'edit_table';
	
	/**
	 * The page called on submit
	 *
	 * @var string
	 */
	var $action = '';
	
	/**
	 * The max upload size for the form. If this is set to 0 or false,
	 * there's no limit
	 *
	 * @var int
	 */
	var $max_file_size = false;
	
	/**
	 * Returns a new Simple subclass based on the type paramter given. 
	 *
	 * @param string $type
	 * @return FormControl
	 */
	function factory($type, $data = null, $controller = null) {
		$class_name = Inflector::camelize($type).'Form';
		
		if (!class_exists($class_name)) {
			trigger_error("No class for type '$type'", E_USER_WARNING);
			return false;
		}
		
		$form = new $class_name($data, $controller); 
		
		if (!is_a($form, __CLASS__)) {
			trigger_error("$class_name doesn't extend ".__CLASS__, E_USER_ERROR);
		}
		
		return $form;
		
	}	
	
	/**
	 * Constructor
	 *
	 * @param array $data
	 * @return Form
	 */
	function Form($data = null, $controller = null) {
		if (isset($data)) {
			$this->data = $data;
		} else {
			$this->data = array();
		}
		
		$this->hidden_fields = array('id');
		$this->controls = array();
		$this->buttons = array();
		$this->controller = $controller;
		$this->setup();
	}
	
	/**
	 * Called by the constructor, this is the place to place add_control calls
	 * to populate the form when subclassing
	 *
	 */
	function setup() {
		
	}
	
	/**
	 * Adds the past field names as hidden fields of the form 
	 *
	 * @param string $field_name,...
	 */
	function hidden_fields($field_name) {
		$this->hidden_fields = func_get_args();	
		
	}
	
	/**
	 * Creates a new control and adds it to the form
	 *
	 * @param string $type
	 * @param string $name
	 * @param string $label
	 * @param array $params
	 * @param array $options
	 * @return FormControl
	 */
	function add_control($type, $name, $label = null, $params = null, $options = null) {
		$control = FormControl::factory($type);
		
		if (!$control) {
			trigger_error("No control found for '$type'", E_USER_WARNING);
			return false;
		}
		
		$control->name = $name;
		
		if (isset($label)) {
			$control->label = $label;
		} else {
			$control->label = Inflector::humanize($name);	
		}
		$control->params = $params;
		$control->options = $options;
		
		$this->append_control($control);
		
		return $control;
	}
	
	/**
	 * Adds an existing form object to the collection
	 *
	 * @param FormControl $control
	 */
	function append_control(& $control) {
		$this->controls[$control->name] = $control;
	}

	/**
	 * Adds a button with the given name and value to the form
	 *
	 * @param string $name
	 * @param string $value
	 */
	function add_button($name, $value, $params = null) {
		$button = FormControl::factory('submit');
		
		$button->name = $name;
		$button->value = $value;
		$button->params = $params;
		
		$this->append_button($button);
		
		return $button;	
	}
	
	function append_button(& $button) {
		$this->buttons[] = $button;	
		
	}
	
	function get_value($field) {
		if (is_a($this->data, 'model')) {
			return $this->data->get($field);
		} elseif (is_array($this->data)) {
			if (key_exists($field, $this->data)) {
				return $this->data[$field];
			} else {
				return null;
			}
		} else {
			return null;
			debug("Data in Form is of an unknown type", $this->data);	
		}
		
	}
	
	/**
	 * Generates the form
	 *
	 * @param array $data
	 * @return string
	 */
	function generate($data = null) {

		if (isset($data)) {
			$this->data = $data;
		}
		
		$return = "<form action='$this->action' method='post' enctype='multipart/form-data'>\n";

		if ($this->max_file_size) {
			$return .= "<input type='hidden' name='MAX_FILE_SIZE' value='".$this->max_file_size."' />";			
			
		}

		foreach ($this->hidden_fields as $field) {
			if (!is_null($data = $this->get_value($field))) {
				$control = new HiddenFormControl($field);
				$return .= $control->generate($data);
			}
		}
		
		$return .= "<table class='$this->class_name'>\n";
			
		foreach ($this->controls as $field => $control) {
			$data = $this->get_value($field);
			$return .= $this->generate_row($control, $data);
			
		}
		
		$return .= $this->generate_buttons();
		
		$return .= "</table>\n";
		$return .= "</form>\n";
		
		return $return;
	}
	
	/**
	 * @param FormControl $control
	 */
	function generate_row($control, $data) {
		$return = "<tr>";
		$return .= "<th><label for='$control->name'>$control->label</label></th>";		
		$return .= "<td>".$control->generate($data)."</td>";
		$return .= "</tr>\n";
		
		return $return;
	}
	
	function generate_buttons() {
		$return = "<tr>";
		$return .= "<th>&nbsp;</th><td>";		
		
		foreach($this->buttons as $button) {
			$return .= $button->generate()."&nbsp;";	
		}
		
		$return .= "</td></tr>\n";		
		
		return $return;
	}
	
	
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 */
class FormControl {
	
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
	 * An array of parameters for the control. These are generally mapped directly to 
	 * the html tag's attributes.
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
	
	function FormControl($name = '') {
		if ($name) {
			$this->name = $name;
			$this->label = ucfirst($name);
		}
	}
	
	/**
	 * Returns a new FormControl subclass based on the type paramter given. i.e. input will return
	 * an InputFormControl.
	 *
	 * @param string $type
	 * @return FormControl
	 */
	function factory($type) {
		$class_name = Inflector::camelize($type).'FormControl';
		
		if (class_exists($class_name)) {
			return new $class_name;
		} else {
			return false;
		}
		
	}
	
	/**
	 * Generates the control
	 *
	 * @param string $value The value to be displayed in the control. This will get overriden by the control's
	 * 'value' parameter if that is set.
	 * @return string
	 */
	function generate($value = null) {
		if (isset($this->params['value'])) {
			$this->value = $this->params['value'];
		
		} elseif (isset($value)) {
			$this->value = $value;
			
		}
		
		return $this->generate_control();

	}


	
	/**
	 * Generates the control part of the control
	 *
	 * @return string
	 */
	function generate_control() {
		return $this->value;
	}
	
	/**
	 * Returns a string of attributes for the control
	 *
	 * @return string
	 */
	function get_attributes() {
		foreach($this->params as $name => $value) {
			$return[] = $name.'="'.htmlentities($value).'"';
		}
		
		return implode(' ', $return);
	}
	
	function escape($string) {
		return utf8_decode($string);
	}
	
}


class DisplayFormControl extends FormControl {
	
	
}

/**
 * Class for hidden form controls
 *
 * @package view
 * @subpackage html
 */
class HiddenFormControl extends FormControl {
	function generate($value = null) {
		if (isset($value)) {
			$this->value = $value;
		}
		
		$this->params['name'] = $this->name;
		$this->params['value'] = $this->value;
		$this->params['type'] = 'hidden';
		
		return "<input ".$this->get_attributes()." />\n";
	}	
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 */
class InputFormControl extends FormControl  {
	function generate_control() {
		if (isset($this->params['prefix'])) {
			$prefix = $this->params['prefix'];
			unset($this->params['prefix']);
		} else {
			$prefix = '';
		}
		
		$this->params['id'] = $this->name;
		$this->params['name'] = $this->name;
		$this->params['value'] = $this->escape($this->value);
		$this->params['type'] = 'text';
		$this->params['class'] = 'text';
		
		return "$prefix<input ".$this->get_attributes()." />";
	}
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 */
class PasswordFormControl extends FormControl  {
	function generate_control() {
		$this->params['id'] = $this->name;
		$this->params['name'] = $this->name;
		$this->params['value'] = $this->escape($this->value);
		$this->params['type'] = 'password';
		$this->params['class'] = 'text';
		
		return "<input ".$this->get_attributes()." />";
	}
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 */
class TextareaFormControl extends FormControl  {
	function generate_control() {
		$this->params['id'] = $this->name;		
		$this->params['name'] = $this->name;
				
		return "<textarea ".$this->get_attributes()." >$this->value</textarea>";
	}
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 */
class SelectFormControl extends FormControl {
	
	function generate_control() {
		$this->params['id'] = $this->name;		
		$this->params['name'] = $this->name;
		
		$return = "<select ".$this->get_attributes().">";
		$return .= make_options($this->options, $this->value, '', true);
		$return .= "</select>";
		
		return $return;
	}
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 */
class FileUploadFormControl extends FormControl {
	function generate_control() {
		$this->params['id'] = $this->name;		
		$this->params['name'] = $this->name;
		$this->params['type'] = 'file';
		$this->params['class'] = 'file';
	
		$return = "<input ".$this->get_attributes()." />";

		return $return;
		
	}	
	
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 */
class FileFormControl extends FormControl {
	
	function generate_control() {
		$this->params['id'] = $this->name;		
		$this->params['name'] = $this->name;
		$this->params['type'] = 'file';
		$this->params['class'] = 'file';
		$link_root = assign($this->params['link_root']);
		$image_root = assign($this->params['image_root']);
		
		$return = '';
		
		if (!$this->value) {
			$this->value = '(none)';
		} else {
			if ($image_root) {
				$return = '<img src="' . $image_root.$this->value . '" alt="preview" />';
			}
			
			if ($link_root) {
				$this->value = '<a href="' . $link_root.$this->value . '" target="_blank">' . $this->value . '</a>'; 
			}
		}
			
		$return .= "<div style='margin-bottom: 4px'><em>Existing file: </em> ".$this->value."</div>";
		$return .= "<div><em>Upload a new file:</em> <input ".$this->get_attributes()." /></div>";

		return $return;
		
	}
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 */
class FckeditorFormControl extends FormControl  {
	
	
	function generate_control() {
		
		$FCKeditor = new FCKeditor($this->name);
		$FCKeditor->BasePath = APP_ROOT.'_fckeditor/';
		$FCKeditor->ToolbarSet = 'Default';
		$FCKeditor->Height = $this->params['height'];
		$FCKeditor->Value = $this->value;
		return $FCKeditor->CreateHtml();

	}
	
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 */
class DateFormControl extends FormControl {
	
	function generate_control() {
		$discard = assign($this->params['discard']);
		$blank = false;
		$date = new Date($this->value);
		
		$return = "<select name='$this->name[year]'>".make_number_options($date->get_year() - 15, $date->get_year() + 15, $date->get_year()) ."</select>\n";
		if ($discard == 'months') { $blank = true; }
		
		if ($blank) {
			$return .= "<input type='hidden' name='$this->name[month]' value='1' />";
		} else {
			$return .= "-<select name='$this->name[month]'>".make_options(Date::month_names(), $date->get_month(), '', true) ."</select>\n";
		} 
		
		if ($discard == 'days') { $blank = true; }
				
		if ($blank) {
			$return .= "<input type='hidden' name='$this->name[day]' value='1' />";
		} else {
			$return .= "-<select name='$this->name[day]'>".make_number_options(1, 31, $date->get_date(), true) ."</select>\n";
		}
		if ($discard == 'hours') { $blank = true; }
		
		if ($blank) {
			$return .= "<input type='hidden' name='$this->name[hour]' value='1' />";
		} else {
			$return .= "&nbsp;&nbsp;&nbsp;<select name='$this->name[hour]'>".make_number_options(0, 23, $date->get_hours(), true) ."</select>\n";
		}
		if ($discard == 'minutes') { $blank = true; }
				
		if ($blank) {
			$return .= "<input type='hidden' name='$this->name[minute]' value='1' />";
		} else {
			$return .= ":<select name='$this->name[minute]'>".make_number_options(0, 59, $date->get_minutes(), true) ."</select>\n";
		}
		if ($discard == 'seconds') { $blank = true; }
		
		if ($blank) {
			$return .= "<input type='hidden' name='$this->name[seconds]' value='1' />";
		} else {
			$return .= ":<select name='$this->name[seconds]'>".make_number_options(0, 59, $date->get_seconds(), true) ."</select>\n";
		}

		return $return;	
	}
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 */
class ImageFormControl extends FormControl {
/*	function generate_label() {
		return "<th>&nbsp;</th>";		
	}*/
	
	function generate_control() {

		if ($this->value) {
			return "<img src='".$this->value."' /><br />".basename($this->value);
		} else {
			return "No image";
		}
	}
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 */
class YesnoFormControl extends FormControl {
	function generate_control() {	
		if (!$this->options) {
			$this->options = array('no', 'yes');
		}
		
		return "<select name='$this->name'>".make_options($this->options, $this->value, '', true) ."</select>";
	}
	
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 */
class LivenotliveFormControl extends FormControl {
	function generate_control() {	
		if (!$this->options) {
			$this->options = array('not live', 'live');
		}
		
		return "<select name='$this->name'>".make_options($this->options, $this->value, '', true) ."</select>";
	}
	
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 */
class FilepopupFormControl extends FormControl {
	function generate_control() {	

		$this->params['id'] = $this->name;
		$this->params['name'] = $this->name;
		$this->params['value'] = $this->escape($this->value);
		$this->params['type'] = 'text';
		$this->params['class'] = 'popup';
		
		return "<input ".$this->get_attributes()." /><input type='button' value='Browse...' onclick='popup_window(\"".make_link(array('type'=>'file', 'id'=>$this->name))."\")'/>";
	}
	
}

/**
 * Returns a string of <option>s for a range of number values
 *
 * @param int $min the number to start at
 * @param int $max the number to end at
 * @param int $default_value the value to select
 * @param bool $zero_padded set to true to pad the options with zeros
 * @return string
 */
function make_number_options($min, $max, $default_value, $zero_padded = false) {
	$return = '';

	for ($x = $min; $x <= $max; $x ++) {
		$return .= "<option";
		
		if ($default_value && $x == (int) $default_value) {
			$return .= " selected='selected' ";
		}
		
		$return .= ">";
		
		if ($zero_padded) {
			$return .= str_pad($x, strlen($max), '0', STR_PAD_LEFT);
		} else {
			$return .= $x;
		}
		
		$return .= "</option>\n";
		
	}

	return $return;
	
}

/**
 * Returns a string of <option>s for a given array
 *
 * @return string
 */
function make_options($data, $default_value = '', $not_found = '', $use_numeric_keys = false) {

	$return = '';
	
	foreach ($data as $key => $value) {
		$return .= "<option";
		
		if (is_string($key) || $use_numeric_keys) {
			$return .= " value='".htmlentities($key, ENT_QUOTES, 'UTF-8')."'";
		} else {
			$key = $value;
		}
		
		if ($default_value && $key == $default_value) {
			$return .= " selected ";
			$default_value = '';
			
		}
		
		$return .= ">".htmlentities($value, ENT_QUOTES, 'UTF-8')."</option>\n";
		
	}

	if ($default_value && $not_found) {
		$return = "<option value='$default_value'>$not_found</option>\n".$return;
		
	}
	
	return $return;
}

/**
 * Returns a string of <input type='checkbox' />s for a given array
 */
function make_checkboxes($data, $name, $selected = null, $use_numeric_keys = false) {
	if (is_null($selected)) {
		$selected = array();	
		
	}

	$return = '';
	
	foreach ($data as $key => $value) {
		$return .= "<label><input type='checkbox' name='".$name."[]'";
		
		if (!is_string($key) && !$use_numeric_keys) {
			$key = $value;
		}
		
		$return .= " value='".htmlentities($key, ENT_QUOTES, 'UTF-8')."'";
		
		if (in_array($key, $selected)) {
			$return .= " checked='checked'";
		
		}
		
		$return .= "/>".htmlentities($value, ENT_QUOTES, 'UTF-8')."</label>\n";
		
	}


	return $return;
	
}


/**
 * Class for form buttons
 *
 * @package view
 * @subpackage html
 */
class SubmitFormControl extends FormControl {

	function generate_control() {
		$this->params['id'] = $this->name;		
		$this->params['name'] = $this->name;
		$this->params['type'] = 'submit';
		$this->params['value'] = $this->value;

		if (isset($this->params['confirm'])) {
			$this->params['onclick'] = "return confirm('".$this->params['confirm']."')";
			unset($this->params['confirm']);			
			
		}		
		
		$return = "<input ".$this->get_attributes()." />";
		
		return $return;
		
	}
	
}



?>