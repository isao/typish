<?php
namespace library\Type;

use ArrayAccess
  , Countable
  , Iterator
  , BadMethodCallException
  , OutOfBoundsException;

abstract class Entity implements ArrayAccess, Countable, Iterator
{
  const SANITIZE_SUFFIX = 'Sanitize';
  const VALIDATE_SUFFIX = 'Validate';
  const EXOGENEOUS_ERRKEY = '*';

  protected $properties = array(/* property names => default values */);
  protected $sanitizers = array(/* property names => modifying callbacks */);
  protected $validators = array(/* property names => validating callbacks */);
  protected $messages = array(/* property names => validation mgs */);
  protected $errors = array(/* property names => validation errors */);
  protected $valid = false;//bool for Iterator support

  /**
   * Bulk-set properties from an associative array or object. Sanitizers and
   * validators (if applicable) are applied to the provided properties. Keys or
   * properties not defined in $this->properties are ignored, unless they are
   * included in the $map argument as an alias or synonym. Arguments can also
   * be objects that can be cast to arrays.
   * @param array $props key/values to assign to matching Entity properties
   * @param array $map alternate keynames => Entity property names
   */
  public function __construct($props = array(), $map = array())
  {
    //cast to arrays, filter out non-Entity key value pairs, and map synonymous
    //keys in the input to property keys if applicable
    $props = $map
      ? $this->_map((array) $props, (array) $map)
      : array_intersect_key((array) $props, $this->properties);

    //use defaults for any key value pairs missing from $props
    $props = array_merge($this->properties, $props);

    //sanitize & validate
    foreach($props as $key => $val) {
      $this->__set($key, $val);
    }
  }

  /**
   * get an entity property
   * @throws OutOfBoundsException
   * @param string property name to get
   * @return mixed
   */
  public function __get($key)
  {
    if(!array_key_exists($key, $this->properties)) {
      throw new OutOfBoundsException("getting undeclared property '$key' not allowed");
    }
    return $this->properties[$key];
  }

  /**
   * set a property, applying any applicable callbacks from $this->sanitizers
   * and $this->validators. Validation errors are accessed with $this->errors().
   * The return value is ignored when called via assignment operator as intended
   * @throws OutOfBoundsException
   * @param string property name
   * @param mixed property value
   */
  public function __set($key, $val)
  {
    if(!array_key_exists($key, $this->properties)) {
      throw new OutOfBoundsException("setting undeclared property '$key' not allowed");
    }
    $this->properties[$key] = $val;
    $this->_sanitize($key);
    $this->_validate($key);
  }

  /**
   * @param string property name
   * @return bool true if the property is not null
   */
  public function __isset($key)
  {
    return isset($this->properties[$key]);
  }

  /**
   * accomodate optional property name synonym(s) and filter out inapplicable
   * key/value pairs
   * @param array $input key/values to assign to matching Entity properties
   * @param array $map alternate keynames => Entity property names
   * @return array
   */
  protected function _map(array $input, array $map)
  {
    $props = array();
    foreach($input as $key => $val) {
      if(array_key_exists($key, $this->properties)) {
        //internal key name
        $props[$key] = $val;
      } elseif(array_key_exists($key, $map) && !array_key_exists($map[$key], $input)) {
        //key name maps & the key it maps to isn't also elsewhere in the input
        $props[$map[$key]] = $val;
      }
    }
    return $props;
  }

  /**
   * modify a property using the method and parameters in $this->sanitizers
   * @param string property name
   */
  protected function _sanitize($key)
  {
    if(isset($this->sanitizers[$key])) {
      $this->properties[$key] = $this->_callback(
        $key, static::SANITIZE_SUFFIX, (array) $this->sanitizers[$key]
      );
    }
  }

  /**
   * validate a property using the method and parameters in $this->validators,
   * record key and optional message in $this->errors.
   * @param string property name
   */
  protected function _validate($key)
  {
    if(isset($this->validators[$key])) {

      $ok = $this->_callback(
        $key, static::VALIDATE_SUFFIX, (array) $this->validators[$key]
      );

      if($ok) {
        unset($this->errors[$key]);
      } else {
        $msg = isset($this->messages[$key]) ? $this->messages[$key] : '';
        $this->errors[$key] = $msg;
      }
    }
  }

