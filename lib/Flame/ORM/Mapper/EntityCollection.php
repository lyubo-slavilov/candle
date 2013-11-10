<?php
namespace Flame\ORM\Mapper;

/**
 * Simple entity collection class
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
class EntityCollection extends AbstractCollection
{
    
    protected function loadCollection()
    {}
    
    public function __construct(array $collection) {
        
        $this->collection = $collection;
        parent::__construct();
    }  
   
}