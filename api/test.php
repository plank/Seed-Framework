<?php

require_once('../testing/support.php');

$test = &new SeedGroupTest('API');

$test->add_component('api');

$test->run(new HtmlReporter());


?>