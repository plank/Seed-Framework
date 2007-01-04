<?php

require_once('../testing/support.php');

$test = &new SeedGroupTest('CLI');

$test->add_component('cli');	

$test->run(new HtmlReporter());


?>