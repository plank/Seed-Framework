<?php
/**
 * Scaffolding.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package controller
 */

/**
 * Scaffolding provides default actions to the controller. These actions
 * will render scaffold templates if user defined templates aren't found
 *
 * @package controller
 */
class Scaffolding {
	
	/**
	 * Reference to the parent controller object
	 *
	 * @var Controller
	 */
	var $controller;
	
	/**
	 * @return string
	 */
	function get_type() {
		return $this->controller->get_type();	
	}
	
	/**
	 * Default index method
	 *
	 */
	function index() {
		$type = $this->get_type();
		
		$finder = Finder::factory($type);
		
		$options = array();
		
		if (isset($finder->model->deleted_field)) {
			$options['conditions'] = $finder->model->deleted_field.' = 0';
		
		}		
		
		if (isset($this->controller->params['sortby']) && isset($this->controller->params['sortdir'])) {
			$options['order'] = $this->controller->params['sortby']." ".$this->controller->params['sortdir'];	

		} else if (isset($this->controller->default_sort)) {
			$options['order'] = $this->controller->default_sort;
			
		}
		
		$current_page = assign($this->controller->params['page'], 1);
		
		$this->controller->template->pages = new Paginator($this->controller, $finder->count(), 20, $current_page);
		
		$current_page = $this->controller->template->pages->get_current_page();
		
		list($options['limit'], $options['offset']) = $current_page->to_sql();
		
		$result = $finder->find('all', $options);
		
		$this->controller->template->table = & Table::factory($this->get_type(), $result, $this->controller);

		$this->render_scaffold('index');

	}
	
	/**
	 * Default view method
	 *
	 * @return bool
	 */
	function view() {
		$id = assign($this->controller->params['id'], 0);
		
		if ($id) {
			$type = $this->get_type();
			$finder = Finder::factory($type);
			$model = $finder->find($id);
			
			$this->controller->template->$type = $model;
		} else {
			trigger_error('no id', E_USER_WARNING);
		}
		
		$this->render_scaffold('view');
		
	}
	
	/**
	 * Default edit method
	 *
	 * @return bool
	 */
	function edit() {
		$id = assign($this->controller->params['id'], 0);
		
		if ($id) {
			$type = $this->get_type();
			$finder = Finder::factory($type);
			$model = $finder->find($id);
			
			$this->controller->template->$type = $model;
			
			$this->controller->template->form = Form::factory($type, $model);
			$this->controller->template->form->action = $this->controller->url_for(array('action'=>'update'));
			
		} else {
			trigger_error('no id', E_USER_WARNING);
			
		}
		
		$this->render_scaffold('edit');
		
	}
	
	/**
	 * Default add method
	 *
	 */
	function add() {
		$this->controller->template->form = Form::factory($this->get_type());
		$this->controller->template->form->action = $this->controller->url_for(array('action'=>'insert'));
		
		$this->render_scaffold('add');
	}
	
	/**
	 * Default delete method
	 *
	 */
	function delete() {
		if (!isset($this->controller->params['id'])) {
			trigger_error('No id for delete', E_USER_WARNING);
		} else {
			$id = $this->controller->params['id'];
			$finder = Finder::factory($this->get_type());
			$model = $finder->find($id);
			$model->delete();
			
			$this->controller->flash->next('flag', 'result: the item was successfully deleted');						
		}
		
		$this->controller->redirect(array('action'=>'index'));

	}
	
	/**
	 * Default insert method
	 *
	 */
	function insert() {
		$errors = false;
				
		if (!isset($this->controller->params['cancel'])) {		
			$model = Model::factory($this->get_type());
			$model->assign($this->controller->request->post);
			if (count($this->controller->request->files)) {
				$uploader = new Uploader($model);
				
				if ($errors = $uploader->Handle_uploads($this->controller->request->files)) {
					$this->controller->flash->next('error',	$errors);
				}
			}
			
			$model->insert();
			
			if ($errors) {
				$this->controller->flash->next('flag', 'result: the item was created but errors occured');	
			} else {
				$this->controller->flash->next('flag', 'result: the item was successfully created');			
			}
		}
		
		$this->controller->redirect(array('action'=>'index'));

	}
	
	/**
	 * Default update method
	 *
	 */
	function update() {
		$errors = false;
		
		if (!isset($this->controller->params['id'])) {
			trigger_error('No id for update', E_USER_WARNING);
			
		} elseif (!isset($this->controller->params['cancel'])) {
			$id = $this->controller->params['id'];
			
			$finder = Finder::factory($this->get_type());
			$model = $finder->find($id);
			$model->assign($this->controller->request->post);
			
			if (count($this->controller->request->files)) {
				$uploader = new Uploader($model);
				
				if ($errors = $uploader->Handle_uploads($this->controller->request->files)) {
					$this->controller->flash->next('error',	$errors);
				}
			}
			
			$model->update();
			
			if ($errors) {
				$this->controller->flash->next('flag', 'result: the item was updated but errors occured');	
			} else {
				$this->controller->flash->next('flag', 'result: the item was successfully updated');			
			}

			
		}

		$this->controller->redirect(array('action'=>'index'));
		
	}

	/**
	 * Attempts to render a user template, and if one isn't found, renders a scaffold template
	 */
	function render_scaffold($action) {
		if (file_exists($this->controller->get_template_name($action))) {
			$this->controller->render();	
		} else {
			$scaffold_template = FRAMEWORK_TEMPLATE_PATH."scaffolding/$action.php";
			
			if (file_exists($scaffold_template)) {
				$this->controller->render($scaffold_template);
			} else {
				trigger_error("No scaffold template for '$action' found in '$scaffold_template'", E_USER_ERROR);
			}
			
		}
	}
	
}

?>