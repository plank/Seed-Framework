<?php
/**
 * SeedSpider.php part of the seed framework
 *
 */


/**
 * Web Crawler
 *
 * @todo  :	Pages with javascript redirects don't get followed
 *			Basehref for documents must be taken into account
 */
class SeedSpider {

	/**
	 * @var DB
	 */
	var $db;
	
	/**
	 * The name of the table containing the search results
	 *
	 * @var string
	 */
	var $table_name = 'page';
	
	/**
	 * Extensions to ignore
	 * 
	 * @var array
	 */
	var $ignore_extensions = array('gif', 'jpg', 'jpeg', 'png', 'css', 'js', 'pdf');
	
	/**
	 * Query string values to include as part of the url, set these to be the parts that represent unique pages
	 *
	 * @var array
	 */
	var $included_query_string = false;
	
	/**
	 * Enable content type checking (slow)
	 *
	 * @var bool
	 */
	var $check_content_type = false;
	
	/**
	 * Contents types to consider valid
	 *
	 * @var array
	 */
	var $include_content_types = array('text/html');
	
	/**
	 * Sets the maximum number of pages to crawl. Any value that evaluates to false will result in no limit
	 *
	 * @var int
	 */
	var $crawl_limit = 100;
	
	/**
	 * If set to true, ignore all domains that are not contained in the search domains
	 *
	 */
	var $ignore_external_domains = true;
	
	/**
	 * Search domains
	 *
	 */
	var $search_domains = array();
	
	/**
	 * The number of pages currently crawled
	 *
	 * @var int
	 */
	var $crawled_pages;
	
	/**
	 * Constructor
	 *
	 * @param $db DB
	 * @return SeedSpider
	 */
	function SeedSpider($db) {
		$this->db = $db;
		
	}
	
	function reset() {
		$this->db->truncate_table($this->table_name);		
	}
	
	/**
	 * Starts the crawling process
	 *
	 * @param mixed $urls A string or an array of strings of URLS to crawl
	 * @return array An array containing the crawled urls
	 */
	function start($urls) {
		$this->crawled_pages = 0;

		if (!is_array($this->search_domains)) {
			$this->search_domains = array($this->search_domains);	
		}
		
		if (!is_array($urls)) {
			$urls = array($urls);	
		}
		
		foreach ($urls as $url) {
			$this->add_page($url);
		}
		
		while($page = $this->crawl_next()) {
			$results[] = $page->url->to_string();
			set_time_limit(0);	
			
		}
		
		return $results;
		
	}
	
	
	/**
	 * Retrieves the first uncrawled page in the database and indexes it
	 *
	 * @return WebPage
	 */
	function crawl_next() {
		
		$this->crawled_pages ++;
				
		$page = new WebPage();
		
		$next_url = $this->get_next_url();
		
		// if we don't have a next url, it means they've all been crawled
		if (!$next_url) {
			return false;	
		}
		
		$page->open($next_url);
	
		// debug($page->url->to_string(), $page->response_code, $page->headers);
		
		$this->update_page($page);
		
		foreach ($page->extract_links() as $linked_url) {
			if ($this->validate_url($linked_url)) {
				
				//debug($linked_url->to_string($this->included_query_string, false));
				
				$this->add_page($linked_url->to_string($this->included_query_string, false));
			}
		}
		
		if ($this->crawl_limit && $this->crawled_pages >= $this->crawl_limit) {
			return false;
				
		} else {
			return $page;
			
		}
		
		
	}
	
	
	/**
	 * Validates a given url to see if it should be crawled or not
	 *
	 * @param URL $url
	 * @return bool
	 */
	function validate_url($url) {
		$url_string = $url->to_string($this->included_query_string, false);
		//debug($url_string);
		
		// if the url is empty, discard
		if (!$url_string) {
			return false;	
		}
		
		// check for ignored file types
		if ($url->extension && in_array($url->extension, $this->ignore_extensions)) {
			return false;	
		}
		
		// ignore urls whose hosts are not in our search domains
		if ($this->ignore_external_domains && !in_array($url->host, $this->search_domains)) {
			return false;	
		}
		
		// check to see if the page already exists in our list
		if ($this->page_exists($url_string)) {
			return false;	
		}
		
		// skip content checks if set to true
		if (!$this->check_content_type) {
			return true;	
		}
		
		// check to see if we're accepting the document's content type		
		$page = new WebPage();
		$page->open($url, true);
		
		list($content_type) = explode(';', $page->headers['Content-Type'], 2);
		
		if (!in_array($content_type, $this->include_content_types)) {
			debug("ignoring content type $content_type");
			return false;	
		}
		
		// if we've passed all the test, we're valid
		return true;
		
	}
	
	/**
	 * Checks the database to see if a given page exists
	 * 
	 * @param string $url
	 * @return bool
	 */
	function page_exists($url) {
		// check to see if this one's been crawled before
		$sql = "SELECT COUNT(*) FROM ".$this->table_name." WHERE url = '".$url."'";
		
		return $this->db->query_value($sql) && true;		
		
	}
	
	function add_page($url) {
		return $this->db->insert_query($this->table_name, array('url'=>$url));		
		
	}
	
	function update_page($page) {
		return $this->db->update_query(
			$this->table_name, 
			"url = '".$page->url->to_string()."'", 
			array(
				'title'=>$page->get_title(), 
				'html'=>$page->get_html_contents(), 
				'text'=>$page->get_plain_text()
			), 
			array(
				'crawled' => 1,
				'crawled_at' => 'NOW()'
			)
		);		
	}
	
	function get_next_url() {
		$sql = "SELECT url FROM ".$this->table_name." WHERE crawled = 0 LIMIT 1";	
		return $this->db->query_value($sql);
	}
	
}

?>