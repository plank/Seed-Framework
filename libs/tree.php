<?php
/**
 * tree.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */


/**
 * A class for representing tree nodes in a nested set model
 *
 * @package library
 */
class TreeNode {
	
	var $id;
	
	var $left_val;
	var $right_val;
	var $level;
	
	var $children;
	
	function TreeNode() {
		$this->children = array();
		
	}
	
	function append_child($node) {
		$this->children[] = $node;
		
	}
	
	function renumber($start_val = 1, $level = 1) {
		$this->left_val = $start_val ++;
		$this->level = $level ++;
		
		foreach($this->children as $key => $child) {
			$child = & $this->children[$key];
			
			$start_val = $child->renumber($start_val, $level);
			
			unset($child);
			
		}
		
		$this->right_val = $start_val ++;
		
		return $start_val;
		
	}
	
	/**
	 * Returns a one dimensional array of this node and all its children, ordered
	 * by the left val
	 *
	 * @return array
	 */
	
	function flatten() {
		
		$result = array();
		
		// flatten the children and collect the results
		foreach($this->children as $child) {
			$result = array_merge($result, $child->flatten());
			
		}
		
		// prepend this node (minus the children) to the results
		$current_node = $this;
		$current_node->children = array();
		array_unshift($result, $current_node);
				
		return $result;
	}
	
	/**
	 * Takes a flattened array of nodes and rebuilds the tree. It assumes
	 * that the left_vals are in ascending order.
	 *
	 * @static 
	 * @param array $nodes
	 */
	function rebuild($nodes) {
				
		$current_node = array_shift($nodes);
		
		$current_node->rebuild_children($nodes);	
		
		return $current_node;
	}

	function rebuild_children($nodes) {
		while($node = array_shift($nodes)) {
			// the node is a child
			if ($node->left_val < $this->right_val) {
				$nodes = $node->rebuild_children($nodes);
				$this->append_child($node);
			} else {
				array_unshift($nodes, $node);
				break;	
				
			}
			
		}
		
		return $nodes;
	}
	
}


?>