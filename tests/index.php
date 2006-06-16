<?php

// DB settings
define('SKIP_DB_TESTS', true);		// set this to true to skip tests require the database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'wilmag');
define('DB_NAME', 'unit_tests');

define('FRAMEWORK_TESTS_PATH', dirname(__FILE__).'/');

require_once('../framework.php');

// test framework is expected to be in same directory as framework
require_once('../../simpletest/unit_tester.php');
require_once('../../simpletest/reporter.php');

$seed = new Seed();
$seed->include_libraries();

if (!SKIP_DB_TESTS) {
	db::register('default', 'mysql');
}


$test = &new GroupTest('All tests');

foreach($seed->subfolders as $subfolder) {
	if (SKIP_DB_TESTS && $subfolder == 'model') {
		continue;	
	
	}
	
	$path = FRAMEWORK_TESTS_PATH.$subfolder.'/';
	// include all classes
	$dir = dir($path);
	
	while(($file = $dir->read()) !== false ) {
		if (substr($file, 0, 1) == '.') {
			continue;
		}
		
		if (is_file($path.$file) && substr($file, -9) == '_test.php') {
			$test->addTestFile($path.$file);
		}
		
	}
}


$test->run(new HtmlReporter());

/**
 * Setup the dbs with test data for tests that require it
 *
 * @param DB $db
 */
function setup_db($db) {
	
	// basic test table
	$db->drop_table('test', true);
	
	$db->query("CREATE TABLE `test` (
		`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
		`name` VARCHAR( 255 ) NOT NULL ,
		PRIMARY KEY ( `id` )
		);"
	);
	
	$db->query("INSERT INTO `test` (`name`) VALUES ('one');");
	$db->query("INSERT INTO `test` (`name`) VALUES ('two');");
	$db->query("INSERT INTO `test` (`name`) VALUES ('three');");
	$db->query("INSERT INTO `test` (`name`) VALUES ('four');");
	$db->query("INSERT INTO `test` (`name`) VALUES ('five');");
	
	// news table
	$db->drop_table('news', true);
	
	$db->query("CREATE TABLE `news` (
		`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
		`date` DATE NOT NULL ,
		`title` VARCHAR( 255 ) NOT NULL ,
		`text` TEXT NOT NULL ,
		`user_id` INT( 11 ) UNSIGNED NOT NULL ,
		PRIMARY KEY ( `id` )
		);"
	);
	
	$db->query("INSERT INTO `news` ( `id` , `date` , `title` , `text` , `user_id` ) VALUES ('', '2006-04-19', 'Article 1', 'Some text', '1');");
	$db->query("INSERT INTO `news` ( `id` , `date` , `title` , `text` , `user_id` ) VALUES ('', '2006-04-25', 'Article 2', 'Some more text', '1');");
	$db->query("INSERT INTO `news` ( `id` , `date` , `title` , `text` , `user_id` ) VALUES ('', '2006-05-10', 'Article 3', 'Again some text', '2');");
	
	// users table
	$db->drop_table('user', true);
	
	$db->query("CREATE TABLE `user` (
		`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
		`username` VARCHAR( 255 ) NOT NULL ,
		`password` VARCHAR( 255 ) NOT NULL ,
		`user_id` INT( 11 ) UNSIGNED NOT NULL ,
		PRIMARY KEY ( `id` )
		);"
	);
	
	$db->query("INSERT INTO `user` ( `id` , `username` , `password` , `user_id` ) VALUES ('', 'admin', 'admin', '1'), ('', 'author', 'author', '2');");

	// category table
	$db->drop_table('category', true);
	
	$db->query("CREATE TABLE `category` (
		`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
		`name` VARCHAR( 255 ) NOT NULL ,
		PRIMARY KEY ( `id` )
		);"
	);

	$db->query("INSERT INTO `category` ( `id` , `name` ) VALUES ('', 'category 1'), ('', 'category 2');");
	$db->query("INSERT INTO `category` ( `id` , `name` ) VALUES ('', 'category 3'), ('', 'category 4');");
	
	// category_news join table
	$db->drop_table('category_news', true);
	
	$db->query("CREATE TABLE `category_news` (
		`category_id` TINYINT( 11 ) UNSIGNED NOT NULL ,
		`news_id` TINYINT( 11 ) UNSIGNED NOT NULL
		);"
	);
	
	$db->query("ALTER TABLE `category_news` ADD INDEX `index` ( `category_id` , `news_id` );");
	
	$db->query("INSERT INTO `category_news` ( `category_id` , `news_id` ) VALUES ('1', '1'), ('2', '1');");
	$db->query("INSERT INTO `category_news` ( `category_id` , `news_id` ) VALUES ('2', '2'), ('3', '2');");
	$db->query("INSERT INTO `category_news` ( `category_id` , `news_id` ) VALUES ('3', '3'), ('4', '3');");
}


?>