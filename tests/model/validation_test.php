<?php

/**
 * Test proper operation of individual validation rules
 */
class ValidationRuleTester extends UnitTestCase {
	
	var $data;
	
	function setup() {
		$this->data = array(
			'id'=>'2',
			'name'=>'george',
			'full_name' => '',
			'email'=>'george@thejungle.com',
			'email_confirmation'=>'george@thejungle.com',
			'password'=>'ofthejungle',
			'role' => 'user',
			'terms' => '1',
			'height' => '170cm',
			'weight' => '120.2'
		);
		
	}
	
	function test_presence_validation() {
		
		$this->data['email'] = '';
		
		// name is contained
		$validator = new PresenceValidationRule('name');
		$this->asserttrue($validator->validate($this->data));
		$this->assertfalse(count($validator->get_messages()));
		
		// name is not present
		$validator = new PresenceValidationRule('email');
		$this->assertfalse($validator->validate($this->data));
		$this->assertequal($validator->get_messages(), array("Email ".$validator->message));

		// with a custom error message
		$validator = new PresenceValidationRule('email', array('message'=>'is required'));
		$this->assertfalse($validator->validate($this->data));
		$this->assertequal($validator->get_messages(), array("Email is required"));
		
		// only on create
		$validator = new PresenceValidationRule('email', array('on'=>'create'));		
		$this->assertfalse($validator->validate($this->data, true));
		$this->asserttrue($validator->validate($this->data, false));
		
		// with multiple attributes
		$validator = new PresenceValidationRule(array('name', 'email'));
		$this->assertfalse($validator->validate($this->data));
		$this->assertequal($validator->get_messages(), array("Email ".$validator->message));		
		
		
	}
	
	function test_acceptance_validation() {
		$validator = new AcceptanceValidationRule('terms');
		$this->asserttrue($validator->validate($this->data));
		
		unset($this->data['terms']);
		
		$this->assertfalse($validator->validate($this->data));
		
	}

	function test_confirmation_validation() {
		$validator = new ConfirmationValidationRule('email');	
		$this->asserttrue($validator->validate($this->data));
		
		unset($this->data['email_confirmation']);
		
		$this->assertfalse($validator->validate($this->data));
	
	}
	
	function test_inclusion_validation() {
		$validator = new InclusionValidationRule('role', array('in'=>array('user')));
		$this->asserttrue($validator->validate($this->data));

		$this->data['role'] = 'admin';
		
		$this->assertfalse($validator->validate($this->data));
		
		$this->data['role'] = null;
		
		$this->assertfalse($validator->validate($this->data));
		
		$validator = new ExclusionValidationRule('role', array('in'=>array('admin'), 'allow_null'=>true));
		$this->asserttrue($validator->validate($this->data));
		
		
	}	
	
	function test_exclusion_validation() {
		$validator = new ExclusionValidationRule('role', array('in'=>array('admin')));
		$this->asserttrue($validator->validate($this->data));

		$this->data['role'] = 'admin';
		
		$this->assertfalse($validator->validate($this->data));
		
		$this->data['role'] = null;
		
		$this->asserttrue($validator->validate($this->data));
		
		$validator = new ExclusionValidationRule('role', array('in'=>array('admin'), 'allow_null'=>true));
		$this->asserttrue($validator->validate($this->data));
		
		
	}
	
	function test_format_validation() {
		$validator = new FormatValidationRule('height', array('with'=>'/^\d+(in|cm)/'));	
		
		$this->asserttrue($validator->validate($this->data));

		$this->data['height'] = '80';
		
		$this->assertfalse($validator->validate($this->data));		
		
	}
	
