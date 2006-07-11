<?php
/**
 * dispatcher.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package controller
 */


/**
 * Dispatches requests to the appropriate action controller
 *
 * @package controller
 */
class Dispatcher {

	/**
	 * Dispatches the request
	 */
	
	function dispatch() {
		if (!headers_sent($file, $line)) {
			session_start();
		} else {
			trigger_error("Session could not be started because output was started in '$file' on line $line", E_USER_WARNING);
			return false;
		}
		
		$request = new Request();
		$response = new Response();
		
		$controller = Router::map($request);
		
		if (is_a($controller, 'Controller')) {
			$response = $controller->process($request, $response);
		} else {
			trigger_error('Mapper returned an empty controller, make sure your controller extends the controller base class', E_USER_WARNING);
			return false;
		}
		
		if (is_a($response, 'Response')) {
			$response->out();
			return true;
		}
	}
}