  /**
   * @param string property name
   * @param string static::SANITIZE_SUFFIX or static::VALIDATE_SUFFIX
   * @param array containing 1. callback, and 2. additional args if any
   * To use a class method for a callback, the first element of the $param array
   * must be an array, so the $this->validators or $this->sanitizers value must
   * look like: array(array('classname', 'methodname), ...optional args)
   * @return mixed
   */
  protected function _callback($key, $suffix, array $param)
  {
    //construct callback
    switch(true)
    {
      case is_callable($call = array($this, $param[0].$suffix))://keySuffix in scope
      case is_callable($call = $param[0])://global function
        break;
      default:
        throw new BadMethodCallException(
          "$suffix method {$param[0]} isn't defined"
        );
    }

    //compile callback arguments
    $args = array_slice($param, 1);//optional arguments
    array_unshift($args, $this->properties[$key]);//prepend prop value

    //exec callback
    return call_user_func_array($call, $args);
  }

  /**
   * @return array keys => property names, values => optional messages
   */
  public function errors()
  {
    return $this->errors;
  }

  /**
   * get errors except for the ones named in the passed arguments
   * @param mixed arbitrary number of string keys OR a single array of keys
   * @return array subset of $this->errors
   */
  public function errorsExcept(/* arbitrary args */)
  {
    $args = $this->_arrayOrList(func_get_args());
    return array_diff_key($this->errors, $args);
  }

  /**
   * get errors considering only the ones named in the passed arguments
   * @param mixed arbitrary number of string keys OR a single array of keys
   * @return array subset of $this->errors
   */
  public function errorsMatching(/* arbitrary args */)
  {
    $args = $this->_arrayOrList(func_get_args());
    return array_intersect_key($this->errors, $args);
  }

  /**
   * return only the values whose keys are passed as arguments
   * @param mixed arbitrary number of string keys OR a single array of keys
   * @return array of matching properties
   */
  public function valuesMatching(/* arbitrary args */)
  {
    $args = $this->_arrayOrList(func_get_args());
    return array_intersect_key($this->properties, $args);
  }

  /**
   * @param array
   * @return array
   */
  protected function _arrayOrList(array $args)
  {
    return is_array($args[0]) ? $args[0] : array_flip($args);
  }

  /**
   * set an error unrelated to internal validations (i.e. database error, etc).
   * there's only one value.
   * @param mixed
   */
  public function setExternalError($msg)
  {
    $this->errors[static::EXOGENEOUS_ERRKEY] = $msg;
  }

  /**
   * unset a presumeably reversable external error. there's only one value.
   * @param mixed
   */
  public function unsetExternalError()
  {
    unset($this->errors[static::EXOGENEOUS_ERRKEY]);
  }

  /**
   * @return ArrayIterator
   */
  public function getIterator()//IteratorAggregate interface
  {
    return new ArrayIterator($this->properties);
  }

  /**
   * @return array
   */
  public function getArray()
  {
    return $this->properties;
  }

  /**
   * @param json_encode() options. 0 for none @see http://php.net/json_encode
   * @return string
   */
  public function getJson($options = JSON_FORCE_OBJECT)
  {
    return json_encode($this->getArray(), $options);
  }

  /**
   * get all defined property names/keys; to populate sql selects, for example
   * Note: get_called_class() requires php 5.3+
   * @return array
   */
  public static function getPropertyNames()
  {
    $all_props = get_class_vars(get_called_class());
    return array_keys($all_props['properties']);
  }

  /*

          for Countable, Iterator, ArrayAccess implementations

  */

  public function count()//Countable
  {
    return count($this->properties);
  }

  public function rewind()//Iterator
  {
    $this->valid = (false !== reset($this->properties));
  }

  public function current()//Iterator
  {
    return current($this->properties);
  }

  public function key()//Iterator
  {
    return key($this->properties);
  }

  public function next()//Iterator
  {
    $this->valid = (false !== next($this->properties));
  }

  public function valid()//Iterator
  {
    return $this->valid;
  }

  public function offsetSet($key, $val)//ArrayAccess
  {
    $this->__set($key, $val);
  }

  public function offsetGet($key)//ArrayAccess
  {
    $this->__get($key);
  }

  public function offsetExists($key)//ArrayAccess
  {
    return array_key_exists($key, $this->properties);
  }

