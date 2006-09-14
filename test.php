<?php

require_once('testing/support.php');

$test = &new SeedGroupTest('All tests');

// defined in config file
if (!SKIP_DB_TESTS) {
	seed_include('db');
	
	db::register('default', 'mysql');
}


$components = array('controller', 'feed', 'library', 'network', 'view', 'xml');

if (!SKIP_DB_TESTS) {
	$components[] = 'db';
	$components[] = 'model';	
	
}

foreach($components as $component) {
	$test->add_component($component);	

}

$test->run(new HtmlReporter());

?>