<?php

require_once('../testing/support.php');

$test = &new SeedGroupTest('Model');

// seed_include('db');


$test->add_component('model');	

db::register('default', 'mysql');

$test->run(new HtmlReporter());


?>