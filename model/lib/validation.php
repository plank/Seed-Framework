<?php

class Validation {
	
	/**
	 * The validation rules to run
	 *
	 * @var array
	 */
	var $validations;
	
	/**
	 * The errors raised by the validation
	 *
	 * @var array
	 */
	var $errors;
	
	/**
	 * The model object that validation rules are being run against
	 *
	 * @var Model
	 */
	var $model;
	
	/**
	 * Constructor
	 *
	 * @return Validation
	 */
	function Validation() {
		$this->validations = array();		
		$this->errors = array();
		$this->setup();
	}

	function setup() {
		
		
	}
	
	/**
	 * Runs the validation rules
	 *
	 * @param array $values The values to run the validation on
	 * @param bool $create Set to true if this is a create query, false if it's an update
	 * @return bool
	 */
	function run($values, $create = true) {
		$valid = true;

		foreach ($this->validations as $validation) {
			if ($validation->validate($values, $create) === false) {
				$valid = false;
				$this->errors = array_merge($this->errors, $validation->get_messages());
			} 	
		}

		return $valid;
		
	}
	
	/**
	 * @return array
	 */
	function get_messages() {
		return $this->errors;	
		
	}
	
	/**
	 * @param string $message
	 */
	function add_message($message) {
		$this->errors[] = $message;
	}
	
	/**
	 * Generic rule adding method
	 *
	 * @param string $type
	 * @param mixed $attribute
	 * @param array $options
	 */
	function add($type, $attribute, $options = null) {
		$rule = ValidationRule::factory($type, $attribute, $options);
		
		$rule->model = & $this->model;

		$this->validations[] = & $rule;
		
		return $rule;
	}
	
	function presence_of($attribute, $options = null) {
		if (is_null($options)) {
			$options = array();	
		}
		
		$rule = new PresenceValidationRule($attribute, $options);
		
		$this->validations[] = $rule;
		
	}

	function acceptance_of($attribute, $options = null) {
		if (is_null($options)) {
			$options = array();	
		}
		
		$rule = new AcceptanceValidationRule($attribute, $options);
		
		$this->validations[] = $rule;
		
	}
	
}

class ValidationRule {

	/**
	 * The model to run the validation rule on
	 *
	 * @var Model
	 */
	var $model;
	
	/**
	 * The atributes to run the validation rule on
	 *
	 * @var array
	 */
	var $attributes;
	
	/**
	 * The atribute names to display
	 *
	 * @var array
	 */
	var $attribute_names;
	
	/**
	 * Validation is run on create?
	 *
	 * @var bool
	 */
	var $on_create = false;
	
	/**
	 * Validation is run on update?
	 *
	 * @var bool
	 */
	var $on_update = false;
	
	/**
	 * Validation message
	 *
	 * @var string
	 */
	var $message = '';
	
	/**
	 * Other, optional parameters
	 *
	 * @var array
	 */
	var $params;	
	
	/**
	 * The valid fields
	 *
	 * @var array
	 */
	var $valid;
	
	/**
	 * The invalid fields
	 *
	 * @var array
	 */
	var $invalid;
	
	/**
	 * Array of error messages
	 *
	 * @var array
	 */
	var $error_messages;
	
	/**
	 * If set to true, empty values simply pass validation
	 *
	 * @var bool
	 */
	var $allow_empty;
	
	/**
	 * Constructor
	 *
	 * @param mixed $attributes
	 * @param array $params
	 * @return ValidationRule
	 */
	function ValidationRule($attributes, $params = null) {
		if (is_array($attributes)) {
			$this->attributes = $attributes;
			
		} else {
			$this->attributes = array($attributes);	
			
		}
		
		if (is_null($params)) {
			$this->params = array();
			
		} else {
			$this->params = $params;
			
		}
		
		// assign "on" param
		$on = assign($this->params['on'], 'save');
		
		if ($on == 'save' || $on == 'update') {
			$this->on_update = true;
		}
		
		if ($on == 'save' || $on == 'create') {
			$this->on_create = true;
		}
		
		unset($this->params['on']);
		
		// assign "message" param
		if (isset($params['message'])) {
			$this->message = $params['message'];
			unset($this->params['message']);	
			
		}
		
		if (isset($params['attribute_names'])) {
			$this->attribute_names = $params['attribute_names'];
			unset($this->params['attribute_names']);	
		}
		
		$this->allow_empty = assign($this->params['allow_empty'], false);			
		
		$this->setup();
		
	}
	
	function setup() {
		
	}
	
