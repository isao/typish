<?php
namespace lib\Type;

require_once dirname(__DIR__).'/Entity.php';

/**
 * example implementation of Entity for testing
 *
 */
class MockEntity extends Entity
{
	const STOOGE_ERRMSG = 'a stooge must be either "larry", "curly", "moe", or "winchester".';
	
	protected $properties = array(
		'id' => 0,
		'name' => '',
		'is_ok' => null,
		'score' => 0,
		'stooge' => null,
		'created' => null,
		'updated' => null,
	);

	protected $sanitizers = array(
		'id' => 'intval',
		'is_ok' => 'intbool',
		'created' => array('timestamp', false),//doesn't update valid pre-existing values
		'updated' => 'timestamp',//always update
	);

	protected $validators = array(
		'id' =>  array('numberInRange', 1),//greater than 1
		'stooge' => array('enum', array('larry', 'curly', 'moe', 'winchester')),
		'score' => array('numberInRange', 0, 100),
	);

	protected $messages = array(
		'stooge' => MockEntity::STOOGE_ERRMSG,
	);
}

class MockException1Entity extends MockEntity
{
	/**
	 * causes constructor to fail (a good thing)
	 * @throws BadMethodCallException
	 */
	protected $validators = array(
		'id' => 'not_a_real_callback',
	);
}

class MockException2Entity extends MockEntity
{
	/**
	 * causes constructor to fail (a good thing)
	 * @throws BadMethodCallException
	 */
	protected $validators = array(
		'id' => array('not_a_real_callback', 'some', 'arguments', 123, 'abc'),
	);
}

class MockEntityMapped extends MockEntity
{
	protected $properties = array(
		'id' => 0,
		'apple' => '',
		'pear' => '',
	);

	protected $sanitizers = array(
		'id' => 'intval',
	);
}
