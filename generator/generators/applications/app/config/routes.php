<?php


Route::connect('', array('controller'=>'news', 'action'=>'index'));
Route::connect('admin/$controller/$action/$id', array('module'=>'admin', 'controller'=>'news', 'action'=>'index', 'id'=>null));
Route::connect('$controller/$action/$id');

?>