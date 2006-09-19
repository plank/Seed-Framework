<?php

require_once('../testing/support.php');

$test = &new SeedGroupTest('Feed');

$test->add_component('feed');	

$test->run(new HtmlReporter());


?>