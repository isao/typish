<?php
namespace lib\Type;

use PHPUnit_Framework_TestCase;

require_once __DIR__.'/MockEntity.php';

/**
 * test validator methods directly
 *
 */
class MockEntityValidatorsTest extends PHPUnit_Framework_TestCase
{
	public function testNumberInRangeMinMax()
	{
		$this->assertTrue(Entity::numberInRangeValidate(1, 1, 100));
		$this->assertTrue(Entity::numberInRangeValidate(10, 1, 100));
		$this->assertTrue(Entity::numberInRangeValidate(100, 1, 100));
		$this->assertTrue(Entity::numberInRangeValidate(0, -101, 101));

		$this->assertFalse(Entity::numberInRangeValidate(0, 1, 100));
		$this->assertFalse(Entity::numberInRangeValidate(-1, 1, 100));
		$this->assertFalse(Entity::numberInRangeValidate(101, 1, 100));
		$this->assertFalse(Entity::numberInRangeValidate(-102, -101, 101));
	}
	
	public function testNumberInRangeMinOnly()
	{
		$this->assertTrue(Entity::numberInRangeValidate(1, 1));
		$this->assertTrue(Entity::numberInRangeValidate(1, -1));
		$this->assertTrue(Entity::numberInRangeValidate('1', 1));
		$this->assertTrue(Entity::numberInRangeValidate('99999999', 99));

		$this->assertFalse(Entity::numberInRangeValidate(0, 1));
		$this->assertFalse(Entity::numberInRangeValidate(-999, -100));
	}
	
	public function testNumberInRangeMaxOnly()
	{
		$this->assertTrue(Entity::numberInRangeValidate(-101, null, -100));
		$this->assertTrue(Entity::numberInRangeValidate(0, null, 99999));
		$this->assertTrue(Entity::numberInRangeValidate(-999, null, 99999));
		$this->assertTrue(Entity::numberInRangeValidate(9999, null, 99999));

		$this->assertFalse(Entity::numberInRangeValidate(9999, null, 9));
		$this->assertFalse(Entity::numberInRangeValidate(0, null, -999));
	}

	public function testIsId()
	{
		$this->assertTrue(Entity::isIdValidate(1));
		$this->assertTrue(Entity::isIdValidate(0xff));
		$this->assertTrue(Entity::isIdValidate(0755));
		$this->assertTrue(Entity::isIdValidate(9999));

		$this->assertFalse(Entity::isIdValidate('1'));
		$this->assertFalse(Entity::isIdValidate(-99));
		$this->assertFalse(Entity::isIdValidate(0));
	}
	
	public function testEnum()
	{
		$list = array('apple', 'banana', 'pear', 'pineapple');
		$this->assertTrue(Entity::enumValidate('apple', $list));
		$this->assertTrue(Entity::enumValidate('banana', $list));
		$this->assertFalse(Entity::enumValidate('Apple', $list));
		$this->assertFalse(Entity::enumValidate('', $list));
		$this->assertFalse(Entity::enumValidate('cow', $list));
	}

	public function testEnumIsCaseSensitive()
	{
		$list = array('apple', 'banana', 'pear', 'pineapple');
		$this->assertFalse(Entity::enumValidate('Apple', $list));
		$this->assertFalse(Entity::enumValidate('PEAR', $list));
	}

	public function testEnumValuesCanBeNonString()
	{
		$list = array(array(), 42, 'apple', 'banana', 'pear', 'pineapple');
		$this->assertTrue(Entity::enumValidate(array(), $list));
		$this->assertTrue(Entity::enumValidate(42, $list));
	}

	public function testEnumComparisonsAreLoose()
	{
		$list = array('1', 2, 3);
		$this->assertTrue(Entity::enumValidate(1, $list));
		$this->assertTrue(Entity::enumValidate(2, $list));
		$this->assertTrue(Entity::enumValidate('1', $list));
		$this->assertTrue(Entity::enumValidate('2', $list));
	}

	public function testEnumComparisonsCanBeStrict()
	{
		$list = array('1', 2, 3);
		$this->assertFalse(Entity::enumValidate(1, $list, true));
		$this->assertTrue(Entity::enumValidate(2, $list, true));
		$this->assertTrue(Entity::enumValidate('1', $list, true));
		$this->assertFalse(Entity::enumValidate('2', $list, true));
	}
	
	public function testTimestamp()
	{
		$this->assertTrue(Entity::timestampValidate(time()));
		$this->assertTrue(Entity::timestampValidate(1274213448));
		$this->assertTrue(Entity::timestampValidate('1274213448'));
		$this->assertTrue(Entity::timestampValidate('  1274213448  asldkj'));

		$this->assertFalse(Entity::timestampValidate(0));
		$this->assertFalse(Entity::timestampValidate(null));
		$this->assertFalse(Entity::timestampValidate(''));
		$this->assertFalse(Entity::timestampValidate('asd  1274213448  asldkj'));
	}
	
	public function testRegex()
	{
		$this->assertTrue(Entity::regexValidate('foobar', '/^foo/i'));
		$this->assertFalse(Entity::regexValidate('foobar', '/^FOO/'));
	}
	
	public function testInstanceOf()
	{
		$M = new MockEntity;
		$this->assertTrue(Entity::instanceofValidate($M, __NAMESPACE__.'\MockEntity'));
		$this->assertTrue(Entity::instanceofValidate($M, __NAMESPACE__.'\Entity'));
		$this->assertFalse(Entity::instanceofValidate($M, 'stdClass'));
		$this->assertFalse(Entity::instanceofValidate($M, 'stdClass'));
	}
	
	public function testInstanceOfFailsGracefullyOnScalars()
	{
		$this->assertFalse(Entity::instanceofValidate(0, 'stdClass'));		
		$this->assertFalse(Entity::instanceofValidate(null, 'stdClass'));		
		$this->assertFalse(Entity::instanceofValidate('', 'stdClass'));		
	}

	public function testInstanceOfCanCheckForEntityErrors()
	{
		//has entity validaton errors
		$M = new MockEntity;
		$this->assertTrue(Entity::instanceofValidate($M, __NAMESPACE__.'\MockEntity'));
		$this->assertTrue((bool) $M->errorsMatching('id', 'stooge'));
		$this->assertFalse(Entity::instanceofValidate($M, __NAMESPACE__.'\MockEntity', true));
		
		//clear entity validaton errors by assigning allowed values
		$M->id = 2;
		$M->stooge = 'moe';
		$this->assertTrue(Entity::instanceofValidate($M, __NAMESPACE__.'\MockEntity', true));
	}
}