	function factory($type, $attributes, $params = null, $model_type = '') {
		$type = strtolower($type);		
		
		$class_name = Inflector::camelize($type).'ValidationRule';
		
		if (!class_exists($class_name)) {
			trigger_error("Class '$class_name' does not exist", E_USER_ERROR);
			return false;
		}
		
		$rule = new $class_name($attributes, $params, $model_type);
		
		if (!is_a($rule, __CLASS__)) {
			trigger_error("Class '$class_name' doesn't extend ValidationRule", E_USER_ERROR);
			return false;
		}
		
		return $rule;
		
	}
	
	/**
	 * Returns an array of error messages
	 *
	 * @return array
	 */
	function get_messages() {
		return $this->error_messages;
		
	}
	
	function add_error_message($attribute, $value = null, $message = null) {
		if (is_null($message)) {
			$message = $this->message;	
		}
		
		if (isset($this->attribute_names[$attribute])) {
			$attribute_name = $this->attribute_names[$attribute];	
		} else {
			$attribute_name = Inflector::humanize($attribute);
		}

		
		$this->error_messages[] = sprintf($message, $attribute_name, $value);
		
	}
	
	/**
	 * Runs the validation test on the values provided
	 *
	 * @param array $values The values to (possibly) validate
	 * @param string $create Set to true if the current action is create, false if it's update
	 * @return bool True if the code passes the validation test
	 */
	function validate($values, $create = true) {
		// only run the rules if they're required 
		if (($create && !$this->on_create) || (!$create && !$this->on_update)) {
			return true;
		}
		
		$valid = true;
		$this->valid = array();
		$this->invalid = array();
		$this->error_messages = array();
	
		foreach($this->attributes as $attribute) {

			if (!key_exists($attribute, $values)) {
				$value = null;
			} else {
				$value = $values[$attribute];
			}
			
			// skip empty values if that option is set
			if (!$value && $this->allow_empty) {
				$this->valid[] = $attribute;
				
			} elseif ($this->validate_attribute($values, $attribute, $value) === false) {
				$valid = false;
				$this->invalid[] = $attribute;	
			
			} else {
				$this->valid[] = $attribute;	
				
			}
			
		}

		return $valid;
		
	}
	
	function validate_attribute($values, $attribute, $value) {
		return true;
		
	}	
	
}

/**
 * Validates that a value is present for the attribute
 */
class PresenceValidationRule extends ValidationRule {
	
	var $message = "%s can't be empty";
	
	function validate_attribute($values, $attribute, $value) {
		if (is_null($value) || $value == '') {
			$this->add_error_message($attribute, $value);
			return false;
			
		} else {
			return true;
			
		}
	}
	
}

/**
 * Validates that a checkbox has been checked
 */
class AcceptanceValidationRule extends ValidationRule {
	var $message = "%s must be accepted";

	function validate_attribute($values, $attribute, $value) {
		$result = ($value && true);	
		
		if (!$result) {
			$this->add_error_message($attribute, $value);	
		}
		
		return $result;
		
	}
	
}

/**
 * Validated that a field has the same content as another
 */
class ConfirmationValidationRule extends ValidationRule {
	var $message = "%s doesn't match confirmation";	
	
	var $confirmation_attribute;
	
	function validate_attribute($values, $attribute, $value) {
		if (isset($params['confirmation'])) {
			$this->confirmation_attribute = $params['confirmation'];
			
		} else {
			$this->confirmation_attribute = $attribute.'_confirmation';
			
		}		
		
		$confirmation_value = assign($values[$this->confirmation_attribute], '');	
		
		$result = ($value == $confirmation_value);
		
		if (!$result) {
			$this->add_error_message($attribute, $value);	
		}		
		
		return $result;
		
	}
	
}

class InclusionValidationRule extends ValidationRule {
	var $message = "%s is not included in the list";	
	var $in;
	
	function setup() {
		$this->in = assign($this->params['in'], array());
		
	}
	
	function validate_attribute($values, $attribute, $value) {
		$result = in_array($value, $this->in);
		
		if (!$result) {
			$this->add_error_message($attribute, $value);	
			
		}
		
		return $result;
		
	}	
}

class ExclusionValidationRule extends ValidationRule {
	var $message = "%s is included in the list";	
	var $allow_empty;
	var $in;
	
	function setup() {
	
		
		$this->in = assign($this->params['in'], array());
		
	}
	
	function validate_attribute($values, $attribute, $value) {
		$result = !in_array($value, $this->in);
		
		if (!$result) {
			$this->add_error_message($attribute, $value);	
			
		}
		
		return $result;
		
	}	
}

class FormatValidationRule extends ValidationRule {
	var $message = "%s is invalid";
	var $regexp;
	
	function setup() {
		$this->regexp = assign($this->params['with']);
		
	}
	
