<?php
/**
 * part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package model
 */

/**
 * Extends model
 */
require_once('model.php');

/**
 * A model class for nested sets, which is a way of representing a hierarchy in a flat format
 * that allows rapid reads (one query) at the cost of slower writes
 *
 * @package model
 */
class NestedSetModel extends Model {
	var $parent_column = "parent_id";
	var $left_column = "lft";
	var $right_column = "rgt";
	var $level_column = "level";
	
	/**
	 * @return bool True if this node is the root of the tree, false if it isn't
	 */
	function is_root() {
		$parent_id = $this->get($this->parent_column);
		
		return ($parent_id == 0 && $this->get_left() == 1 && $this->get_right() > $this->get_left());
	}
	
	/**
	 * @return bool True if this node is a child in the true, false if it isn't
	 */	
	function is_child() {
		$parent_id = $this->get($this->parent_column);
		
		return ($parent_id != 0 && $this->get_left() > 1 && $this->get_right() > $this->get_left());
		
	}
	
	function has_children() {
		return ($this->get_left() < ($this->get_right() - 1));
		
	}
	
	/**
	 * Returns the parent id of the node
	 *
	 * @return int
	 */
	function parent_id() {
		return $this->data[$this->parent_column];	
	}
	
	/**
	 * @return int
	 */
	function get_left() {
		return $this->data[$this->left_column];
	}
	
	/**
	 * @param int $value
	 */
	function set_left($value) {
		$this->data[$this->left_column] = $value;
	}
	
	/**
	 * @return int
	 */
	function get_right() {
		return $this->data[$this->right_column];
	}
	
	/**
	 * @param int $value
	 */
	function set_right($value) {
		$this->data[$this->right_column] = $value;	
	}
	
	/**
	 * @return int
	 */
	function level() {
		return $this->data[$this->level_column];	
		
	}
	
	/**
	 * Checks to see if the given node is a sibling to this
	 *
	 * @param NestedSetModel $node The node to check
	 * @return bool True if the given node is a sibling
	 */
	function is_sibling($node) {
		$siblings = ($this->parent_id() == $node->parent_id());
		
		return $siblings;
		
	}
	
	/**
	 * Returns the node directly to the left of the current item
	 *
	 * @return NestedSetModel
	 */
	function get_left_sibling() {
		$condition = $this->right_column.' = '.($this->get_left() - 1);
		$finder = $this->finder();
		$sibling = $finder->find('first', array('conditions'=>$condition));
		
//		debug($condition, 'this', $this->dump_data(), 'left sibling', $sibling->dump_data());
		
		if (!($sibling && $this->is_direct_sibling($sibling))) {
			return false;	
		}
		
		return $sibling;
		
	}
	
	/**
	 * Returns the node directly to the right of the current item
	 *
	 * @return NestedSetModel
	 */
	function get_right_sibling() {
		$condition = $this->left_column.' = '.($this->get_right() + 1);
		$finder = $this->finder();
		$sibling = $finder->find('first', array('conditions'=>$condition));
		
//		debug($condition, 'this', $this->dump_data(), 'right sibling', $sibling->dump_data());		
		
		if (!($sibling && $this->is_direct_sibling($sibling))) {
			return false;	
		}
		
		return $sibling;		
		
	}
	
	/**
	 * Checks to see if the given node is a direct sibling to this, meanining its
	 * position is adjancent
	 *
	 * @param NestedSetModel $node The node to check
	 * @return bool True if the given node is a sibling
	 */	
	function is_direct_sibling($node) {
		return ($this->is_sibling($node) && ($this->get_left() == $node->get_right() + 1 || $this->get_right() == $node->get_left() - 1));	
	}
	
	/**
	 * Moves the current node to the left, within the same parent
	 */
	function move_up() {
		$sibling = $this->get_left_sibling();
		
		if ($sibling) {
			return $this->swap_siblings($sibling);	
		}
		
		return false;
	}

	/**
	 * Moves the current node to the right, within the same parent
	 */
	function move_down() {
		$sibling = $this->get_right_sibling();
		
		if ($sibling) {
			return $this->swap_siblings($sibling);	
		}
	
		return false;
	}
	
