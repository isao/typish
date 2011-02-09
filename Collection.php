<?php
namespace library\Type;

use ArrayAccess
  , Countable
  , Iterator
  , InvalidArgumentException
  , RuntimeException;

/**
 * Collection
 *
 */
abstract class Collection implements ArrayAccess, Countable, Iterator
{
  protected $classname;//name of class of objects that can be collected
  protected $classpkey;//property name used to access collected objects
  protected $container = array();//Entity[]
  protected $errors = array();//array id => optional msg
  protected $valid = false;//bool for Iterator support

  public function __construct($entities = array(), $options = array())
  {
    if(empty($this->classname) || empty($this->classpkey)) {
      throw new InvalidArgumentException('class and/or key name undefined');
    }

    foreach($entities as $entity) {
      $this->set($entity);
    }
  }

  /**
   * @param scalar
   * @return Entity|null
   */
  public function get($id)
  {
    return isset($this->container[$id])
      ? $this->container[$id]
      : null;
  }

  /**
   * @param scalar
   * @return Entity|null
   */
  public function __get($id)
  {
    return $this->get($id);
  }

  /**
   * @param scalar
   * @return bool
   */
  public function __isset($id)
  {
    return isset($this->container[$id]);
  }

  /**
   * same as $this->get($id)->prop but won't error out if $id doesn't exist
   * @param scalar entity id
   * @param scalar entity property name
   * @return mixed
   */
  public function getProperty($id, $key)
  {
    return $this->get($id) && isset($this->get($id)->$key)
      ? $this->get($id)->$key
      : null;
  }

  /**
   * add an object to the collection
   * @return bool ok or error
   */
  public function set($entity)
  {
    switch(true)
    {
      //bad input
      case !is_a($entity, $this->classname):
        $this->errors[] = 'class '.get_class($entity)." must implement {$this->classname}";
        $ok = false;
        break;

      //entity has errors
      case method_exists($entity, 'errors') && $entity->errors():
        //record entity error
        $this->errors[$entity->__get($this->classpkey)] = $entity->errors();
        $ok = false;
        break;

      //add to the container, indexed by the entity's primary key property
      default:
        $this->_add($entity);
        $ok = true;
    }

    return $ok;
  }

  /**
   * add an entity to the collection-- slim method for overriding in subclasses
   * @param entity
   */
  protected function _add($entity)
  {
    $this->container[$entity->__get($this->classpkey)] = $entity;
  }

  /**
   * protect against accidental property assignment; use $this->set() otherwise
   * @throws RuntimeException
   */
  public function __set($key, $val)
  {
    throw new RuntimeException("setting undeclared property '$key' not allowed");
  }

  /**
   * @param string
   * @param callback
   * @return Collection
   */
  public function sort($key, $callback = null)
  {
    if(!is_callable($callback)) {
      $callback = function($a, $b) use($key) {
        return strcmp($a->$key, $b->$key);
      };
    }
    uasort($this->container, $callback);
    return $this;
  }

  /**
   * @return array note objects that were not of class $this->classname or
   * contained errors themselves @see set()
   */
  public function errors()
  {
    return $this->errors;
  }

  /**
   * get all ids in this collection
   * @return array of scalar ids
   */
  public function getIds()
  {
    return array_keys($this->container);
  }

  /**
   * return the collection as an array of arrays
   * @return array[]
   */
  public function getArray()
  {
    return array_map('iterator_to_array', $this->container);
  }

  /**
   * @param int @see http://php.net/json_encode
   * @return string
   */
  public function getJson($options = JSON_FORCE_OBJECT)
  {
    return json_encode($this->getArray(), $options);
  }

  /*
          for Countable, Iterator, ArrayAccess implementations
  */

  public function count()//Countable
  {
    return count($this->container);
  }

  public function rewind()//Iterator
  {
    $this->valid = (false !== reset($this->container));
  }

  public function current()//Iterator
  {
    return current($this->container);
  }

  public function key()//Iterator
  {
    return key($this->container);
  }

  public function next()//Iterator
  {
    $this->valid = (false !== next($this->container));
  }

  public function valid()//Iterator
  {
    return $this->valid;
  }

  public function offsetSet($key, $val)//ArrayAccess
  {
    return $this->set($val);//ignoring $key, use entity classpkey property instead
  }

  public function offsetGet($key)//ArrayAccess
  {
    return $this->get($key);
  }

  public function offsetExists($key)//ArrayAccess
  {
    return array_key_exists($key, $this->container);
  }

  public function offsetUnset($key)//ArrayAccess
  {
    unset($this->container[$key]);
  }
}
