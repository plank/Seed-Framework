<?php

class TreeTest extends UnitTestCase {
	
	
	function test_construction() {
		$node = new TreeNode();
		$this->assertNoErrors();
		
	}
	
	function test_flatten_and_rebuild() {
		
		$node = new TreeNode();
		
		$child1 = new TreeNode();
		$child2 = new TreeNode();
		$child3 = new TreeNode();
		
		$child2->append_child($child3);
		
		$node->append_child($child1);
		$node->append_child($child2);
		$node->renumber();
		
		// flatten and rebuild the tree, make sure they're the same		
		$flattened = $node->flatten();
		$new_nodes = TreeNode::rebuild($flattened);
		
		$this->assertIdentical($node, $new_nodes);
		
	}
	
}

?>