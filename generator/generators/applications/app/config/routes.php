<?php


$this->connect('', array('controller'=>'news', 'action'=>'index'));
$this->connect('admin/$controller/$action/$id', array('module'=>'admin', 'controller'=>'news', 'action'=>'index', 'id'=>null));
$this->connect('$controller/$action/$id');

?>