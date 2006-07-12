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
	 * @var Controller
	 */
	var $controller;
	
	/**
	 * @var Paginator
	 */
	var $paginator;
	
	/**
	 * @var Page
	 */
	var $current_page;
	
	/**
	 * @var string
	 */
	var $seperator = " ";

	/**
	 * @var string
	 */
	var $previous_page_text = "&laquo;";
	
	/**
	 * @var string
	 */
	var $next_page_text = "&raquo;";
	
	/**
	 * @var int
	 */
	var $end_size = 2;
	
	/**
	 * @var int
	 */
	var $padding = 1;
	
	/**
	 * @var int
	 */
	var $fill_gap_size = 2;
	
	/**
	 * @var int
	 */
	var $num_pages;
	
	/**
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
	
	function link_to($page_number, $text = null) {
		
		if (is_null($text)) {
			$text = $page_number;	
		}
		
		return $this->controller->link_to($text, null, array('page'=>$page_number), null);	
		
	}
	
	function previous_page_link($first_page, $page) {
		if ($first_page) {
			return "<span>".$this->previous_page_text."</span>";
		} else {
			return $this->link_to($page, $this->previous_page_text);
		}		
	}
	
	function next_page_link($last_page, $page) {
		if ($last_page) {
			return "<span>".$this->next_page_text."</span>";
		} else {
			return $this->link_to($page, $this->next_page_text);
		}
	}

	/**
	 * Add the start and end pages
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
	 * return int
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
	 * return int
	 */
	function high_page() {
		$high_page = $this->current_page->number + $this->padding;
		
		if ($high_page > $this->num_pages - $this->end_size) {
			$high_page = $this->num_pages - $this->end_size;
		}

		return $high_page;	
	}
	
	function add_current_pages() {
		for($x = $this->low_page(); $x <= $this->high_page(); $x++) {
			$this->pages[$x] = $x;
		}

	}
	
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
	
	
	function convert_to_links($pages, $current_page) {
		ksort($pages);
		
		$last_page = 0;
		
		$return = $this->seperator;
		
		foreach($pages as $page) {
			
			if ($last_page && $page > $last_page + 1) {
				$return .= "&hellip;".$this->seperator;
			}
			
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