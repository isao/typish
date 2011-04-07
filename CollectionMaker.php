<?php
namespace library\Type;

/**
 * CollectionMaker - to use/configure collections without pre-defining a class.
 * Pass entity class name, primary keyname, and optional indexing callbacks to
 * the constructor options array.
 *
 *  @category  Model Collection
 *  @package  USX
 *  @author  isao
 *  @license  none
 *  @link  none
 *
 */
class CollectionMaker extends Collection
{
  protected $indexers = array();//callback[]

  protected static $optdefs = array(
    'classname' => 'Entity',
    'classpkey' => 'id',
    'indexers' => array(),
  );

  /**
   * Adds indexers to parent options ...
   * @param $entities a starting list of entities for the collection
   * @param $options indexers and options for parent
   */
  public function __construct($entities = array(), $opts = array())
  {
    $options = ((array) $opts) + static::$optdefs;
    $this->classname = $options['classname'];
    $this->classpkey = $options['classpkey'];

    //register optional indexing functions
    if(isset($options['indexers'])) {
      $this->indexers = array_filter((array)$options['indexers'], 'is_callable');
      if($uncallable = count($options['indexers']) - count($this->indexers)) {
        $this->errors[] = sprintf(
          '%d indexer%s invalid', $uncallable, $uncallable === 1 ? ' is': 's are'
        );
      }
    }

    parent::__construct($entities, $options);
  }

  protected function _add($entity)
  {
    //process callbacks that can can accrue additional entity metadata in
    //$this->indexes, i.e. max/min, alternate sort orders, or filter
    foreach($this->indexers as $indexer) {
      $indexer($entity, $this->indexes);
    }
    parent::_add($entity);
  }
}
