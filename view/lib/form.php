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

seed_include('library/date');

/**
 * Class for generating html forms
 *
 * @package view
 * @subpackage html
 */
class Form {
	/**
	 * The html id for the table
	 *
	 * @var string
	 */
	var $id;
	
	/**
	 * The controller containing the table
	 *
	 * @var Controller
	 */
	var $controller;	
	
	/**
	 * Translator
	 *
	 * @var Translator
	 */
	var $translator;
	
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
	 * The class name to use for the table. 
	 *
	 * If this is left blank, it will default to edit_table or view_table, depending on which mode it's used in
	 *
	 * @var string
	 */
	var $class_name = '';
	
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
	
	var $right_to_left = false;
	
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
		$this->translator = new Translator();
		
		if (!$this->id) {
			$this->id = Inflector::underscore(get_class($this));
		}		
		
		$this->setup();
	}
	
	/**
	 * Called by the constructor, this is the place to place add_control calls
	 * to populate the form when subclassing
	 *
	 */
	function setup() {
		$this->auto_setup();	
	}
	
	function auto_setup() {
		
		if(!is_a($this->data, 'Model')) {
			return false;	
		}
		
		foreach($this->data->columns() as $column) {
			switch (true) {
				case $column->name == 'id':
					$this->hidden_fields = array('id');
					break;
			
				case $column->type == 'string':
					$this->add_control('input', $column->name);
					break;

				case $column->type == 'binary':
				case $column->type == 'text':
					$this->add_control('textarea', $column->name);
					break;

					
				case $column->type == 'integer':
					$key = substr($column->name, 0, strlen($column->name) - 3);
					
					if (isset($this->data->associations[$key]) && $this->data->associations[$key]->type = 'belongs_to') {
						$this->add_control('select', $column->name, ucfirst($key), null, $this->data->associations[$key]->class_name);	
					}
					break;
					
				case $column->type == 'date':
				case $column->type == 'datetime':
					if ($column->name == 'created_at' || $column->name == 'modified_at') {
						continue;
					}
				
					$this->add_control('date', $column->name);
					break;
				
			}
			
		}
		
		$this->add_default_buttons();
		
		return true;
		
	}
	
	/**
	 * Adds the default save and cancel buttons to the form
	 */
	function add_default_buttons() {
		$this->add_button('submit', 'Save');
		$this->add_button('cancel', 'Cancel');	
		
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
		$control->translator = & $this->translator;
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
		$control->set_options($options);

		$this->append_control($control);
		
		return $control;
	}
	
	/**
	 * Adds an existing form object to the collection
	 *
	 * @param FormControl $control
	 */
	function append_control(& $control) {
		$control->translator = & $this->translator;
		$this->controls[$control->name] = & $control;
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
		$button->translator = & $this->translator;
		$this->buttons[] = & $button;	
		
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
	function generate($data = null, $read_only = false) {

		if (isset($this->controller->config)) {
			$this->right_to_left = $this->controller->config->is_right_to_left($this->translator->lang);		
		}
		
		if (isset($data)) {
			$this->data = $data;
		}
		
		if ($read_only) { 
			$return = '';	
		} else {
			$return = "<form action='$this->action' method='post' enctype='multipart/form-data'>\n";
	
			if ($this->max_file_size) {
				$return .= "<input type='hidden' name='MAX_FILE_SIZE' value='".$this->max_file_size."' />";			
				
			}
		}
		
		foreach ($this->hidden_fields as $field) {
			if (!is_null($data = $this->get_value($field))) {
				$control = new HiddenFormControl($field);
				$return .= $control->generate($data);
			}
		}
		
		if (!$this->class_name) {
			if ($read_only) {
				$class_name = 'view_table';	
			} else {
				$class_name = 'edit_table';	
			}
		} else {
			$class_name = $this->class_name;	
		}
		
		$return .= "<table class='$class_name' id='$this->id' >\n";
		
		$row_number = 1;
		
		foreach ($this->controls as $field => $control) {
			$data = $this->get_value($field);
			
			$return .= $this->generate_row($control, $data, $row_number ++, $read_only);
			
		}
		
		if (!$read_only) {
			$return .= $this->generate_buttons();
		}
		
		$return .= "</table>\n";
		
		if (!$read_only) {
			$return .= "</form>\n";
		}
		
		return $return;
	}
	
	/**
	 * @param FormControl $control
	 */
	function generate_row($control, $data, $row_number, $read_only = false) {
		
		if (!$control->show_in_mode($read_only)) {
			return '';	
		}
		
		$classname = $row_number % 2 ? 'odd' : 'even';		
		
		$return = "<tr class='$classname'>";		
		
		if ($this->right_to_left) {
			$return .= "<td>".$control->generate($data, $read_only)."</td>";			
			$return .= "<th><label for='$control->name'>".$this->translator->text($control->label)."</label></th>";		
		} else {
			$return .= "<th><label for='$control->name'>".$this->translator->text($control->label)."</label></th>";		
			$return .= "<td>".$control->generate($data, $read_only)."</td>";
		}
		
		$return .= "</tr>\n";
		
		return $return;
	}
	
	function generate_buttons() {
		$return = "<tr>";
		
		if (!$this->right_to_left) $return .= "<th>&nbsp;</th>";		
		$return .= "<td>";
		
		foreach($this->buttons as $button) {

			// $button->translator = & $this->translator;
			$return .= $button->generate()."&nbsp;";	
		}
		
		$return .= "</td>";
		
		if ($this->right_to_left) $return .= "<th>&nbsp;</th>";		
		
		$return .= "</tr>\n";
		
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
	 * @var Translator
	 */
	var $translator;
	
	/**
	 * The current language
	 *
	 * @var string
	 */
	var $lang;
	
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
	
	/**
	 * Text to display when value isn't found
	 */
	var $empty_value = '-';	
	
	function FormControl($name = '', $params = null, $options = null) {
		if ($name) {
			$this->name = $name;
			$this->label = ucfirst($name);
		}
		
		if (!is_null($params)) $this->params = $params;
		
		if (!is_null($options)) $this->options = $options;
		
	}
	
	function set_options($options) {
		$this->options = $options;	
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
	function generate($value = null, $read_only = false) {
		if (isset($this->params['value'])) {
			$this->value = $this->params['value'];
		
		} elseif (isset($value)) {
			$this->value = $value;
			
		}
		
		if ($read_only) {
			return $this->generate_read_only();	
		} else {
			return $this->generate_control();
		}

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
	 * Generate a read only version of the control
	 *
	 * @return string
	 */
	function generate_read_only() {
		if ($this->value) {
			return $this->value;	
		} else {
			return $this->empty_value;
		}	
	}
	
	/**
	 * Returns a string of attributes for the control
	 *
	 * @return string
	 */
	function get_attributes() {
		foreach($this->params as $name => $value) {
			$return[] = $name.'="'.htmlentities($value, ENT_QUOTES, 'UTF-8').'"';
		}
		
		return implode(' ', $return);
	}
	
	function escape($string) {
		return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
		//return utf8_decode($string);
	}

	/**
	 * Returns true if the control should be shown in the current mode
	 *
	 * @param bool $read_only  Set to true when in read mode, false when in edit mode
	 * @return bool
	 */
	function show_in_mode($read_only = false) {
		$mode = assign($this->params['only'], false);
		
		if ($read_only && $mode == 'edit') {
			return false;
		} 
		
		if (!$read_only && $mode == 'read') {
			return false;	
		}	
		
		return true;
	}
	
}


class DisplayFormControl extends FormControl {
	
	
}

class StaticFormControl extends FormControl {
	function generate($value = null) {
		if ($this->params['text']) {
			return '<strong>'.$this->params['text'].'</strong>';
		} else {
			return '<hr />';
		}
		
	}	
}

class AutocompleteFormControl extends FormControl {
	function generate_control() {
		$this->params['id'] = $this->name;
		$this->params['name'] = $this->name;
		$this->params['value'] = $this->value;
		$this->params['type'] = 'text';
		$this->params['class'] = 'text';
				
		$return = "<input ".$this->get_attributes()." /><div id='".$this->name."_choices' class='autocomplete'></div>\n";		
		$return .= "<script type='text/javascript'>new Ajax.Autocompleter('".$this->name."', '".$this->name."_choices', '".$this->options."', { tokens: ',' });</script>\n";

		return $return;
	
	}	
	
}

/**
 * Class for hidden form controls
 *
 * @package view
 * @subpackage html
 */
class HiddenFormControl extends FormControl {
	function generate_control() {
		
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
class TextFormControl extends FormControl  {
	function generate_control() {
		if (isset($this->params['prefix'])) {
			$prefix = $this->params['prefix'];
			unset($this->params['prefix']);
		} else {
			$prefix = '';
		}
		
		$this->params['id'] = $this->name;
		$this->params['name'] = $this->name;
		$this->params['value'] = $this->value;
		$this->params['type'] = 'text';
		$this->params['class'] = 'text';
		
		return "$prefix<input ".$this->get_attributes()." />";
	}
}

// for backwards compatibility
class InputFormControl extends TextFormControl { }

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
		$this->params['value'] = $this->value;
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
				
		return "<textarea ".$this->get_attributes()." >".$this->escape($this->value)."</textarea>";
	}
}

class SelectmultipleFormControl extends SelectFormControl {
	function generate_read_only() {
		$this->fix_options($this->options);
		
		// directly return the values from iterators
		if (is_a($this->value, 'SeedIterator')) {
			if ($this->value->size() == '0') {
				return $this->empty_value;	
			}
			
			$values = $this->value->to_name_array();
			
			return implode(', ', $values);
		} 
		
		// values is an id or an array of ids
		if (!is_array($this->value)) {
			$values = array($this->value);
		} else {
			$values = $this->value;	
		}
		
		foreach($values as $value) {
			if (isset($this->options[$value])) {
				$result[] = $this->options[$value];	
			} 
			
		}
		
		if (isset($result)) {
			return implode(', ', $result);
		} else {
			return $this->empty_value;	
		}
		
	}	
	
	
	function generate_control() {
		$this->fix_options($this->options);
		
		if (isset($this->params['allow_none'])) {
			$allow_none = $this->params['allow_none'];
			unset($this->params['allow_none']);
			
		} else {
			$allow_none = false;	
			
		}
		//  debug($this->value->to_name_array('id', 'id'));
		if (!isset($this->params['size'])) {
			$this->params['size'] = 5;
		}
		$this->params['id'] = $this->name;		
		$this->params['name'] = $this->name.'[]';
		$this->params['multiple'] = 'multiple';
		
		$return = "<em>".$this->translator->text("Note: this data is not versioned - changes made will go live immediately even if you save as draft")."</em><br />";
		$return .= "<select ".$this->get_attributes().">";
/*		
		if ($allow_none) {
			$return .= "<option value=''>(none)</option>\n";	
			
		}
	*/	
		if ($this->value) {
			$defaults = $this->value->to_name_array('id', 'id');
		} else {
			$defaults = '';	
		}

		$return .= make_options($this->options, $defaults, '', true);
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
class SelectFormControl extends FormControl {
	
	function fix_options($options) {
		
		if (is_string($options)) {
			$this->options = $this->get_options($options);	
		} else {
			$this->options = $options;	
		}
	}
	
	function generate_control() {
		$this->fix_options($this->options);
		
		if (isset($this->params['allow_none'])) {
			$allow_none = $this->params['allow_none'];
			unset($this->params['allow_none']);
			
		} else {
			$allow_none = false;	
			
		}
		
		$this->params['id'] = $this->name;		
		$this->params['name'] = $this->name;
		
		$return = "<select ".$this->get_attributes().">";
		
		if ($allow_none) {
			$return .= "<option value=''>".$this->translator->text("(none)")."</option>\n";	
			
		}
		
		$return .= make_options($this->options, $this->value, '', true);
		$return .= "</select>";
		
		return $return;
	}
	
	function generate_read_only() {
		$this->fix_options($this->options);
		
		
		if (isset($this->options[$this->value])) {
			return $this->options[$this->value];	
		} else {
			return $this->empty_value;	
		}
		
	}
	
	/**
	 * Returns an array of options for the given type
	 *
	 * @param string $type
	 * @return array
	 */
	function get_options($type) {
		$finder = Finder::factory($type);
		
		$result = array();
		//debug($this->translator);
		$options = $finder->find('all', array('order'=>$finder->model->name_field.' ASC', 'language'=>$this->translator->lang));
		
		while($option = $options->next()) {
			$result[$option->get_id()] = $option->get($option->name_field);	
		}

		return $result;
	}
	
}

/**
 * Displays a combo form controler
 *
 * @package view
 * @subpackage html
 */
class ComboboxFormControl extends SelectFormControl {

	function generate_control() {
		$this->params['onchange'] = "combobox('$this->name')";
		
		if (is_string($this->options)) {
			$this->options = $this->get_options($this->options);	
		}		
		
		$this->options[''] = '(new)';
		
		return parent::generate_control()."<input type='hidden' id='".$this->name."_new' name='".$this->name."_new' value='' />";
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
		$help = assign($this->params['help'], false);
		unset ($this->params['help']);
		$link_root = assign($this->params['link_root']);
		$image_root = assign($this->params['image_root']);
		$removable = assign($this->params['removable'], false);
		
		$return = '';
		
		if (!$this->value) {
			$removable = false;
			$this->value = $this->translator->text('(none)');
		} else {
			if ($image_root) {
				$return = '<img src="' . $image_root.$this->value . '" alt="preview" />';
			}
			
			if ($link_root) {
				$this->value = '<a href="' . $link_root.$this->value . '" target="_blank">' . $this->value . '</a>'; 
			}
		}
			
		$return .= "<div style='margin-bottom: 4px'><em>Existing file: </em> ".$this->value;
		
		if ($removable) {
			$return .= "&nbsp;<label for='{$this->name}_remove'><input type='checkbox' id='{$this->name}_remove' name='$this->name' value='' /> Remove?</label>";	
		}
		
		$return .= "</div><div><em>".$this->translator->text("Upload a new file:")."</em> <input ".$this->get_attributes()." />"; 

		if ($help) {
			$return .= "&nbsp;".$help;
		}

		$return .= "</div>";
		
		
		return $return;
		
	}
	
	
	function generate_read_only() {
		$image_root = assign($this->params['image_root']);	
		
		if ($image_root) {
			//Nesting this, so I don't break anything already working...
			if($this->value != ''){
				$return = '<img mitch="'.$this->value.'" src="' . $image_root.$this->value . '" alt="preview" />';				
			} else {
				$return = $this->translator->text("None");
			}
		} else {
			$return = $this->value;	
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
class FckeditorFormControl extends FormControl  {
	
	
	function generate_control() {
		
		$FCKeditor = new FCKeditor($this->name);
		if (defined('FCKEDITOR_PATH')) {
			$FCKeditor->BasePath = FCKEDITOR_PATH;
		} else {
			$FCKeditor->BasePath = '/FCKeditor/';
		}
		
		if (defined('FCKEDITOR_CUSTOM_CONFIG_PATH')) {
			$FCKeditor->Config['CustomConfigurationsPath']	= FCKEDITOR_CUSTOM_CONFIG_PATH ;
		}

		if (defined('FCKEDITOR_TOOLBAR_SET')) {
			$FCKeditor->ToolbarSet = FCKEDITOR_TOOLBAR_SET ;
		} else{
			$FCKeditor->ToolbarSet = 'Basic';
		}
	
		if (isset($this->params['height'])) {
			$FCKeditor->Height = $this->params['height'];
		}
		$FCKeditor->Value = $this->value;
		return $FCKeditor->CreateHtml();

	}
	
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 * @todo Doesn't support dates such as 1950-0-0, which is valid in mysql
 */
class DateFormControl extends FormControl {
	
	var $year_min = 0;
	var $year_max = 0;
	
	function generate_control() {
		$discard = assign($this->params['discard']);

		$this->year_min = assign($this->params['year_min'],0);
		$this->year_max = assign($this->params['year_max'],0);

		$hide = false;
		
		if (!intval($this->value)) {
			$this->value = 0;	
		}
		
		// if we allow empty, empty dates should show up as blank, if not they should default to the current date
		if (assign($this->params['allow_none'], false)) {
			if ($this->value) {
				$first_option = "<option value='0'></option>";
				$date = new Date($this->value);
			} else {
				$first_option = "<option selected='selected' value='0'></option>";
				$date = false;
			}
			
		} else {
			$first_option = "";	
			$date = new Date($this->value);
		}
		
		$return = '';
		
		$date_parts = array('year' => '', 'month' => '-', 'day' => '-', 'hour' => '&nbsp;&nbsp;', 'minute' => ':', 'second' => ':');
		
		foreach($date_parts as $date_part => $prefix) {
			$method = $date_part.'_options';
			
			// if we've set the discard option to this part, hide it and all the rest
			if ($discard == $date_part.'s') {
				$hide = true;
			}
			
			if ($hide) {
				$return .= "<input type='hidden' name='$this->name[$date_part]' value='1' />";
				
			} else {
				if ($prefix) {
					$return .= "$prefix&nbsp;";
				}
				
				$return .= "<select name='$this->name[$date_part]'>$first_option".$this->$method($date)."</select>\n";
			} 			
		}
		
		return $return;

	}
	
	function year_options($date, $range = array()) {
		
		if($this->year_min == '0') {
			$this->year_min = 1900;
		}
		
		if($this->year_max == '0') {
			$this->year_max = 2020;
		}
		
		if ($date) {
			return make_number_options($this->year_min, $this->year_max, $date->get_year());
		} else {
			return make_number_options($this->year_min, $this->year_max, '');
		}
		
	}
	
	function month_options($date) {
		if ($date) {
			return make_options(Date::month_names(), $date->get_month(), '', true);
		} else {
			return make_options(Date::month_names(), '', '', true);
		}
	}
	
	function day_options($date) {
		if ($date) {
			$day = $date->get_date();
		} else {
			$day = 0;
		}
		
		return make_number_options(1, 31, $day, true);
		
	}
	
	function hour_options($date) {
		if ($date) {
			$hour = $date->get_hours();
		} else {
			$hour = 0;
		}

		return make_number_options(0, 59, $hour, true);	
	}
	
	function minute_options($date) {
		if ($date) {
			$minute = $date->get_minutes();
		} else {
			$minute = 0;
		}

		return make_number_options(0, 59, $minute, true);
	}
	
	function second_options($date) {
		if ($date) {
			$second = $date->get_seconds();
		} else {
			$second = 0;
		}
		
		return make_number_options(0, 59, $second, true);
	}
	
}

/**
 * Base class for form controls
 *
 * @package view
 * @subpackage html
 */
class ImageFormControl extends FormControl {
	
	function generate_control() {

		if ($this->value) {
			return "<img src='".$this->value."' /><br />".basename($this->value);
		} else {
			return $this->translator->text("No image");
		}
	}
}

/**
 * Class for checkboxes
 *
 * @package view
 * @subpackage html
 */
class CheckboxFormControl extends FormControl {
		
	function generate_control() {
		$this->params['id'] = $this->name;
		$this->params['name'] = $this->name;
		$this->params['type'] = 'checkbox';
		$this->params['class'] = 'checkbox';

		if ($this->value) {
			$this->params['checked'] = 'checked';
		}
		
		return "<input ".$this->get_attributes()." />";
		
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
		
		if (isset($this->params['allow_none'])) {
			$allow_none = $this->params['allow_none'];
			unset($this->params['allow_none']);
			
		} else {
			$allow_none = false;	
			
		}		
		
		$return = "<select name='$this->name'>";
		
		if ($allow_none) {
			$return .= "<option value=''>".$this->translator->text('(none)')."</option>\n";	
			
		}
		
		$return .= make_options($this->options, $this->value, '', true) ."</select>";
		
		return $return;
	}

	function generate_read_only() {
		if (!$this->options) {
			$this->options = array('no', 'yes');
		}

		if (isset($this->options[$this->value])) {
			return $this->options[$this->value];
		} else {
			return 'no';
		}
			
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
 * @param int $min 			  The number to start at
 * @param int $max 			  The number to end at
 * @param int $default_value  The value to select
 * @param bool $zero_padded   Set to true to pad the options with zeros
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
 * @param array $data			  The array of data to display
 * @param mixed $default_value    Can be a string or an array of strings indicating the option(s) to select
 * @param string $not_found		  An optional value to display if the default value is not found
 * @param bool $use_numeric_keys  If true, use the keys as options values; this is always true for string keys
 * @param bool $escape			  If true, escapes html
 * @return string
 */
function make_options($data, $default_value = '', $not_found = '', $use_numeric_keys = false, $escape = true) {
	$return = '';
	
	if (!is_array($data)) {
		return false;	
	}
	
	if (!is_array($default_value)) {
		$default_value = array($default_value);	
		
	}
	
	foreach ($data as $key => $value) {
		$return .= "<option";
		
		if (is_string($key) || $use_numeric_keys) {
			if ($escape) {
				$return .= " value='".htmlentities($key, ENT_QUOTES, 'UTF-8')."'";
			} else {
				$return .= " value='".$key."'";
			}
		} else {
			$key = $value;
		}
		
		if (in_array($key, $default_value)) {
			$return .= " selected ";
		}
		
		if ($escape) {
			$return .= ">".htmlentities($value, ENT_QUOTES, 'UTF-8')."</option>\n";
		} else {
			$return .= ">".$value."</option>\n";
		}
		
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
		$this->params['value'] = $this->translator->text($this->value);

		if (isset($this->params['confirm'])) {
			$this->params['onclick'] = "return confirm('".$this->params['confirm']."')";
			unset($this->params['confirm']);			
			
		}		
		
		$return = "<input ".$this->get_attributes()." />";
		
		return $return;
		
	}
	
}



?>