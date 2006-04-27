<?php
/**
 * paginator.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */

/**
 * A class for creating paginations
 *
 * @package view
 */
class Paginator {
	
	/**
	 * Reference to controller
	 *
	 * @var Controller
	 */
	var $controller;
	
	/**
	 * Total number of items to paginate
	 *
	 * @var int
	 */
	var $item_count;
	
	/**
	 * Number of items per page
	 *
	 * @var int
	 */
	var $items_per_page;
	
	/**
	 * Number of the current page
	 *
	 * @var int
	 */
	var $current_page;
	
	/**
	 * Constructor
	 *
	 * @param Controller $controller
	 * @param int $item_count
	 * @param int $items_per_page
	 * @param int $current_page
	 * @return Paginator
	 */
	function Paginator($controller, $item_count, $items_per_page = 10, $current_page = 1) {
		$this->controller = $controller;
		$this->item_count = $item_count;
		$this->items_per_page = $items_per_page;
		$this->current_page = $current_page;
	}
	
	/**
	 * Sets the current page
	 *
	 * @param mixed $page
	 * @return bool
	 */
	function set_current_page($page) {
        if (is_object($page) && is_a($page, 'Page')) {
        	if ($page->paginator != $this) {
          		trigger_error('Page/Paginator mismatch');
          		return false;
        	}
        	
        	$page = $page->number;
        }
        
        if ($this->has_page_number($page)) {
        	$this->current_page = $page;
     	} else {
     		$page = 1;
     	}
     	
     	return true;
	}

    /**
     * Returns a Page object representing this paginator's current page.
     * 
     * @return Page
     */
    function get_current_page() {
        return $this->get($this->current_page);
	}
      
    /**
     * Returns a new Page representing the first page in this paginator.
     * 
     * @return Page
     */
    function first_page() {
        return $this->get(1);
	}
      
    /**
     * Returns a new Page representing the last page in this paginator.
     * 
     * @return Page
     */
	function last_page() {
		return $this->get($this->page_count());
	}
      
    /**
     * Returns the number of pages in this paginator.
     * 
     * @return int
     */
	function page_count() {
      	if ($this->item_count == 0) {
      		return 1;
      	}
     	
      	return ceil($this->item_count / $this->items_per_page);
        
	}
		
    /**
     * Returns true if this paginator contains the page of index $number.
     * 
     * @param int $number
     * @return bool
     */
	function has_page_number($number) {
        return ($number >= 1 and $number <= $this->page_count());
	}
	
	/**
	 * Returns a page object for the given page
	 *
	 * @param int $number
	 * @return Page
	 */
	function get($number) {
		return new Page($this, $number);
	}
}

/**
 * A class for representing pages
 *
 * @package view
 */
class Page {
	
	/**
	 * Reference to paginator
	 *
	 * @var Paginator
	 */
	var $paginator;
	
	/**
	 * Number of the current page
	 *
	 * @var unknown_type
	 */
	var $number;
	
	/**
	 * Constructor
	 *
	 * @param Paginator $paginator
	 * @param int $number
	 * @return Page
	 */
	function Page($paginator, $number) {
		$this->paginator = $paginator;
		
		if ($paginator->has_page_number($number)) {
			$this->number = $number;
		} else {
			$this->number = 1;
		}
	}
	
	/**
	 * Returns the offset value for the page
	 *
	 * @return int
	 */
	function offset() {
		return $this->limit() * ($this->number - 1);		
	}
	
	/**
	 * Returns the limit for the paginator
	 *
	 * @return int
	 */
	function limit() {
		return $this->paginator->items_per_page;
		
	}	
	
	/**
	 * Returns the number of items per page and the offset
	 *
	 * @return array
	 */
	function to_sql() {
		return array($this->limit(), $this->offset());
	}


	
}

?>