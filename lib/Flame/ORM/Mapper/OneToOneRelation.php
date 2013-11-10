<?php
namespace Flame\ORM\Mapper;

use Flame\Exception\MapperException;

use Flame\Flame;

use Flame\ORM\Query;

class OneToOneRelation
{
    /**
     * @var AbstractEntity
     */
    private $owner;
    private $foreignKey;
    private $table;
    
    private $reverse = false;
    private $relatedEntity;
    private $entityLoaded = false;
    
    protected $uowUpdate = null;
    
    public function __construct($owner, $table, $reverse = false)
    {
        $this->owner = $owner;
        $this->table = $table;
        $this->reverse = $reverse;
        
        if ($reverse) {
            //TODO make this indipendant form '_id' suffix
            $this->foreignKey = $table . '_id';
        } else {
            $this->foreignKey = $owner->getTable() . '_' . $owner->getPk();
        }
    }
    
    /**
     * Lazy loads the related entity object
     * @return Ambigous <\Flame\ORM\Mapper\AbstractEntity, NULL>
     */
    public function getRelatedEntity()
    {
        if ($this->entityLoaded) {
            return $this->relatedEntity;
        }
        
        $ot = $this->owner->getTable();
        $ct = $this->table;
        $pk = $this->owner->getPk();
        
        if ($this->reverse) {
        
            $fk = 'id';
            $lk = $this->foreignKey;
        } else {
            $lk = $pk;
            $fk = $this->foreignKey;
        }
        
        
        $classParts = explode('\\', get_class($this->owner));
        array_pop($classParts);
        
        $entClassName = str_replace(' ', '', ucwords(str_replace('_', ' ', $this->table)));
        $entFqn = implode('\\', $classParts) . '\\' .   $entClassName;

        $query = new Query(Flame::getAdapter());
        
        
        $query
            ->select('c.*')
            ->from("{$ot} AS o INNER JOIN {$ct} AS c ON (o.{$lk} = c.{$fk})")
            ->where("o.{$pk} = :pk")
            ->setParam('pk', $this->owner->getPkValue())
            ->fetchAsClass($entFqn)
            ->execute();
        $this->relatedEntity = $query->fetchOne();
        $this->entityLoaded = true;
        
        return $this->relatedEntity;
    }
    
    /**
     * Attaching new related object to the relation.
     * It registeres he entity to the unit of work
     * 
     * @param mixed $entity NULL or an instance of a Flame\ORM\Mapper\AbstractEntity
     * @throws MapperException
     */
    public function setRelatedEntity($entity)
    {    
        if (is_null($entity)) {
            
            if (! $this->entityLoaded) {
                $this->getRelatedEntity();
            }
            if (!is_null($this->relatedEntity)) {
                if ($this->reverse) {
                    $this->owner->{$this->foreignKey} = 0;
                } else {
                    $this->relatedEntity->{$this->foreignKey} = 0;
                    $this->uowUpdate = $this->relatedEntity;
                }
            }
            
        } elseif (! $entity instanceof AbstractEntity) {
            
            $entClassName = str_replace(' ', '', ucwords(str_replace('_', ' ', $this->table)));
            throw new MapperException("{$entClassName} must be instance of AbstractEntity");
            
        } else {
            
            if (! $this->entityLoaded) {
                $this->getRelatedEntity();
            }
            
            if (is_null($this->relatedEntity)) {
                if ($this->reverse) {
                    $this->owner->{$this->foreignKey} = $entity->getPkValue();
                } else {
                    $entity->{$this->foreignKey} = $this->owner->getPkValue();
                    $this->uowUpdate = $entity;
                }
            }
        }
        
        $this->relatedEntity = $entity;
        $this->entityLoaded = true;
    }
    
    /**
     * Unit Of Work process
     * @see \Flame\ORM\Mapper\AbstractToManyRelation::processChanges()
     */
    public function processChanges()
    {
        if ($this->uowUpdate) {
                $this->uowUpdate->save();
        }
    }
}