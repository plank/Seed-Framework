<?php

require_once('../testing/support.php');

$test = &new SeedGroupTest('Cache');

$test->add_component('cache');	

$test->run(new HtmlReporter());


?>