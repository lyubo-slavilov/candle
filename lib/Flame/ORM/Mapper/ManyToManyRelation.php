<?php
namespace Flame\ORM\Mapper;

use Flame\Exception\MapperException;

use Flame\Flame;

use Flame\ORM\Query;

/**
 * Class which represents a many to many relation
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
class ManyToManyRelation extends AbstractToManyRelation
{
    /**
     * @var AbstractEntity
     */
    private $owner;
    private $mappedByTable;
    private $table;
    private $targetEntFqn;
    
    public function __construct(AbstractEntity $owner, $table, $mappedByTable)
    {
        $this->owner = $owner;
        $this->table = $table;
        $this->mappedByTable = $mappedByTable;
        
        $classParts = explode('\\', get_class($this->owner));
        array_pop($classParts);
        
        $entClassName = str_replace(' ', '', ucwords(str_replace('_', ' ', $this->table)));
        $this->targetEntFqn = implode('\\', $classParts) . '\\' .   $entClassName;
    }
    
    /**
     * Returns the fully qualified name of the target entity
     * @see \Flame\ORM\Mapper\AbstractToManyRelation::getTargetEntityFqn()
     */
    public function getTargetEntityFqn()
    {
        return $this->targetEntFqn;
    }
    
    /**
     * Executes a query for loading the entities partisipate in the relation
     * @see \Flame\ORM\Mapper\AbstractCollection::loadCollection()
     */
    protected function loadCollection()
    {
        if ($this->collectionLoaded) {
            return;
        }
        
        $ot = $this->owner->getTable();
        $pk = $this->owner->getPk();
        
        $mt = $this->mappedByTable;
        $mok = $this->owner->getTable() . '_' . $pk;
        $mfk = $this->table . '_' . $pk;
        
        $ct = $this->table;
        $fk = 'id';
        
        $entFqn = $this->targetEntFqn;

        $query = new Query(Flame::getAdapter());
        $query->select('c.*')
            ->from("{$ot} AS o INNER JOIN {$mt} AS m ON (o.{$pk} = m.{$mok}) LEFT JOIN {$ct} AS c ON (m.{$mfk} = c.{$fk})")
            ->where("o.{$pk} = :pk")
            ->setParam('pk', $this->owner->getPkValue())
            ->fetchAsClass($entFqn)
            ->execute();
        
        $this->collection = $query->fetchAll();
        $this->collectionLoaded = true;
        
    }

    /**
     * Unit Of Work process
     * @see \Flame\ORM\Mapper\AbstractToManyRelation::processChanges()
     */
    public function processChanges()
    {
        $needReset = false;
        
        $pk = $this->owner->getPk();
        $mok = $this->owner->getTable() . '_' . $pk;
        $mfk = $this->table . '_' . $pk;
        
        if (count($this->uowAttach) > 0) {
            $query = new Query(Flame::getAdapter());
            $query->insert($this->mappedByTable);
            
            foreach ($this->uowAttach as $pkValue => $value) {
                $query->values(array(
                    $mok => $this->owner->getPkValue(),
                    $mfk => $pkValue
                ))->execute();
            }
            $needReset = true;
        }
        
        if (count($this->uowDetach) > 0) {
            
            $keys = array_keys($this->uowDetach);
            
            $query = new Query(Flame::getAdapter());
            $query
                ->delete($this->mappedByTable)
                ->where("{$mok} = :mok")
                ->in($mfk, $keys)
                ->setParam('mok', $this->owner->getPkValue())
                ->execute();
            
            $needReset = true;
        }
        
        if ($needReset) {
            $this->reset();
        }
    }
}