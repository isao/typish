<?php
use library\Type\Entity
  , library\Type\MockEntityMapped;

require_once __DIR__.'/MockEntity.php';

/**
 * test sanitizer methods directly
 *
 */
class MockEntitySanitizersTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$_SERVER['REQUEST_TIME'] = 1274213448;
	}

	public function tearDown()
	{
		unset($_SERVER['REQUEST_TIME']);
	}

	public function testIntbool()
	{
		//input -> expected result
		$in = array(

			//true
			'true' => 1,
			true => 1,
			'ON' => 1,
			'Yes' => 1,
			'1' => 1,
			1 => 1,

			//false
			'anything else' => 0,
			'0' => 0,
			false => 0,
			'FALse' => 0,
			'' => 0,
			null => 0,
		);
	
		foreach($in as $val => $expected) {
			$this->assertSame($expected, Entity::intboolSanitize($val));
		}

		//other types, also false
		$this->assertSame(0, Entity::intboolSanitize(array()));
		$this->assertSame(0, Entity::intboolSanitize((object) array()));
	}

	/**
	 * FILTER_SANITIZE_NUMBER_FLOAT sucks: flags don't work like I expect and this
	 * behavior is f'ing whack
	 */
	public function testFloat()
	{
		$this->assertSame(1234.567, Entity::floatSanitize('1234.567'));
		$this->assertSame(1234.567, Entity::floatSanitize('1,234.567'));

		//wtf?
		$this->assertSame(1234.567, Entity::floatSanitize('12,34.56,7'));
		$this->assertSame(1234.567, Entity::floatSanitize('blah $1234.567¥ blah, blah'));
		$this->assertSame(1234.567, Entity::floatSanitize(' 12 34.5 67 '));
		$this->assertSame(1234.567, Entity::floatSanitize(" 1,2\n34.5\t67 "));
		
		//FYI: floatval returns <double:1>
		$this->assertNotEquals(1234.567, floatval(" 1,2\n34.5\t67 "));
	}

	public function testTimestampAutoUpdate()
	{
		//always returns current timestamp
		$this->assertSame(1274213448, Entity::timestampSanitize('1274213448'));
		$this->assertSame(1274213448, Entity::timestampSanitize(''));
		$this->assertSame(1274213448, Entity::timestampSanitize('0'));
		$this->assertSame(1274213448, Entity::timestampSanitize('1274219999'));
	}
	
	public function testTimestampDoNotUpdate()
	{
		//only return current time if value is not a valid timestamp
		$this->assertSame(1274213448, Entity::timestampSanitize(null, false));
		$this->assertSame(1274213448, Entity::timestampSanitize('', false));
		$this->assertSame(1274213448, Entity::timestampSanitize('0', false));
		$this->assertSame(1274213448, Entity::timestampSanitize(array(), false));
		
		//only type is changed
		$this->assertSame(1274299999, Entity::timestampSanitize(1274299999, false));
		$this->assertSame(1274299999, Entity::timestampSanitize('1274299999', false));		
		$this->assertSame(1274288888, Entity::timestampSanitize('1274288888', false));		
	}

	public function testClamp()
	{
		$actual = Entity::clampSanitize('222', 11, 22);
		$this->assertSame(22, $actual);

		$actual = Entity::clampSanitize('222', 11, 22.5);
		$this->assertSame(22, $actual);

		$actual = Entity::clampSanitize(10.1, 11.5, 22.5, 'float');
		$this->assertSame(11.5, $actual);

		$actual = Entity::clampSanitize(9999, 11.5, 22.5, 'float');
		$this->assertSame(22.5, $actual);

		$actual = Entity::clampSanitize(15.2, 11.5, 22.5, 'float');
		$this->assertSame(15.2, $actual);
		$this->assertInternalType('float', $actual);
		$this->assertNotInternalType('int', $actual);
	}
	
	public function testSanitzerRegex()
	{
		$actual = Entity::regexSanitize('foobarbar', '/bar/', 'baz');
		$this->assertSame('foobazbaz', $actual);

		$actual = Entity::regexSanitize('foobarbar', '/bar/', 'baz', 1);
		$this->assertSame('foobazbar', $actual);
	}
}