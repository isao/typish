<?php
use library\Type\Entity
  , library\Type\MockEntityMapped;

require_once __DIR__.'/MockEntity.php';

class MockEntityMappedTest extends PHPUnit_Framework_TestCase
{
  public function testMapFrenchToEnglishPropertyNames()
  {
	$key_map = array(
	  'FRENCHID' => 'id',
	  'POMME' => 'apple',
	  'POIRE' => 'pear',
	);

	$input_data = array(
	  'FRENCHID' => '88',
	  'POMME' => 'ipad',
	  'POIRE' => 'pecl',
	);

	$M = new MockEntityMapped($input_data, $key_map);

	$expected = array(
	  'id' => 88,
	  'apple' => 'ipad',
	  'pear' => 'pecl',
	);
	$this->assertSame($expected, $M->getArray());

	$this->assertSame(88, $M->id);
	$this->assertSame('ipad', $M->apple);
	$this->assertSame('pecl', $M->pear);
  }

  public function testPartialMapOk()
  {
	$key_map = array(
	  'POMME' => 'apple',
	  'POIRE' => 'pear',
	);

	$input_data = array(
	  'id' => '88',
	  'POMME' => 'ipad',
	  'POIRE' => 'pecl',
	);

	$M = new MockEntityMapped($input_data, $key_map);

	$expected = array(
	  'id' => 88,
	  'apple' => 'ipad',
	  'pear' => 'pecl',
	);
	$this->assertSame($expected, $M->getArray());

	$this->assertSame(88, $M->id);
	$this->assertSame('ipad', $M->apple);
	$this->assertSame('pecl', $M->pear);
  }

  public function testExtraMapKeysOk()
  {
	$key_map = array(
	  'POMME' => 'apple',
	  'POIRE' => 'pear',
	  'la_pomme' => 'apple',
	  'this is ignored' => 'id'
	);

	$input_data = array(
	  'id' => '88',
	  'POMME' => 'ipad',
	  'POIRE' => 'pecl',
	);

	$M = new MockEntityMapped($input_data, $key_map);

	$expected = array(
	  'id' => 88,
	  'apple' => 'ipad',
	  'pear' => 'pecl',
	);
	$this->assertSame($expected, $M->getArray());

	$this->assertSame(88, $M->id);
	$this->assertSame('ipad', $M->apple);
	$this->assertSame('pecl', $M->pear);
  }

  /**
   * @todo change this so non-mapped keys take priority
   */
  public function testIfDataWithMappedKeyAndRealKeyProvidedRealOneIsUsed()
  {
	$key_map = array(
	  'FRENCHID' => 'id',
	  'POMME' => 'apple',
	  'POIRE' => 'pear',
	);

	$input_data = array(
	  'id' => '77',
	  'FRENCHID' => '88',//maps to 'id'
	  'POMME' => 'ipad',
	  'POIRE' => 'pecl',
	);

	$M = new MockEntityMapped($input_data, $key_map);

	$expected = array(
	  'id' => 77,
	  'apple' => 'ipad',
	  'pear' => 'pecl',
	);
	$this->assertSame($expected, $M->getArray());

	$this->assertSame(77, $M->id);
	$this->assertSame('ipad', $M->apple);
	$this->assertSame('pecl', $M->pear);
  }

  public function testXpathPropsAreMapped()
  {
	$raw = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<props>
  <id>77</id>
  <FRENCHID>88</FRENCHID>
  <POMME>ipad</POMME>
  <pear>pecl</pear>
</props>
XML;

	$key_map = array(
	  'FRENCHID' => 'id',
	  'POMME' => 'apple',
	  'POIRE' => 'pear',
	);

	$input_data = new SimpleXmlElement($raw);
	$input_data = $input_data->xpath('//props');//array(0 => SimpleXmlElement);
	/* i.e.:
	array(
	  [0] => SimpleXMLElement Object(
		[id] => 77
		[FRENCHID] => 88
		[POMME] => ipad
		[pear] => pecl
	  )
	)
	*/
	$input_data = $input_data[0];//SimpleXMLElement Object

	$M = new MockEntityMapped($input_data, $key_map);

	$expected = array(
	  'id' => 77,
	  'apple' => 'ipad',
	  'pear' => 'pecl',
	);
	$this->assertSame($expected, $M->getArray());

	$this->assertSame(77, $M->id);
	$this->assertSame('ipad', $M->apple);
	$this->assertSame('pecl', $M->pear);

  }
}
