<?php

/**
 * Usage examples:
 *
 *	$this->connect('blog/',
 * 		array('controller'=>'blog', 'action'=>'index'));
 *
 * Will connect the url 'blog/' to the index action of the blog controller
 *
 *	$this->connect('blog/$year/$month/$day', 
 *		array('controller'=>'blog', 'action'=>'show_date', 'month'=>null, 'day'=>null), 
 *		array('year'=>'/^(19|20)\d\d$/', 'month'=>'/^[01]?\d$/', 'day'=>'/^[0-3]?\d$/')
 *	);
 *
 * Will connect urls like 'blog/2004/06/30' to the show_date action of the blog controller, with the year month and day
 * contained in the relevant variable. The default values for month and day means that those values are optional.
 * The third argument contains regex that the values captured are compared to. If any of the regexes fail, the route is
 * not followed.
 *
 *	$this->router->connect('blog/show/$id',
 *		array('controller'=>'blog', 'action'=>'show'),
 *		array('id'=>'/\d+/')
 *	);
 *
 * Will connect urls like 'blog/show/3' to the show action of the blog controller. Again, the id is validate to make sure
 * it's numeric.
 *	
 *	$this->router->connect('blog/$controller/$action/$id');
 *
 * Will connect any other url starting with blog/ to the captured controller, action and id; 'blog/comment/show/1' will
 * call the show action of the comment controller, with an id of 1. When no defaults a given, a default of 
 * array('action' => 'index', 'id' => null) is used.
 *	
 *	$this->router->connect('*anything',
 *		array('controller'=>'blog', 'action'=>'unknown_request')
 *	);
 *
 * Will connect any url to the unknown_request action of the blog controller. The * operator captures everything in and after it into an array
 * e.g. the url 'foo/bar/bat' will result in array('controller' => 'blog', 'action' => 'unknown_request', 'anything' => array('foo', 'bar', 'bat))
 */

$this->connect('$controller/$action/$id');

?>