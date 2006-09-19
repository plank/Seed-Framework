<?php


seed_include('library/iterator');
seed_include('library/inflector');
seed_include('db');


if (SEED_PHP_VERSION == 4 && (defined('SEED_MODEL_VERSION') && SEED_MODEL_VERSION == 1)) {
	require_once('model_versions/version1.php');
	
} else {
	require_once('model_versions/version2.php');

}


?>