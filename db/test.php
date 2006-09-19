<?php

require_once('../testing/support.php');

$test = &new SeedGroupTest('Database');

$test->add_component('db');	

$test->run(new HtmlReporter());


?>