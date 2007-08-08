<?php


class MyspaceScraperTester extends UnitTestCase {

	/**
	 * Myspace scraper object
	 *
	 * @var MyspaceScraper
	 */
	var $scraper;

	function setup() {
		$this->scraper = new MyspaceScraper();
	}

	function test_page_scrape() {
		$this->assertTrue($this->scraper->load_file(dirname(__FILE__).'/pages/myspace_mateomurphy.html'));

		$this->assertEqual($this->scraper->name, 'Mateo Murphy');
		$this->assertEqual($this->scraper->genre, 'Techno / House / Electro');
		$this->assertEqual($this->scraper->headline, 'Slave to the rhythm');

		$this->assertEqual($this->scraper->member_since, '04/04/2005');
		$this->assertEqual($this->scraper->band_website, 'http://www.mateomurphy.com');
		$this->assertEqual($this->scraper->band_members, 'I\'m an army of one');
		$this->assertEqual($this->scraper->influences, 'See friends list...');
		$this->assertEqual($this->scraper->sounds_like, 'Minimal Electro-Trance and Acid Tech-Breaks');
		$this->assertEqual($this->scraper->record_label, 'Underwater, Tronic, Turbo, Ascend, & others...');
		$this->assertEqual($this->scraper->type_of_label, 'Indie');
	}

}


?>