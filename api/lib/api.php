<?php

seed_include('network');

/**
 * Base class for api objects.
 *
 */
class ApiObject {

	var $_dev_key;

	/**
	 * The type of object
	 *
	 * @var string
	 */
	var $_type;

	/**
	 * HTTP Object for requesting data
	 *
	 * @var HTTP
	 */
	var $_connection;

	/**
	 * Constructor
	 *
	 * @param mixed $data
	 * @param HTTP $connection
	 * @return ApiObject
	 */
	function __construct($data, $connection, $dev_key = null) {

		$this->_connection = $connection;

		if (!is_null($dev_key)) $this->_dev_key = $dev_key;

		$result = $this->_parse_data($data);

	}

	function is_valid() {
		return true;
	}

	/**
	 * Parses a data source and assigns values
	 *
	 * @param mixed $data  Can either be a string, an array, or an SimpleXMLElement. If it's a string, it's assumed to be the name.
	 * @return bool
	 */
	function _parse_data($data) {
		if (is_string($data)) {
			$data = array('name'=>$data);
		}

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$this->{$key} = $value;
			}
		}

		if (is_a($data, 'SimpleXMLElement')) {
			$this->_parse_xml($data);
		}

		$this->_fix_data();

		return true;
	}

	/**
	 * Parses an SimpleXMLElement object.
	 *
	 * @param SimpleXMLElement $xml
	 */
	function _parse_xml($xml) {
		foreach ($xml as $key => $value) {
			if (!count($value)) {
				$this->{$key} = trim((string) $value);
			} else {
				$this->{$key} = (array) $value;
			}
		}
	}


	/**
	 * Massages the given data after parsing.
	 *
	 * @return bool
	 */
	function _fix_data() {
		return true;
	}

	function _request_data($method, $params = null, $format = 'xml', $check_existance = false) {
		$url = $this->_request_url($method, $params, $format);

		$this->_connection->open($url);

		if ($check_existance) {
			$data = $this->_connection->head();
		} else {
			$data = $this->_connection->get();
		}

		$this->_connection->close(); // this is probably slowing everything down..

		if (!$data) return false;

		if ($data->response_code != 200) {
			return false;
		}

		if ($check_existance) return true;

		$data = $data->body;

		if (!$data) return false;

		if ($format == 'xml') {
			try {
				return new SimpleXMLElement($data);
			} catch(Exception $e) {
				die(debug($data));
				//echo 'Caught exception: ',  $e->getMessage(), "\n";
			}
		}

		return $data;

	}

}

?>