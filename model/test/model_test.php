<?php


class TestFinder extends Finder {
	
	
}

class TestModel extends Model  {
	
	
}

class NewsFinder extends Finder {
	
	
}

class NewsModel extends Model {
	
	function setup() {
		$this->belongs_to('user');	
		$this->has_and_belongs_to_many('category');
		$this->has_many('tag', array('as'=>'taggable'));
	}
	
}

class CategoryFinder extends Finder {
	
	
}

class CategoryModel extends Model {
	
	function setup() {
		$this->has_and_belongs_to_many('news');
	}
	
	
}

class UserFinder extends Finder {
	

}

class UserModel extends Model {

	function setup() {
		$this->has_many('news');
		$this->has_many('tag', array('as'=>'taggable'));		
	}
	
}

class TagFinder extends Finder {
	
	
}

class TagModel extends Model {
	
	function setup() {
		$this->belongs_to('taggable', array('polymorphic'=>true));	
	}	
	
}

class ModelTester extends UnitTestCase {
	
	/**
	 * @var DB
	 */
	var $db;

	function setup() {
		if (SKIP_DB_TESTS) return;
		
		$this->db = DB::factory('mysql', DB_HOST, DB_USER, DB_PASS, DB_NAME);
		
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

		$test_finder = new TestFinder($this->db);
		
		// result should be model #3
		$model = $test_finder->find('3');
		$this->assertEqual($model->get('name'), 'three');		
		
		// result should be an iterator with a single result, #1
		$results = $test_finder->find(array(1));
		$this->assertIsA($results, 'SeedIterator');
		$model = $results->next();
		$this->assertEqual($model->get('name'), 'one');
		$this->assertFalse($results->has_next());
		
		// result should be an iterator containing #1, #4, and #5
		$results = $test_finder->find(1, 4, 5);
		$this->assertIsA($results, 'SeedIterator');

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

		$test_finder = new TestFinder($this->db);		
		
		$model = $test_finder->find('first');
		$this->assertEqual($model->get('name'), 'one');
		
		$model = $test_finder->find('first', array('order'=>'id desc'));
		$this->assertEqual($model->get('name'), 'five');
		
		
	}
	
	function test_find_by() {
		if (SKIP_DB_TESTS) return;

		$test_finder = new TestFinder($this->db);		
		
		$model = $test_finder->find_by('name', 'one');	
		$this->assertEqual($model->get('name'), 'one');
		
		$model = $test_finder->find_by('name', 'five');
		$this->assertEqual($model->get('name'), 'five');
		
	}

	function test_find_all_by() {
		if (SKIP_DB_TESTS) return;	
		
		$news_finder = new NewsFinder($this->db);
		
		// find all the news with the user_id of 1
		$results = $news_finder->find_all_by('user_id', 1);
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 1');
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 2');
		$this->assertFalse($results->has_next());
		
		// same thing, but ordered backwards
		$results = $news_finder->find_all_by('user_id', 1, array('order' => 'id DESC'));
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 2');
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 1');
		$this->assertFalse($results->has_next());		
		
		// this should raise an error
		$this->assertError($news_finder->find_all_by('user_id', 1, 2));
		
	}
	

	function test_belongs_to() {
		if (SKIP_DB_TESTS) return;
		
		$news_finder = new NewsFinder($this->db);
		
		$model = $news_finder->find(1);
		$this->assertEqual($model->get('title'), 'Article 1');
		
		$user = $model->get('user');
		$this->assertEqual($user->get('username'), 'admin');
		
	}

	function test_has_many() {
		if (SKIP_DB_TESTS) return;
		
		$finder = new UserFinder($this->db);
		
		$model = $finder->find(1);
		$this->assertEqual($model->get('username'), 'admin');
		
		$results = $model->get('news');
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 1');
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 2');
		$this->assertFalse($results->has_next());

		$model = $finder->find(2);
		$this->assertEqual($model->get('username'), 'author');
		
		$results = $model->get('news');		

		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 3');
		$this->assertFalse($results->has_next());
	}