	function test_length_validation() {
		// test pass of exact length
		$validator = new LengthValidationRule('name', array('is'=>'6'));
		$this->asserttrue($validator->validate($this->data));	

		// test fail of exact length
		$validator = new LengthValidationRule('name', array('is'=>'7'));
		$this->assertfalse($validator->validate($this->data));	
		$this->assertequal($validator->get_messages(), array('Name is the wrong length (should be 7 characters)'));

		// test pass of minimum length
		$validator = new LengthValidationRule('password', array('minimum'=>'6'));
		$this->asserttrue($validator->validate($this->data));	
		
		// test fail of minimum length
		$validator = new LengthValidationRule('password', array('minimum'=>'20'));
		$this->assertfalse($validator->validate($this->data));	
		$this->assertequal($validator->get_messages(), array('Password is too short (min is 20 characters)'));

		// test pass of maximum length
		$validator = new LengthValidationRule('password', array('maximum'=>'20'));
		$this->asserttrue($validator->validate($this->data));	
		
		// test fail of maximum length
		$validator = new LengthValidationRule('password', array('maximum'=>'10'));
		$this->assertfalse($validator->validate($this->data));	
		$this->assertequal($validator->get_messages(), array('Password is too long (max is 10 characters)'));		
		
	}

	
	function test_numericality_of() {
		$validator = new NumericalityValidationRule(array('id','weight'));	
		$this->assertTrue($validator->validate($this->data));
		
		$validator = new NumericalityValidationRule(array('name','height'));	
		$this->assertFalse($validator->validate($this->data));
		$this->assertEqual($validator->get_messages(), array('Name is not a number', 'Height is not a number'));
		
		$validator = new NumericalityValidationRule(array('id','weight'), array('only_integer'=>true));	
		$this->assertFalse($validator->validate($this->data));
		$this->assertEqual($validator->get_messages(), array('Weight is not a number'));
		
	}
	
}

/**
 * Test proper operation of validator
 */
class ValidationTester extends UnitTestCase {

	/**
	 * @var Validation
	 */
	var $validate;
	
	function setup() {
		$this->validate = new Validation();
		
		
	}
	
	function test_simple_data() {
		$rule = $this->validate->add('presence', 'password');
		
		$this->assertIsA($rule, 'ValidationRule');
		
		$data = array('password'=>'test');	
		
		$this->assertTrue($this->validate->run($data));
		
		$data = array('password'=>'');
		
		$this->assertFalse($this->validate->run($data));
		
	}
	
	function test_complex_data() {
		
		$this->validate->add('presence', 'password');
		$this->validate->add('acceptance', 'terms');
		$this->validate->add('confirmation', 'email');
		$this->validate->add('exclusion', 'role', array('in'=>array('author', 'user')));
		$this->validate->add('format', 'height', array('with'=>'@^\d+(in|cm)@'));
		$this->validate->add('inclusion', 'gender', array('in'=>array('male', 'female')));
		$this->validate->add('length', 'username', array('maximum'=>20, 'minimum'=>2));
		$this->validate->add('numericality', 'count');
		
		// this data will satisfy all conditions
		$data = array(
			'id'=>'2',
			'username'=>'george',
			'email'=>'george@thejungle.com',
			'email_confirmation'=>'george@thejungle.com',
			'password'=>'ofthejungle',
			'role' => 'admin',
			'terms' => '1',
			'height' => '170cm',
			'count' => '120',
			'gender' => 'male'
		);
		
		$this->assertTrue($this->validate->run($data));

		// now for some bad data
		$data = array(
			'id'=>'2',
			'username'=>'georgeofthejungleisthegreatest',
			'email'=>'george@thejungle.com',
			'email_confirmation'=>'george@thejunkle.com',
			'password'=>'',
			'role' => 'user',
			'terms' => '0',
			'height' => '170',
			'count' => '12ets',
			'gender' => 'unknown'
		);	
		
		$expected_errors = array(
		    "Password can't be empty",
		    "Terms must be accepted",
		    "Email doesn't match confirmation",
		    "Role is included in the list",
		    "Height is invalid",
		    "Gender is not included in the list",
		    "Username is too long (max is 20 characters)",
		    "Count is not a number"
		);
		
		$this->assertFalse($this->validate->run($data));		
		$this->assertEqual($this->validate->get_messages(), $expected_errors);
		
	}
	
}
?>