<?php
/**
 * part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package view
 * @subpackage html
 */

/**
 * Class for generating paging links
 *
 * @package view
 * @subpackage html
 */

class PaginationView {
	
	/**
	 * Reference to the parent controller, used for generating the links
	 * 
	 * @var Controller
	 */
	var $controller;
	
	/**
	 * Reference to the paginator to get data from
	 * 
	 * @var Paginator
	 */
	var $paginator;
	
	/**
	 * Reference to the current page
	 * 
	 * @var Page
	 */
	var $current_page;
	
	/**
	 * String to use to seperate between page links
	 * 
	 * @var string
	 */
	var $seperator = " ";

	/**
	 * String to use for link to previous page
	 * 
	 * @var string
	 */
	var $previous_page_text = "&laquo; Previous";
	
	/**
	 * String to use for link to next page
	 * 
	 * @var string
	 */
	var $next_page_text = "Next &raquo;";
	
	/**
	 * Number of pages to always display at begining and end of total pages
	 * 
	 * @var int
	 */
	var $end_size = 3;
	
	/**
	 * Number of pages to display as padding around current page
	 * 
	 * @var int
	 */
	var $padding = 1;
	
	/**
	 * Threshold for gap filling
	 * 
	 * @var int
	 */
	var $fill_gap_size = 2;
	
	/**
	 * Total number of pages
	 * 
	 * @var int
	 */
	var $num_pages;
	
	/**
	 * The of pages being generated
	 * 
	 * @var array
	 */
	var $pages;
	
	/**
	 * Constructor
	 *
	 * @param Controller $controller
	 * @param Paginator $paginator
	 * @return PaginationView
	 */
	function PaginationView(& $controller, & $paginator) {
		$this->controller = & $controller;
		$this->paginator = & $paginator;
		$this->current_page = $this->paginator->get_current_page();
		$this->num_pages = $this->paginator->page_count();		
		
	}
	
	/**
	 * Returns a string containing a link to the given page number
	 *
	 * @param int $page_number
	 * @param text $text
	 * @return string
	 */
	function link_to($page_number, $text = null) {
		
		if (is_null($text)) {
			$text = $page_number;	
		}
		
		$params['page'] = $page_number;
		return $this->controller->link_to($text, null, $params, null);	
		
	}
	
	/**
	 * Returns a link to go to the previous page
	 *
	 * @param bool $first_page
	 * @param int $page
	 * @return string
	 */
	function previous_page_link($first_page, $page) {
		if ($first_page) {
			return "<span>".$this->previous_page_text."</span>";
		} else {
			return $this->link_to($page, $this->previous_page_text);
		}		
	}
	
	/**
	 * Returns a link to go to the next page
	 *
	 * @param bool $last_page
	 * @param int $page
	 * @return string
	 */
	function next_page_link($last_page, $page) {
		if ($last_page) {
			return "<span>".$this->next_page_text."</span>";
		} else {
			return $this->link_to($page, $this->next_page_text);
		}
	}

	/**
	 * Add the start and end pages, based on the desired end_size
	 */
	function add_end_pages() {
		for($x = 1; $x <= $this->end_size && $x < $this->num_pages; $x ++) {
			$this->pages[$x] = $x;
		}	
		
		for($x = $this->num_pages; $x > $this->num_pages - $this->end_size && $x > 1; $x--) {
			$this->pages[$x] = $x;
		}		
		
	}
	
	/**
	 * Returns the low page of the current page range, not including the start pages
	 *
	 * @return int
	 */
	function low_page() {
		$low_page = $this->current_page->number - $this->padding;
		
		if ($low_page < $this->end_size + 1) {
			$low_page = $this->end_size + 1;	
		}

		return $low_page;	
	}
	
	/**
	 * Returns the high page of the current page range, not including the start pages
	 *	
	 * @return int
	 */
	function high_page() {
		$high_page = $this->current_page->number + $this->padding;
		
		if ($high_page > $this->num_pages - $this->end_size) {
			$high_page = $this->num_pages - $this->end_size;
		}

		return $high_page;	
	}
	
	/**
	 * Adds the current page and the pages in the padding window to the pages array
	 *
	 */
	function add_current_pages() {
		for($x = $this->low_page(); $x <= $this->high_page(); $x++) {
			$this->pages[$x] = $x;
		}

	}
	
	/**
	 * Fills gaps in the pages array 
	 *
	 */
	function fill_gaps() {
		if (!$this->fill_gap_size || !$this->end_size) {
			return;	
		}
		
		// fill small gaps before range
		if ($this->low_page() <= $this->end_size + $this->fill_gap_size + 1) {
			for($x = $this->end_size + 1; $x < $this->low_page() ;$x++) {
				$this->pages[$x] = $x;
			}
		} 
		
		// fill small gaps after range
		if ($this->high_page() >= $this->num_pages - $this->end_size - $this->fill_gap_size) {
			for($x = $this->num_pages - $this->end_size; $x > $this->high_page() ;$x--) {
				$this->pages[$x] = $x;
			}			
		} 
		
		if ($this->fill_gap_size >= $this->num_pages - $this->end_size * 2) {
			for($x = $this->end_size + 1; $x <= $this->num_pages - $this->end_size; $x++) {
				$this->pages[$x] = $x;
			}
		}
		
	}
	
	/**
	 * Generate pagination links
	 *
	 * @return string
	 */
	function generate() {
		if ($this->num_pages == 1) {
			return "";
		}
		
		$this->pages = array();
	
		$this->add_end_pages();

		$this->add_current_pages();

		$this->fill_gaps();		
		
		// add page forward and backwards links
		$return = $this->previous_page_link($this->current_page->number == 1, $this->current_page->number - 1);
		
		$return .= $this->convert_to_links($this->pages, $this->current_page->number);
		
		$return .= $this->next_page_link($this->current_page->number == $this->num_pages, $this->current_page->number + 1);
	
		return $return;
	}	
	
	/**
	 * Convert the pages array to a string of links
	 *
	 * @param array $pages
	 * @param int $current_page
	 * @return string
	 */
	function convert_to_links($pages, $current_page) {
		ksort($pages);
		
		$last_page = 0;
		
		$return = $this->seperator;
		
		foreach($pages as $page) {
			// place ellipsis in gaps
			if ($last_page && $page > $last_page + 1) {
				$return .= "&hellip;".$this->seperator;
			}
			
			// return the current page as a simple span, others as links
			if ($page == $current_page) {
				$return .= "<span>".$page."</span>";
			} else {
				$return .= $this->link_to($page);
			}
			
			$return .= $this->seperator;
			
			$last_page = $page;

		}		
		
		return $return;
		
	}
	
	
}


?>