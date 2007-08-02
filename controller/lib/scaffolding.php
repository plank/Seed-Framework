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
	 * Returns the name of the model to use
	 *
	 * @return string
	 */
	function get_model_type() {

		return $this->controller->model_name;
	}

	/**
	 * Default index method
	 *
	 */
	function index() {
		$type = $this->get_model_type();

		$finder = Finder::factory($type);

		$options = array('conditions' => $this->_list_conditions($finder));

		if (isset($this->controller->params['sortby']) && isset($this->controller->params['sortdir'])) {
			$options['order'] = $this->controller->params['sortby']." ".$this->controller->params['sortdir'];

		} else if (isset($this->controller->default_sort)) {
			$options['order'] = $this->controller->default_sort;

		}

		$current_page = assign($this->controller->params['page'], 1);

		$this->controller->template->pages = new Paginator($this->controller, $finder->count($options['conditions']), 20, $current_page);

		$current_page = $this->controller->template->pages->get_current_page();

		list($options['limit'], $options['offset']) = $current_page->to_sql();

		$result = $finder->find('all', $options);

		$this->controller->template->table = & Table::factory($this->get_type(), $result, $this->controller);

		$this->_render_scaffold('index');

	}

	/**
	 * Returns an SQL conditions string for the current request
	 *
	 * @param Finder $finder  Used to get field data
	 */
	function _list_conditions($finder) {

		$model = $finder->model;

		// belongs to association
		if (isset($this->controller->belongs_to)) {
			// this is likely not be the best default
			$this->id = assign($this->controller->params['id'], 1);

			if (isset($model->associations[$this->controller->belongs_to])) {
				$data = $model->associations[$this->controller->belongs_to];

				if ($data->type = 'belongs_to') {
					$conditions[] = $data->foreign_key_name().' = '.$this->controller->db->escape($this->id);
				}
			}
		}

		// add deleted field
		if (isset($model->deleted_field)) {
			$conditions[] = $finder->model->deleted_field.' = 0';
		}

		// add like condition
		if (isset($this->controller->params['like']) && $this->controller->params['like']) {
			$conditions[] = $finder->like_condition('%'.$this->controller->db->escape($this->controller->params['like']).'%');
		}

		if (isset($this->controller->params['search'])) {
			$search = $this->controller->params['search'];

			if (is_string($search)) {
				$search = unserialize($search);
			}

			foreach ($search as $field => $value) {
				$column = assign($model->columns[$field], false);

				if (!$column) {
					continue;
				}

				$conditions[] = $column->search_condition($value);

			}
		}

		// compile conditions
		if (isset($conditions)) {
			$conditions = '('.implode(') AND (', $conditions).')';
		} else {
			$conditions = '1 = 1';
		}

		return $conditions;

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
			$finder = Finder::factory($this->get_model_type());
			$model = $finder->find($id);

			$this->controller->template->$type = $model;

			$this->controller->template->form = Form::factory($type, $model, $this->controller);

			if (isset($this->controller->has_many)) {
				$this->controller->template->table = Table::factory($this->controller->has_many, $model->get($this->controller->has_many), $this->controller);

			}

		} else {
			trigger_error('no id', E_USER_WARNING);
		}

		$this->_render_scaffold('view');

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
			$finder = Finder::factory($this->get_model_type());
			$model = $finder->find($id);

			$this->controller->template->$type = $model;

			$this->controller->template->form = Form::factory($this->get_type(), $model, $this->controller);
			$this->controller->template->form->action = $this->controller->url_for(null, array('action'=>'update'));
			$this->controller->template->form->translator = $this->controller->template->translator;

		} else {
			trigger_error('no id', E_USER_WARNING);

		}

		$this->_render_scaffold('edit');

	}

	/**
	 * Default add method
	 *
	 */
	function add() {

		$id = assign($this->controller->params['id'], 0);
		$type = $this->get_type();
		$model = Model::factory($this->get_model_type());

		// if we have an id and we've defined a belongs_to, assign that ID to it
		if ($id && isset($this->controller->belongs_to) && isset($model->associations[$this->controller->belongs_to])) {
			$data = $model->associations[$this->controller->belongs_to];

			if ($data->type = 'belongs_to') {
				$model->set($data->foreign_key_name(), $id);
			}
		}

		$this->controller->template->form = Form::factory($type, $model, $this->controller);
		$this->controller->template->form->action = $this->controller->url_for(null, array('action'=>'insert'));

		$this->_render_scaffold('add');

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
			$finder = Finder::factory($this->get_model_type());
			$model = $finder->find($id);
			$model->delete();

			$this->controller->flash->next('flag', 'result: the item was successfully deleted');
		}

		$this->_redirect_back($model);

	}

	/**
	 * Default insert method
	 *
	 */
	function insert() {
		$errors = false;

		$model = Model::factory($this->get_model_type());
		$model->assign($this->controller->request->post);

		if (!isset($this->controller->params['cancel'])) {

			if (count($this->controller->request->files)) {
				$uploader = new Uploader($model);

				if ($errors = $uploader->Handle_uploads($this->controller->request->files)) {
					$this->controller->flash->next('error',	$errors);
				}
			}

			$model->save();

			if ($errors) {
				$this->controller->flash->next('flag', 'result: the item was created but errors occurred');
			} else {
				$this->controller->flash->next('flag', 'result: the item was successfully created');
			}
		}

		$this->_redirect_back($model);

	}

	/**
	 * Default update method
	 *
	 */
	function update() {
		$errors = false;

		if (!isset($this->controller->params['id'])) {
			trigger_error('No id for update', E_USER_WARNING);

		}

		$id = $this->controller->params['id'];
		$finder = Finder::factory($this->get_model_type());
		$model = $finder->find($id);
		$model->assign($this->controller->request->post);

		if (!isset($this->controller->params['cancel'])) {
			if (count($this->controller->request->files)) {
				$uploader = new Uploader($model);

				if ($errors = $uploader->Handle_uploads($this->controller->request->files)) {
					$this->controller->flash->next('error',	$errors);
				}
			}

			$model->save();

			if ($errors) {
				$this->controller->flash->next('flag', 'result: the item was updated but errors occured');
			} else {
				$this->controller->flash->next('flag', 'result: the item was successfully updated');
			}


		}

		$this->_redirect_back($model);

	}


	// redirects either to the index, or the parent model's view, as appropriate
	function _redirect_back($model = null) {

		$to_view = assign($this->controller->to_view, false);

		// going back to parent
		if (isset($this->controller->belongs_to) && !is_null($model)) {

			$belongs_to = $this->controller->belongs_to;

			if (isset($model->associations[$belongs_to]) && $model->associations[$belongs_to]->type = 'belongs_to') {
				$id = $model->get($model->associations[$belongs_to]->foreign_key_name());

			} else {
				$id = null;
			}

			if ($to_view) {
				return $this->controller->redirect(array('controller'=>$belongs_to, 'action'=>'view', 'id'=>$id));
			} else {
				return $this->controller->redirect(array('action'=>'index', 'id'=>$id));
			}

		} elseif ($to_view && isset($this->controller->has_many)) {
			return $this->controller->redirect(array('action'=>'view', 'id'=>$model->id));

		}

		return $this->controller->redirect(null, array('action'=>'index'));



	}

	/**
	 * Attempts to render a user template, and if one isn't found, renders a scaffold template
	 */
	function _render_scaffold($action) {
		if (file_exists($this->controller->get_template_name($action))) {
			$this->controller->render($this->controller->get_template_name($action));
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