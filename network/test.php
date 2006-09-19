<?php

require_once('../testing/support.php');

$test = &new SeedGroupTest('Network');

$test->add_component('network');	

$test->run(new HtmlReporter());


?>