<?php

require_once('../testing/support.php');

$test = &new SeedGroupTest('View');

$test->add_component('view');	

$test->run(new HtmlReporter());


?>