<?php

require_once('../testing/support.php');

$test = &new SeedGroupTest('XML');

$test->add_component('xml');	

$test->run(new HtmlReporter());


?>