	function test_has_and_belongs_to_many() {
		if (SKIP_DB_TESTS) return;
		
		$finder = new NewsFinder($this->db);
		
		// get all the categories for item one
		$model = $finder->find(1);	
		
		$results = $model->get('category');
		
		$category = $results->next();
		$this->assertEqual($category->get('name'), 'category 1');
		
		$category = $results->next();
		$this->assertEqual($category->get('name'), 'category 2');
		$this->assertFalse($results->has_next());
		
		// get all the categories for item two
		$model = $finder->find(2);	
		
		$results = $model->get('category');
		
		$category = $results->next();
		$this->assertEqual($category->get('name'), 'category 2');
		
		$category = $results->next();
		$this->assertEqual($category->get('name'), 'category 3');
		$this->assertFalse($results->has_next());		
		
		// get all the categories for item 3
		$model = $finder->find(3);	
		
		$results = $model->get('category');
		
		$category = $results->next();
		$this->assertEqual($category->get('name'), 'category 3');
		
		$category = $results->next();
		$this->assertEqual($category->get('name'), 'category 4');
		$this->assertFalse($results->has_next());		

		$finder = new CategoryFinder($this->db);
		
		// get all the news items for category 2
		$category = $finder->find(2);
		$this->assertEqual($category->get('name'), 'category 2');
		
		$results = $category->get('news');
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 1');
		
		$news = $results->next();
		$this->assertEqual($news->get('title'), 'Article 2');
		$this->assertFalse($results->has_next());
		
		
	}
	

	
	function test_polymorphic_has_many() {
		if (SKIP_DB_TESTS) return;
		
		$finder = new NewsFinder($this->db);
		
		$news = $finder->find(1);
		$tags = $news->get('tag');
		
		$tag = $tags->next();
		$this->assertIsA($tag, 'TagModel');
		$this->assertEqual($tag->get('name'), 'Article 1, Tag 1');

		$tag = $tags->next();
		$this->assertIsA($tag, 'TagModel');
		$this->assertEqual($tag->get('name'), 'Article 1, Tag 2');
		
		$this->assertFalse($tags->next());
		
		$finder = new UserFinder($this->db);
		
		$user = $finder->find(1);
		$tags = $user->get('tag');
		
		$tag = $tags->next();
		$this->assertIsA($tag, 'TagModel');
		$this->assertEqual($tag->get('name'), 'Admin Tag');

		$this->assertFalse($tags->next());
	}
	
	function test_polymorphic_belongs_to() {
		if (SKIP_DB_TESTS) return;
		
		$finder = new TagFinder($this->db);
		
		// 1st tag
		$tag = $finder->find(1);
		$this->assertEqual($tag->get('name'), 'Article 1, Tag 1');

		$news = $tag->get('taggable');
		
		$this->assertIsA($news, 'NewsModel');
		$this->assertEqual($news->get('title'), 'Article 1');

		// 2nd tag
		$tag = $finder->find(2);
		$this->assertEqual($tag->get('name'), 'Article 1, Tag 2');

		$news = $tag->get('taggable');
		
		$this->assertIsA($news, 'NewsModel');
		$this->assertEqual($news->get('title'), 'Article 1');

		// 3rd tag
		$tag = $finder->find(3);
		$this->assertEqual($tag->get('name'), 'Article 2, Tag 1');

		$news = $tag->get('taggable');
		
		$this->assertIsA($news, 'NewsModel');
		$this->assertEqual($news->get('title'), 'Article 2');

		// 4th tag
		$tag = $finder->find(4);
		$this->assertEqual($tag->get('name'), 'Article 2, Tag 2');

		$news = $tag->get('taggable');
		
		$this->assertIsA($news, 'NewsModel');
		$this->assertEqual($news->get('title'), 'Article 2');
		
		// 5th tag
		$tag = $finder->find(5);
		$this->assertEqual($tag->get('name'), 'Admin Tag');

		$news = $tag->get('taggable');
		
		$this->assertIsA($news, 'UserModel');
		$this->assertEqual($news->get('username'), 'admin');

		// 6th tag
		$tag = $finder->find(6);
		$this->assertEqual($tag->get('name'), 'Author Tag');

		$news = $tag->get('taggable');
		
		$this->assertIsA($news, 'UserModel');
		$this->assertEqual($news->get('username'), 'author');		
	}
	

}


?>