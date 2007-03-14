<?php

require_once('../testing/support.php');

$test = &new SeedGroupTest('Error');

$test->add_component('error');	

$test->run(new HtmlReporter());


?>