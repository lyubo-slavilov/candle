<?php
namespace Flame\ORM\Mapper;

use Flame\Flame;

use Flame\ORM\Query;

/**
 * Class which represent a OneToMany relation
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
class OneToManyRelation extends AbstractToManyRelation
{
    /**
     * @var AbstractEntity
     */
    private $owner;
    private $foreignKey;
    private $table;
    private $sortable;

    public function __construct(AbstractEntity $owner, $table, $foreignKey = null, $sortable = false)
    {
        $this->owner = $owner;
        $this->table = $table;
        $this->sortable = $sortable;

        if (is_null($foreignKey)) {
            $this->foreignKey = $owner->getTable() . '_' . $owner->getPk();
        } else {
            $this->foreignKey = $foreignKey;
        }

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

        $ct = $this->table;
        $fk = $this->foreignKey;

        $classParts = explode('\\', get_class($this->owner));
        array_pop($classParts);

        $entClassName = str_replace(' ', '', ucwords(str_replace('_', ' ', $this->table)));
        $entFqn = implode('\\', $classParts) . '\\' .   $entClassName;

        $query = new Query(Flame::getAdapter());
        $query->select('c.*')
            ->from("{$ot} AS o INNER JOIN {$ct} AS c ON (o.{$pk} = c.{$fk})")
            ->where("o.{$pk} = :pk");

        if ($this->sortable) {
            $query->order('c.sort', 'DESC');
        }

        $query->setParam('pk', $this->owner->getPkValue())
            ->fetchAsClass($entFqn)
            ->execute();

        $reverseSetter = 'set'. str_replace(' ', '', ucwords(str_replace('_', ' ', $ot)));

        while ($ent = $query->fetch()) {
            $ent->$reverseSetter($this->owner);
            $this->collection[] = $ent;
        }
        $this->collectionLoaded = true;

    }

    /**
     * Unit Of Work process
     * @see \Flame\ORM\Mapper\AbstractToManyRelation::processChanges()
     */
    public function processChanges()
    {
        $needReset = false;

        if (count($this->uowAttach)) {
            $keys = array_keys($this->uowAttach);
            $query = new Query(Flame::getAdapter());

            $fk = $this->owner->getTable() . '_' . $this->owner->getPk();

            $query
                ->update($this->table)
                ->set(array(
                     $fk => $this->owner->getPkValue()
                ))
                ->in('id', $keys)
                ->execute();
            echo $fk . '=>' .$this->owner->getPkValue();
            $needReset = true;
        }
        if (count($this->uowDetach)) {
            $keys = array_keys($this->uowDetach);
            $query = new Query(Flame::getAdapter());

            $fk = $this->owner->getTable() . '_' . $this->owner->getPk();

            $query
                ->update($this->table)
                ->set(array(
                        $fk => 0
                ))
                ->in('id', $keys)
                ->execute();
            $needReset = true;
        }

        if ($needReset) {
            $this->reset();
        }
    }
}