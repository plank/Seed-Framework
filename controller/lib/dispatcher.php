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

		// try to start the session
		if (!Dispatcher::start_session()) {
			return false;	
		}
		
		$request = new Request();
		$response = new Response();
		
		$router = new Router();
		$router->load_config();
		
		$controller = $router->map($request);
		
		if (is_a($controller, 'Controller')) {
			$response = $controller->process($request, $response);
		} else {
			trigger_error('Mapper returned an empty controller, make sure your controller extends the controller base class', E_USER_WARNING);
			return false;
		}
		
		if (is_a($response, 'Response')) {
			$response->out($request->method);
			return true;
		}
	}
	
	/**
	 * Creates or resumes a session.
	 *
	 * @return bool  True if the sessions was succesfully started, false if not
	 */
	function start_session() {
		// bail if output was already started
		if (headers_sent($file, $line)) {
			trigger_error("Session could not be started because output was started in '$file' on line $line", E_USER_WARNING);
			return false;
		}
		
		// make sure session id is valid
		if (isset($_REQUEST[session_name()])) {
			$session_id = $_REQUEST[session_name()];
			
			if (!preg_match('/^[a-zA-Z0-9]*$/', $session_id)) {
				trigger_error("Invalid session id, aborting");
				return false;	
			}
			
		}
		
		session_start();
		
		return true;
		
	}
	
}
?>