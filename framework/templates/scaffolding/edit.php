<div id='page_header'>
<div id="page_header_links"><?php print $this->link_to('Back to list', array('action'=>'index')); ?></div>
<h1>Editing <?php print Inflector::humanize($this->controller->get_type()) ?></h1>
</div>

<div id='page_body'>
<?php print $this->form->generate(); ?>
</div>