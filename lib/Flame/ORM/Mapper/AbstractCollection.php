<?php
namespace Flame\ORM\Mapper;

/**
 * Abstract class representing a ollection
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
abstract class AbstractCollection implements \Iterator, \ArrayAccess, \Countable
{
    protected $cursor;
    protected $collection = array(); 

    abstract protected function loadCollection();
    
    public function add($element){
        $this->collection[] = $element;
    }
    
    public function remove($offset)
    {
        unset($this->collection[$offset]);
    }
    
    public function __construct() {
        $this->cursor = 0;
    }

    function rewind() {
        $this->cursor = 0;
    }

    function current() {
        $this->loadCollection();
        return $this->collection[$this->cursor];
    }

    function key() {
        return $this->cursor;
    }

    function next() {
        ++$this->cursor;
    }

    function valid() {
        $this->loadCollection();
        return isset($this->collection[$this->cursor]);
    }
    
    public function offsetSet($offset, $value) {
        $this->loadCollection();
        if (is_null($offset)) {
            $this->add($value);
        } else {
            $this->collection[$offset] = $value;
        }
    }
    
    public function offsetExists($offset) {
        $this->loadCollection();
        return isset($this->collection[$offset]);
    }
    
    public function offsetUnset($offset) {
        $this->loadCollection();
        $this->remove($offset);
    }
    
    public function offsetGet($offset) {
        $this->loadCollection();
        //TODO May be throw exception if offset does not exist.
        return isset($this->collection[$offset]) ? $this->collection[$offset] : null;
    }
    
    public function count() {
        $this->loadCollection();
        return count($this->collection);
    }
}