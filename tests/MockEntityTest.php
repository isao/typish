<?php
use library\Type\Entity
  , library\Type\MockEntity
  , library\Type\MockException1Entity
  , library\Type\MockException2Entity;

require_once __DIR__.'/MockEntity.php';

/**
 * test object constructor, getters, setters
 */
class MockEntityTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$_SERVER['REQUEST_TIME'] = 1274213448;
	}

	public function tearDown()
	{
		unset($_SERVER['REQUEST_TIME']);
	}

	public function testDefaultsSetWithEmptyConstructor()
	{
		$M = new MockEntity;

		//default property values and sanitizations
		$this->assertSame(0, $M->id);
		$this->assertSame('', $M->name);
		$this->assertSame(0, $M->is_ok);
		$this->assertSame(0, $M->score);
		$this->assertNull($M->stooge);
		$this->assertSame($_SERVER['REQUEST_TIME'], $M->created);
		$this->assertSame($_SERVER['REQUEST_TIME'], $M->updated);

		$expected = array(
			'id' => '',
			'stooge' => MockEntity::STOOGE_ERRMSG
		);
		$this->assertEquals($expected, $M->errors());
	}

	public function testConstructor()
	{
		$data = array(
			'id' => '44',
			'name' => 'bubba',
			'is_ok' => 'true',
			'score' => '88',
			'stooge' => 'larry',
			'created' => 1274213000,
			'updated' => null,
		);

		$expected = array(
			'id' => 44,
			'name' => 'bubba',
			'is_ok' => 1,
			'score' => '88',// <- no sanitizer, so it's not cast to int
			'stooge' => 'larry',
			'created' => 1274213000,
			'updated' => $_SERVER['REQUEST_TIME'],		
		);

		$M = new MockEntity($data);

		foreach($data as $key => $val) {
			$this->assertSame($expected[$key], $M->$key);
		}

		//no errors
		$this->assertFalse((bool) $M->errors());
		$this->assertSame(array(), $M->errors());
	}

	public function testConstructorTooFewProperties()
	{
		$data = array(
			#'id' => '44',
			'name' => 'bubba',
			'is_ok' => 'true',
			#'score' => '88',
			'stooge' => 'larry',
			#'created' => 1274213000,
			#'updated' => null,
		);

		$expected = array(
			'id' => 0,//default
			'name' => 'bubba',
			'is_ok' => 1,
			'score' => 0,//default
			'stooge' => 'larry',
			'created' => $_SERVER['REQUEST_TIME'],//via santizer
			'updated' => $_SERVER['REQUEST_TIME'],//via santizer
		);

		$M = new MockEntity($data);

		foreach($data as $key => $val) {
			$this->assertSame($expected[$key], $M->$key);
		}

		//id is invalid, must be greater than 1
		//test this in a few ways to test
		$this->assertTrue((bool) $M->errorsMatching('id'));
		$this->assertFalse((bool) $M->errorsExcept('id'));
		$this->assertSame(array('id' => ''), $M->errorsMatching('id'));
		$this->assertSame(array(), $M->errorsExcept('id'));
	}

	public function testConstructorIgnoresExtraProperties()
	{
		$data = array(
			'id' => '44',
			'name' => 'bubba',
			'is_ok' => 'true',
			'score' => '88',
			'stooge' => 'larry',
			'created' => 1274213000,
			'updated' => null,
		);
		
		$extra_data = array(
			'foo' => rand(),
			'bar' => rand(),
			'baz' => rand(),
		);

		$expected = array(
			'id' => 44,
			'name' => 'bubba',
			'is_ok' => 1,
			'score' => '88',// <- no sanitizer, so it's not cast to int
			'stooge' => 'larry',
			'created' => 1274213000,
			'updated' => $_SERVER['REQUEST_TIME'],		
		);

		$M = new MockEntity($data + $extra_data);

		foreach($data as $key => $val) {
			$this->assertSame($expected[$key], $M->$key);
		}

		foreach($extra_data as $key => $val) {
			$this->assertFalse(isset($M->$key));
		}

		//no errors
		$this->assertFalse((bool) $M->errors());
		$this->assertSame(array(), $M->errors());
	}

	public function testBatchSetViaConstructorAfterInstantiation()
	{
		$data = array(
			'id' => '44',
			'name' => 'bubba',
			'is_ok' => 'true',
			'score' => '88',
			'stooge' => 'larry',
			'created' => 1274213000,
			'updated' => null,
		);
		
		$new_data = array(
			'id' => 48,
			'name' => 'Mr. Bubba',
			'extra_property' => 'foobarbaz',
		);

		$expected = array(
			'id' => 48,
			'name' => 'Mr. Bubba',
			'is_ok' => 1,
			'score' => '88',// <- no sanitizer, so it's not cast to int
			'stooge' => 'larry',
			'created' => 1274213000,
			'updated' => $_SERVER['REQUEST_TIME'],		
		);

		$M = new MockEntity($data);
		$M->__construct($new_data);

		foreach($data as $key => $val) {
			$this->assertSame($expected[$key], $M->$key);
		}

		//extraneous properties are ignored in the constructor
		$this->assertFalse(isset($M->extra_property));

		//no errors
		$this->assertFalse((bool) $M->errors());
		$this->assertSame(array(), $M->errors());
	}

	public function testEntityIsIterable()
	{
		$data = array(
			'id' => '44',
			'name' => 'bubba',
			'is_ok' => 'true',
			'score' => '88',
			'stooge' => 'larry',
			'created' => 1274213000,
			'updated' => null,
		);

		$expected = array(
			'id' => 44,
			'name' => 'bubba',
			'is_ok' => 1,
			'score' => '88',// <- no sanitizer, so it's not cast to int
			'stooge' => 'larry',
			'created' => 1274213000,
			'updated' => $_SERVER['REQUEST_TIME'],		
		);

		$M = new MockEntity($data);

		foreach($M as $key => $val) {
			$this->assertSame($expected[$key], $M->$key);
			$this->assertSame($expected[$key], $val);
		}

		//no errors
		$this->assertFalse((bool) $M->errors());
		$this->assertSame(array(), $M->errors());
	}

	public function testEntityAsArray()
	{
		$data = array(
			'id' => '44',
			'name' => 'bubba',
			'is_ok' => 'true',
			'score' => '88',
			'stooge' => 'larry',
			'created' => 1274213000,
			'updated' => null,
		);

		$expected = array(
			'id' => 44,
			'name' => 'bubba',
			'is_ok' => 1,
			'score' => '88',// <- no sanitizer, so it's not cast to int
			'stooge' => 'larry',
			'created' => 1274213000,
			'updated' => $_SERVER['REQUEST_TIME'],		
		);

		$M = new MockEntity($data);

		//no errors
		$this->assertSame($expected, $M->getArray());
	}
	
	public function testGetPropertyNames()
	{
		$expected = array(
			'id', 'name', 'is_ok', 'score', 'stooge', 'created', 'updated',
		);
		$this->assertSame($expected, MockEntity::getPropertyNames());
	}

	public function testSetAndUnsetError()
	{
		$data = array(
			'id' => '44',
			'name' => 'bubba',
			'is_ok' => 'true',
			'score' => '88',
			'stooge' => 'larry',
			'created' => 1274213000,
			'updated' => null,
		);

		$M = new MockEntity($data);

		//no errors
		$this->assertSame(array(), $M->errors());

		//invalid id, must be >= 1
		$M->id = null;
		$expected_errors = array('id' =>'');
		$this->assertTrue((bool) $M->errorsMatching('id'));
		$this->assertEquals($expected_errors, $M->errors());
		
		//invalid stooge
		$M->stooge = 'carrottop';
		
		//now we have 2 errors
		$this->assertTrue((bool) $M->errorsMatching('id', 'stooge'));

		$expected_errors = array('id'=>'', 'stooge'=>MockEntity::STOOGE_ERRMSG);
		$this->assertEquals($expected_errors, $M->errors());

		//valid id, unset id error
		$M->id = 46;
		$this->assertFalse((bool) $M->errorsMatching('id'));

		//still have stooge error
		$this->assertTrue((bool) $M->errorsMatching('stooge'));
		
		//apply a valid stooge
		$M->stooge = 'moe';
		//no stooge error
		$this->assertFalse((bool) $M->errorsMatching('stooge'));
		
		//no errors at all actually
		$expected_errors = array();
		$this->assertEquals($expected_errors, $M->errors());
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testSetException()
	{
		$M = new MockEntity;
		$M->UNKNOWN_PROP = "99";
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testGetException()
	{
		$M = new MockEntity;
		$a = $M->UNKNOWN_PROP;
	}
	
	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testPropsCaseSensitive()
	{
		$M = new MockEntity;
		$M->ID = "99";
	}

	public function testIssetIsFalseForNullValuesJustLikePhpFunction()
	{
		$M = new MockEntity;
		$this->assertNull($M->stooge);
		$this->assertFalse(isset($M->stooge));
	}

	/**
	 * @expectedException BadMethodCallException
	 */
	public function testCallbackException1()
	{
		$M = new MockException1Entity;
	}

	/**
	 * @expectedException BadMethodCallException
	 */
	public function testCallbackException2()
	{
		$M = new MockException2Entity;
	}

	/**
	 * let's say validations would normally pass, but there's still something else
	 * wrong. for example, inserting data to a database is not possible because 
	 * some contraint gets violated. specify your own error.
	 */
	public function testExogeneousError()
	{
		$data = array(
			'id' => '44',
			'name' => 'bubba',
			'is_ok' => 'true',
			'score' => '88',
			'stooge' => 'larry',
			'created' => 1274213000,
			'updated' => null,
		);

		$M = new MockEntity($data);

		//no errors
		$this->assertFalse((bool) $M->errors());
		$this->assertSame(array(), $M->errors());

		//let's say we try to store this in a db, but name "bubba" already exists
		//and db has a uniqueness constraint
		$errmsg = 'name must be unique';
		$M->setExternalError($errmsg);
		
		//now we have special non-validation error
		$expected = array(MockEntity::EXOGENEOUS_ERRKEY => $errmsg);
		$this->assertTrue((bool) $M->errors());
		$this->assertSame($expected, $M->errors());
		
		//undo
		$M->unsetExternalError();
		$this->assertFalse((bool) $M->errors());
		$this->assertSame(array(), $M->errors());
	}
}