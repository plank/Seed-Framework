<?php




class TestModel extends Model  {
	
	
}

class NewsModel extends Model {
	
	function setup() {
		$this->belongs_to('user');	
		$this->has_and_belongs_to_many('category');
	}
	
}

class CategoryModel extends Model {
	
	function setup() {
		$this->has_and_belongs_to_many('news');
	}
	
	
}

class UserModel extends Model {

	function setup() {
		$this->has_many('news');
	}
	
}

class ModelTester extends UnitTestCase {
	
	/**
	 * @var DB
	 */
	var $db;

	function setup() {
		if (SKIP_DB_TESTS) return;
		
		$this->db = DB::get_db();
		
		setup_db($this->db);

	}
	
	/**
	 * Test the creation of all the various models
	 */
	function test_creation() {
		if (SKIP_DB_TESTS) return;
		
		$model = new TestModel($this->db);
		$this->assertNoErrors();
		
		$model = new NewsModel($this->db);
		$this->assertNoErrors();
		
		$model = new CategoryModel($this->db);
		$this->assertNoErrors();
		
		$model = new UserModel($this->db);
		$this->assertNoErrors();		
	}
	
	
	
	function test_find_ids() {
		if (SKIP_DB_TESTS) return;

		// result should be model #3
		$model = TestModel::find('3');
		$this->assertEqual($model->get('name'), 'three');		
		
		// result should be an iterator with a single result, #1
		$results = TestModel::find(array(1));
		$this->assertIsA($results, 'Iterator');
		$model = $results->next();
		$this->assertEqual($model->get('name'), 'one');
		$this->assertFalse($results->has_next());
		
		// result should be an iterator containing #1, #4, and #5
		$results = TestModel::find(1, 4, 5);
		$this->assertIsA($results, 'Iterator');

		$this->assertEqual($results->size(), 3);
		
		$model = $results->next();
		$this->assertEqual($model->get('name'), 'one');

		$model = $results->next();
		$this->assertEqual($model->get('name'), 'four');

		$model = $results->next();
		$this->assertEqual($model->get('name'), 'five');
		$this->assertFalse($results->has_next());
		
		
	}	
	
	function test_find_first() {
		if (SKIP_DB_TESTS) return;
		
		$model = TestModel::find('first');
		$this->assertEqual($model->get('name'), 'one');
		
		$model = TestModel::find('first', array('order'=>'id desc'));
		$this->assertEqual($model->get('name'), 'five');
		
		
	}
	
	function test_find_by() {
		if (SKIP_DB_TESTS) return;
		
		$model = TestModel::find_by('name', 'one');	
		$this->assertEqual($model->get('name'), 'one');
		
		$model = TestModel::find_by('name', 'five');
		$this->assertEqual($model->get('name'), 'five');
		
	}

	function test_find_all_by() {
		if (SKIP_DB_TESTS) return;	
		
		// find all the news with the user_id of 1
		$results = NewsModel::find_all_by('user_id', 1);
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 1');
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 2');
		$this->assertFalse($results->has_next());
		
		// same thing, but ordered backwards
		$results = NewsModel::find_all_by('user_id', 1, array('order' => 'id DESC'));
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 2');
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 1');
		$this->assertFalse($results->has_next());		
		
		// this should raise an error
		$this->assertError(NewsModel::find_all_by('user_id', 1, 2));
		
	}
	
	
	function test_belongs_to() {
		if (SKIP_DB_TESTS) return;
		
		$model = NewsModel::find(1);
		$this->assertEqual($model->get('title'), 'Article 1');
		
		$user = $model->get('user');
		$this->assertEqual($user->get('username'), 'admin');
		
	}
	
	function test_has_many() {
		if (SKIP_DB_TESTS) return;
		
		$model = UserModel::find(1);
		$this->assertEqual($model->get('username'), 'admin');
		
		$results = $model->get('news');
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 1');
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 2');
		$this->assertFalse($results->has_next());

		$model = UserModel::find(2);
		$this->assertEqual($model->get('username'), 'author');
		
		$results = $model->get('news');		

		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 3');
		$this->assertFalse($results->has_next());
	}

	function test_has_and_belongs_to_many() {
		if (SKIP_DB_TESTS) return;
		
		// get all the categories for item one
		$model = NewsModel::find(1);	
		
		$results = $model->get('category');
		
		$category = $results->next();
		$this->assertEqual($category->get('name'), 'category 1');
		
		$category = $results->next();
		$this->assertEqual($category->get('name'), 'category 2');
		$this->assertFalse($results->has_next());
		
		// get all the categories for item two
		$model = NewsModel::find(2);	
		
		$results = $model->get('category');
		
		$category = $results->next();
		$this->assertEqual($category->get('name'), 'category 2');
		
		$category = $results->next();
		$this->assertEqual($category->get('name'), 'category 3');
		$this->assertFalse($results->has_next());		
		
		// get all the categories for item 3
		$model = NewsModel::find(3);	
		
		$results = $model->get('category');
		
		$category = $results->next();
		$this->assertEqual($category->get('name'), 'category 3');
		
		$category = $results->next();
		$this->assertEqual($category->get('name'), 'category 4');
		$this->assertFalse($results->has_next());		

		// get all the news items for category 2
		$category = CategoryModel::find(2);
		$this->assertEqual($category->get('name'), 'category 2');
		
		$results = $category->get('news');
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 1');
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 2');
		$this->assertFalse($results->has_next());
		
		
	}
	
}


?>