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
    
    private $lastSort = null;
    abstract public function processChanges();
    abstract public function getTargetEntityFqn();
    
    public function sort($col, $dir = 'ASC') {
        
        $this->loadCollection();
        if ($this->lastSort == $col . '-' . strtoupper($dir)) {
            return $this;
        }
        if (strtoupper($dir) == 'DESC') {
            $sorter = function($ent1, $ent2) use ($col){
                return $ent1->{$col} > $ent2->{$col} ? -1 : ($ent1->$col == $ent2->{$col} ? 0 : 1);  
            };
        } else {
            $dir = 'ASC';
            $sorter = function($ent1, $ent2) use ($col){
                return $ent1->{$col} < $ent2->{$col} ? -1 : ($ent1->{$col} == $ent2->{$col} ? 0 : 1);  
            };
            
        }
        usort($this->collection, function($ent1, $ent2) use ($sorter){
            return $sorter($ent1, $ent2);
        });
        
        $this->lastSort = $col . '-' . strtoupper($dir);
        return $this;
    }
    
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
            if (! $entityOrId instanceof $fqn) {
                $class = get_class($entityOrId);
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