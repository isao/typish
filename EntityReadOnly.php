<?php
namespace lib\Type;

/**
 * EntityReadOnly - a simple entity type that prevents changing properties after the
 * constructor is called.
 */
abstract class EntityReadOnly extends Entity
{
	protected $is_writeable = true;
	
	public function __construct($props = array(), $map = array())
	{
		parent::__construct($props, $map);
		$this->is_writeable = false;
	}

	public function __set($key, $val)
	{
		return $this->is_writeable
			? parent::__set($key, $val)
			: user_error('properties can be set via constructor only', E_USER_NOTICE);
		}
	}
}