  public function offsetUnset($key)//ArrayAccess
  {
    unset($this->properties[$key]);
  }


  /*

          SANITIZERS

  */


  /**
   * @param scalar
   * @return int 1 if $val is 1|"1"|"true"|"on"|"yes", else 0
   */
  public static function intboolSanitize($val)
  {
    return (int) filter_var($val, FILTER_VALIDATE_BOOLEAN);
  }

  /**
   * strips all characters except [0-9.+-] and casts to float. more lax than
   * floatval(), use if input might contain commas or currency chars.
   * @see MockEntitySanitizersTest::testFloat()
   * @param scalar
   * @return float
   */
  public static function floatSanitize($val)
  {
    return (float) filter_var($val, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
  }

  /**
   * unix timestamp; if $always_update = true or if the current value is not a
   * valid timestamp, return the current time
   * @param scalar
   * @param bool change current value even if it's already a valid timestamp
   * @return int epoch seconds
   */
  public static function timestampSanitize($val, $always_update = true)
  {
    if($always_update || !self::timestampValidate($val)) {
      $val = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
    }
    return (int) $val;
  }

  public static function datetimeSanitize($val)
  {
    return self::timestampSanitize($val, false);
  }

  /**
   * @param scalar value to clamp
   * @param number lowest allowable value
   * @param number highest allowable value
   * @param string $settype 'int'|'float', value is cast to one of these types
   * @return int (or float if $settype === 'float')
   */
  public static function clampSanitize($val, $min, $max, $settype = 'int')
  {
    $val = max($min, min($val, $max));
    settype($val, $settype);
    return $val;
  }

  /**
   * @param string subject
   * @param mixed regular expression matching pattern
   * @param mixed regular expression replacement pattern
   * @param int replacement limit
   * @return string
   */
  public static function regexSanitize($val, $pattern, $replacement, $limit = -1)
  {
    return preg_replace($pattern, $replacement, (string) $val, $limit);
  }

  /**
   * @param mixed
   * @param string name of a class to instantiate if empty
   * @param object
   */
  public function instanceSanitize($val, $classname)
  {
    return $val ?: new $classname;
  }


  /*

          VALIDATORS

  */


  /**
   * @param number
   * @param number minimum, or null to ignore
   * @param number maximum, or null to ignore
   * @return bool
   */
  public static function numberInRangeValidate($val, $min = null, $max = null)
  {
    return is_numeric($val)
      && (is_null($max) || ($val <= $max))
      && (is_null($min) || ($val >= $min));
  }

  /**
   * @param int
   * @return bool
   */
  public static function isIdValidate($val)
  {
    return is_int($val) && ($val > 0);
  }

  /**
   * check the key value is in array
   * @param string value to check
   * @param array possible acceptable values
   * @param bool true to check type as well as value
   * @return bool
   */
  public static function enumValidate($val, array $enum, $strict = false)
  {
    return in_array($val, $enum, $strict);
  }

  /**
   * check if value is a unix timestamp in a given range; default is between
   * 2001-09-09 and 2106-02-06
   * @param scalar
   * @param int optional minimum timestamp value
   * @param int optional maximum timestamp value
   * @return bool
   */
  public static function timestampValidate($val, $min = 1000000000, $max = 4294967295)
  {
    return self::numberInRangeValidate((int) $val, $min, $max);
  }

  /**
   * @param string subject
   * @param mixed regular expression matching pattern
   * @return bool
   */
  public static function regexValidate($val, $pattern)
  {
    return (bool) preg_match($pattern, (string) $val);
  }

  /**
   * check if a value is an instance/implementation of a classname, and
   * optionally check it's errors() method if it exists
   * @param object
   * @param string class name
   * @param bool $check_errors true to also check entity for errors
   * @return bool
   */
  public static function instanceofValidate($val, $classname, $check_errors = false)
  {
    $ok = is_object($val) && ($val instanceof $classname);
    if($check_errors && method_exists($val, 'errors')) {
      $ok &= !$val->errors();
    }
    return (bool) $ok;
  }

  /**
   * check if a value looks like a sha1 hash
   * @param string
   * @return bool
   */
  public static function sha1hashValidate($val)
  {
    return (strlen($val) === 40) && ctype_xdigit($val);
  }
}