	function validate_attribute($values, $attribute, $value) {
		$result = (preg_match($this->regexp, $value) && true);
		
		if (!$result) {
			$this->add_error_message($attribute, $value);	
			
		}
		
		return $result;
		
	}	
	
}

class LengthValidationRule extends ValidationRule {
	var $wrong_length_message = '%s is the wrong length (should be %d characters)';	
	var $too_long_message = '%s is too long (max is %d characters)';
	var $too_short_message = '%s is too short (min is %d characters)';
	
	var $minimum;
	var $maximum;
	
	function setup() {
		if (isset($this->params['is'])) {
			$this->maximum = $this->minimum	= $this->params['is'];
			
		} else {
			$this->maximum = assign($this->params['maximum'], null);
			$this->minimum = assign($this->params['minimum'], null);
			
		}
		
		if (isset($this->params['message'])) {
			$this->wrong_length_message = $this->too_long_message = $this->too_short_message = $this->params['message'];	
			
		}
		
	}
	
	function validate_attribute($values, $attribute, $value) {
		$value = strlen($value);
		
		if ($this->minimum == $this->maximum && $this->minimum != $value) {
			$this->add_error_message($attribute, $this->minimum, $this->wrong_length_message);
			return false;
		}
		
		if (isset($this->minimum) && $value < $this->minimum) {
			$this->add_error_message($attribute, $this->minimum, $this->too_short_message);	
			return false;	
		}
		
		if (isset($this->maximum) && $value > $this->maximum) {
			$this->add_error_message($attribute, $this->maximum, $this->too_long_message);	
			return false;
		}
		
		return true;
		
	}	
	
}

class NumericalityValidationRule extends ValidationRule  {
	var $message = '%s is not a number';
	var $only_integer;
	
	function setup() {
		if (isset($this->params['only_integer']) && $this->params['only_integer']) {
			$this->only_integer = true;	
			
		} else {
			$this->only_integer = false;
				
		}

	}
	
	function validate_attribute($values, $attribute, $value) {
				
		$result = is_numeric($value);	
		
		if ($this->only_integer) {
			$result = $result && (intval($value) == floatval($value));
			
		} 
		
		if (!$result) {
			$this->add_error_message($attribute, $value);	
			
		}
		
		return $result;
		
	}	
	
}

class EmailValidationRule extends ValidationRule {
	var $message = '%s is not a valid email address';
	var $allow_empty;
	
	function setup() {
		$this->allow_empty = assign($this->params['allow_empty'], false);	
	}
	
	function validate_attribute($values, $attribute, $value) {
		if (!$value  && $this->allow_empty) {
			return true;
		}
						
		$result = is_valid_email_address($value);	
		
		if (!$result) {
			$this->add_error_message($attribute, $value);	
			
		}
		
		return $result;
		
	}	
	
}

/**
* checks that an extension is proper type
*
* When declaring this validation rule, pass allowed types in a params array of allowed type.
* $this->validate->add('extension', 'pdf', array('allowed_types'=>array('pdf'),'message'=>'need pdf'));
* 
* @param	string	Target directory for the generated HTML Files
*/
class ExtensionValidationRule extends ValidationRule {
	var $message = '%s is not a the right extension';

	var $allow_empty;
	
	function setup() {
		//default: allow empty
		$this->allow_empty = assign($this->params['allow_empty'], true);	
	}
	
	function validate_attribute($values, $attribute, $value) {
		//$values - the object being checkd
		
		//$attribute - the column being checked
		
		//$value - the value being checked (in this case, the filename)
		
		//Types to check file extension against
		//$this->params['allowed_types'];

		if (!$value  && $this->allow_empty) {
			return true;
		}

		$allowed_types = $this->params['allowed_types']; 
						
		foreach ($allowed_types as $allowed_type) {
			
			$parts = pathinfo($value);
			
			if(strtolower($parts['extension']) == $allowed_type){
				$result = true;
				break;
			} else {		
				$result = false;
			}
		}
		
		if (!$result) {
			$this->add_error_message($attribute, $value);	
		}
				
		return $result;
		
	}	
	
}

class UniquenessValidationRule extends ValidationRule {
	var $message = "%s is already taken";
	
	function validate_attribute($values, $attribute, $value) {
		
		$condition = $this->model->db->escape_identifier($attribute)." = '".$this->model->db->escape($value)."'";
		
		if (isset($values[$this->model->id_field]) && $values[$this->model->id_field]) {
			$condition .= ' AND '.$this->model->id_field." <> ".$values[$this->model->id_field];
			
		}
		
		$finder = $this->model->finder();
		$result = $finder->find('first', array('conditions' => $condition));
	
		if ($result) {
			$this->add_error_message($attribute, $value);	
			return false;	
		}
		
		return true;
		
	}
	
}

?>