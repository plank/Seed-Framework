<div id="page_header">
<div id="page_header_links"><?php print $this->link_to('Add '.Inflector::humanize($this->controller->get_type()), array('action'=>'add')); ?></div>
<h1><?php print Inflector::humanize($this->controller->get_type()) ?> List</h1>
</div>

<div id="page_body">
<?php 

if ($this->table) {
	print $this->table->generate() ;
	
	print $this->pagination_links($this->pages);
	
} else {
	print "<p>No table defined</p>";
}

?>
</div>