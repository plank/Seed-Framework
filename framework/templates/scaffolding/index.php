<h1><?php print Inflector::humanize($this->controller->get_type()) ?> List</h1>
<p><?php print $this->link_to('Add '.Inflector::humanize($this->controller->get_type()), array('action'=>'add')); ?></p>
<?php 

if ($this->table) {
	print $this->table->generate() ;
	
	print $this->pagination_links($this->pages);
	
} else {
	print "<p>No table defined</p>";
}

?>