	/**
	 * Swaps the positions of the current node with an adjacent node
	 *
	 * @param NestedSetModel $node The node to swap
	 * @return bool True if the swap was succesful
	 */	
	function swap_siblings($node) {
		if (!$this->is_direct_sibling($node)) {
			return false;
		}
		
		// figure out which item is in which position		
		if ($this->get_left() < $node->get_left()) {
			$lower_item = $this;
			$upper_item = $node;
		} else {
			$lower_item = $node;
			$upper_item = $this;	
		}
		
		// make some size and bound calculations
		$dif = $lower_item->get_right() - $lower_item->get_left() + 1;
		
		$move_amount = $upper_item->get_right() - $lower_item->get_left() + 1;				
		
		$upper_bound = $upper_item->get_right();
		$lower_bound = $lower_item->get_left();

		$finder = $this->finder();
		
		// make the space after the higher item
		$finder->update_all(
			"$this->left_column = ($this->left_column + $dif), $this->right_column = ($this->right_column + $dif)",
			"$this->left_column >= $upper_bound"
		);
		
		// move the lower item and all its children after the higher item
		$finder->update_all(
			"$this->left_column = ($this->left_column + $move_amount), $this->right_column = ($this->right_column + $move_amount)", 
			"$this->left_column >= ".$lower_item->get_left()." AND $this->right_column <= ".$lower_item->get_right()
		);

		// remove the space left 
		$finder->update_all(
			"$this->left_column = ($this->left_column - $dif), $this->right_column = ($this->right_column - $dif)",
			"$this->left_column >= $lower_bound"
		);
///die();
		return true;	
	}

	
	
	/**
	 * Add a child element to the tree, which generally involves shifting stuff in the tree
	 *
	 * @param NestedSetModel $child The child to add to the tree
	 */ 
	function add_child(& $child) {
		// set the childs values
		$right_bound = $this->get_right();
		
		$child->set($this->parent_column, $this->id);
		$child->set($this->level_column, $this->get($this->level_column) + 1);
		$child->set_left($right_bound);
		$child->set_right($right_bound + 1);
		$this->set_right($right_bound + 2);
		
		$this->db->lock_table($this->table_name());
		
		$finder = $this->finder();
		
		// move stuff to the right in the database
		$finder->update_all(
			"$this->left_column = ($this->left_column + 2)", "$this->left_column >= $right_bound"
		);
		
		$finder->update_all(
			"$this->right_column = ($this->right_column + 2)", "$this->right_column >= $right_bound"
		);
		
		$this->save();
		$child->save();
		
		$this->db->unlock_tables();
		
	}
	
	/**
	 * Deletes the item and all its child nodes
	 */
	function delete() {
		$finder = $this->finder();
		
		if ($this->deleted_field) {
			$finder->update_all('deleted = 1', $this->left_column." > ".$this->get_left()." AND ".$this->right_column." < ".$this->get_right());
			
		} else {
			$dif = $this->get_right() - $this->get_left() + 1;
	
			// delete the children
			$finder->delete_all($this->left_column." > ".$this->get_left()." AND ".$this->right_column." < ".$this->get_right());
			
			// fill in the gap
			$finder->update_all("$this->left_column = ($this->left_column - $dif)", $this->left_column." >= ".$this->get_right());
			$finder->update_all("$this->right_column = ($this->right_column - $dif)", $this->right_column." >= ".$this->get_right());		

		}
			
		parent::delete();
	}
	
	/**
	 * Returns a set consisting of this node and all its children
	 *
	 * @return ModelIterator
	 */
	function full_set($conditions = '1') {
		$finder = $this->finder();
		return $finder->find('all', array('conditions' => "$conditions AND (".$this->left_column." >= ".$this->get_left().") and (".$this->right_column." <= ".$this->get_right().")", 'order'=>'lft ASC'));
	}
	
	/**
	 * Returns a set consisting of all this node's children
	 *
	 * @return ModelIterator
	 */
	function all_children($conditions = '1') {
		$finder = $this->finder();
		return $finder->find('all', array('conditions' => "$conditions AND (".$this->left_column." > ".$this->get_left().") and (".$this->right_column." < ".$this->get_right().")", 'order'=>'lft ASC'));
	}
	
	/**
	 * Returns a set consisting of all this node's immediate children
	 *
	 * @return ModelIterator
	 */
	function direct_children($conditions = '1') {
		$finder = $this->finder();
		return $finder->find('all', array('conditions' => $conditions." AND ".$this->parent_column." = ".$this->get_id(), 'order'=>'lft ASC'));
	}
	
	
}


?>