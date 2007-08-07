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
 * Extends the model class
 *
 * For some reason require_once isn't working in some cases...
 */
class_exists('model') || require('model.php');

/**
 * A model that acts as a list
 *
 * @package model
 */
class ListModel extends Model {

	/**
	 * The field representing the objects position in a list
	 *
	 * @var string
	 */
	var $position_field = 'position';

	var $scope_condition = '1 = 1';

	/**
	 *
	 *
	 * @return string
	 */
	function scope_condition() {
		return $this->scope_condition;

	}

	function insert() {

		if ($this->position_field) {
			$this->add_to_list_bottom();
		}

		return parent::insert();

	}

	function destroy() {
		if ($this->position_field) {
			$this->decrement_position_on_lower_items();
		}


		return parent::destroy();

	}

	/**
	 * Returns true if the item is in a list
	 *
	 * return bool
	 */
	function in_list() {
		return (isset($this->position_field) && $this->position_field && $this->data[$this->position_field]);

	}

	function remove_from_list() {
		return $this->decrement_position_on_lower_items();
	}

	/**
	 * Moves the item up in the list by swapping its position with
	 * the one above it
	 *
	 */
	function move_higher() {
		if (!$this->in_list()) {
			trigger_error('Move higher called on an item not in a list', E_USER_WARNING);
			return false;
		}

		$item = $this->higher_item();

		if (!$item) return false;

		$item->increment_position();
		$this->decrement_position();
	}

	/**
	 * Moves the item down in the list by swapping its position with
	 * the one below it
	 *
	 */
	function move_lower() {
		if (!$this->in_list()) {
			trigger_error('Move lower called on an item not in a list', E_USER_WARNING);
			return false;
		}

		$item = $this->lower_item();

		if (!$item) return false;

		$this->increment_position();
		$item->decrement_position();
	}

	/**
	 * Increments the position of the current item, moving it down in the list
	 *
	 * @return bool
	 */
	function increment_position() {
		$this->data[$this->position_field] ++;
		return $this->save();
	}

	/**
	 * Sets the position value of the object to be after the last item
	 *
	 */
	function add_to_list_bottom() {
		$this->data[$this->position_field] = $this->bottom_position_in_list() + 1;
	}

	/**
	 * Decrements the position of the current item, moving it up in the list
	 *
	 * @return bool
	 */
	function decrement_position() {
		$this->data[$this->position_field] --;
		$this->save();
	}

	/**
	 * Retrieves the item that is higher in the list
	 *
	 * @return model
	 */
	function higher_item() {
		$finder = $this->finder();
		$item = $finder->find('first', array('conditions'=>$this->position_field." = ".($this->data[$this->position_field] - 1)." AND ".$this->scope_condition()));
		return $item;
	}

	/**
	 * Retrieves the item that is lower in the list
	 *
	 * @return model
	 */
	function lower_item() {
		$finder = $this->finder();
		$item = $finder->find('first', array('conditions'=>$this->position_field." = ".($this->data[$this->position_field] + 1)." AND ".$this->scope_condition()));
		return $item;

	}

	/**
	 * Get the position value of the last item in the collection
	 *
	 * @return int
	 */
	function bottom_position_in_list() {
		$sql = "SELECT $this->position_field FROM $this->table WHERE ".$this->scope_condition()." ORDER BY $this->position_field DESC LIMIT 1";
		return $this->db->query_value($sql);
	}

	/**
	 * Moves the items below the current item one position
	 *
	 * @return bool
	 */
	function decrement_position_on_lower_items() {
		if ($this->in_list()) {
			$finder = $this->finder();

			return $finder->update_all("$this->position_field = $this->position_field - 1", $this->position_field." = ".($this->data[$this->position_field] + 1)." AND ".$this->scope_condition());
		} else {
			return false;
		}
	}

}

?>