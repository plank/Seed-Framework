<?php

/**
 * Make some assumptions about where things are
 *
 */
define('SEEDTEST_SEED_PATH', dirname(dirname(__FILE__)).'/');
define('SEEDTEST_SIMPLETEST_PATH', dirname(dirname(dirname(__FILE__))).'/simpletest/');
define('SEEDTEST_CONFIG_PATH', dirname(dirname(dirname(__FILE__))).'/seed_config/tests.php');

/**
 * Include all file neccesary for running unit tests
 *
 * Simple test framework is expected to be in same directory as framework
 */
require_once(SEEDTEST_SEED_PATH.'seed.php');
require_once(SEEDTEST_SIMPLETEST_PATH.'unit_tester.php');
require_once(SEEDTEST_SIMPLETEST_PATH.'reporter.php');
require_once(SEEDTEST_SIMPLETEST_PATH.'mock_objects.php');


class SeedGroupTest extends GroupTest {

	function SeedGroupTest($label = false) {
		parent::GroupTest($label);
		$this->load_config();	
		
	}
	
	/**
	 * Attempt to load config file
	 */	
	function load_config() {
		if (!file_exists(SEEDTEST_CONFIG_PATH)) {
			die("Config file for tests not found in '".SEEDTEST_CONFIG_PATH."', please create it");
		}
		
		require_once(SEEDTEST_CONFIG_PATH);		
		
	}
	
	
	/**
	 * test the given component
	 *
	 * @param string $name
	 */
	function add_component($name) {
		seed_include($name);
		
		$path = SEEDTEST_SEED_PATH.'/'.$name.'/test/';
		
		if (file_exists($path.'support.php')) {
			require_once($path.'support.php');	
		}
		
		// include all classes
		$dir = dir($path);
		
		while(($file = $dir->read()) !== false ) {
			if (substr($file, 0, 1) == '.') {
				continue;
			}
			
			if (is_file($path.$file) && substr($file, -9) == '_test.php') {
				$this->addTestFile($path.$file);
			}
			
		}	
		
		return true;
	}

}

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
	
	
	$db->drop_table('tag', true);
	
	$db->query("CREATE TABLE `tag` (
		  `id` int(11) unsigned NOT NULL auto_increment,
		  `name` varchar(255) NOT NULL default '',
		  `taggable_type` varchar(255) NOT NULL default '',
		  `taggable_id` int(11) unsigned NOT NULL default '0',
		  PRIMARY KEY  (`id`),
		  KEY `taggable_index` (`taggable_type`,`taggable_id`)
		) TYPE=MyISAM AUTO_INCREMENT=7 ;"
	);

	$db->query("INSERT INTO `tag` VALUES (1, 'Article 1, Tag 1', 'news', 1);");
	$db->query("INSERT INTO `tag` VALUES (2, 'Article 1, Tag 2', 'news', 1);");
	$db->query("INSERT INTO `tag` VALUES (3, 'Article 2, Tag 1', 'news', 2);");
	$db->query("INSERT INTO `tag` VALUES (4, 'Article 2, Tag 2', 'news', 2);");
	$db->query("INSERT INTO `tag` VALUES (5, 'Admin Tag', 'user', 1);");
	$db->query("INSERT INTO `tag` VALUES (6, 'Author Tag', 'user', 2);");

	
}

?>