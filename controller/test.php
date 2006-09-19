<?php

require_once('../testing/support.php');

$test = &new SeedGroupTest('Controller');

$test->add_component('controller');	

$test->run(new HtmlReporter());


?>