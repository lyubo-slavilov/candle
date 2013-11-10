<?php
namespace Flame\ORM\Mapper;


/**
 * Abstract class which represents *-To-Many relation
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
abstract class AbstractToManyRelation extends AbstractCollection {
    
    protected $uowAttach = array();
    protected $uowDetach  = array();
    protected $collectionLoaded = false;
    
    abstract public function processChanges();
    abstract public function getTargetEntityFqn();
    
    
    /**
     * Resets all the collection settings
     */
    protected function reset()
    {
        $this->collectionLoaded = false;
        $this->uowAttach = array();
        $this->uowDetach = array();
        $this->collection = array();
    
    }
    
    /**
     * Searches the collection for an elemnt with the given primary key value
     * @param mixed $pkValue
     * @return mixed
     */
    protected function findInCollection($pkValue)
    {
        $this->loadCollection();

        //TODO could be optimized
        foreach ($this->collection as $entity) {
            if ($entity->getPkValue() == $pkValue) {
                return $entity;
            }
        }
        return null;
    }
    
    /**
     * Attaches a new entity to the collection and registeres it to the Unit Of Work
     * @param mixed $entityOrId
     * @throws MapperException If the specified entity does not participate in the relation
     */
    public function attach($entityOrId)
    {
    
        if (is_object($entityOrId)) {
            $fqn = $this->getTargetEntityFqn();
            if (! $entity instanceof $fqn) {
                $class = get_class($entity);
                throw new MapperException("Relation works whit entities of class {$fqn}. Instanse of {$class} passed instead.");
            }
    
            $pkValue = $entityOrId->getPkValue();
    
            if (is_null($pkValue)) {
                throw new MapperException("Related entity must have value for its primary key.");
            }
            $entity = $entityOrId;
        } else {
            $pkValue = $entityOrId;
            $entity = null;
        }
    
        if (is_null($this->findInCollection($pkValue))) {
            $this->uowAttach[$pkValue] = $entity;
        }
    
    }
    
    /**
     * Removes an entity from the collection and registeres the fact in the Unit Of Work
     * @param mixed $entityOrId
     * @throws MapperException
     */
    public function detach($entityOrId)
    {
        if (is_object($entityOrId)) {
            $fqn = $this->getTargetEntityFqn();
            if (! $entity instanceof $fqn) {
                $class = get_class($entity);
                throw new MapperException("Relation works whit entities of class {$fqn}. Instanse of {$class} passed instead.");
            }
    
            $pkValue = $entityOrId->getPkValue();
    
            if (is_null($pkValue)) {
                throw new MapperException("Can't remove entity which does not havevalue for its primary key.");
            }
            $entity = $entityOrId;
        } else {
            $pkValue = $entityOrId;
            $entity = null;
        }
    
        if (is_null($this->findInCollection($pkValue))) {
            //TODO throw an exeption ?
        } else {
            $this->uowDetach[$pkValue] = $entity;
        }
    }
}