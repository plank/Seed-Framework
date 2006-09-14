<?php

require_once('../testing/support.php');

$test = &new SeedGroupTest('Library');

$test->add_component('library');	

$test->run(new HtmlReporter());


?>