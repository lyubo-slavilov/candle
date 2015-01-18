<?php
namespace Flame\ORM\Mapper;

use Flame\Flame;

use Flame\ORM\Query;

use Flame\Exception\MapperException;

/**
 * Abstract Flame entity.
 * Introduces methods which are necessary for setting up the children classes.
 *
 *
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
abstract class AbstractEntity {

    private $fields;
    private $relations;
    private $pk;
    private $table;

    private $loaded;

    public function __construct($loaded = false)
    {
        $classParts = explode('\\', get_class($this));
        $class = array_pop($classParts);

        $table = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $class));
        $this->table = $table;

        $this->relations['hasOne'] = array();
        $this->relations['hasMany'] = array();

        $this->setup();

        if (is_null($this->pk)) {
            throw new MapperException('Primary key must be declared');
        }

        if (!isset($this->{$this->pk})) {
            $this->{$this->pk} = null;
        }
        $this->loaded = $loaded;

    }

    abstract function setup();

    /**
     * Primary key declarator
     * @param string $name
     */
    protected function declaratePk($name)
    {
        $this->pk = $name;
        $this->declarateField($name);

    }

    /**
     * Primary key name getter
     * @return string
     */
    public function getPk()
    {
        return $this->pk;
    }


    /**
     * Primary key value getter
     */
    public function getPkValue()
    {
        return $this->{$this->pk};
    }

    /**
     * Returns the entity db table name
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Simple column declarator
     * @param string $name The name of the column
     * @param array $options - Reserved for future versions
     */
    protected function declarateField($name, $options = true)
    {
        $this->fields[$name] = $options;
    }


    /**
     * Declarates To-One relation with another table
     * @param string $name The name of the related table
     * @param array $options
     *
     * @param boolen $options[reverse] If TRUE indicates that the foreign key is in the target table
     * @param string $options[alias] Decpalartes an alias to the relation which will be used for dynamic
     * getter and setter definition
     *
     */
    protected function hasOne($name, array $options = array())
    {
        $reverse = isset($options['reverse']) ? $options['reverse'] : false;

        if (isset($options['alias'])) {
            $alias = $options['alias'];
        } else {
            $alias = $name;
        }

        $this->relations['hasOne'][$alias] = new OneToOneRelation($this, $name, $reverse);
    }

    /**
     * Declarates To-Many relation with another table
     * @param string $name The name of the related table
     * @param array $options
     *
     * @param string $options[alias] Decpalartes an alias to the relation which will be used for dynamic
     * getter and setter definition
     *
     * @param string $options[foreignKey] The name of the foreign key column. If ommited - the name will
     * be resolvet automaticially
     */
    protected function hasMany($name, array $options = array())
    {

        if (isset($options['alias'])) {
            $alias = $options['alias'];
        } else {
            $alias = $name . 's';
        }
        $foreignKey = isset($options['foreignKey']) ? $options['foreignKey'] : null;
        $sortable = isset($options['sortable']) ? (boolean) $options['sortable'] : false;

        $this->relations['hasMany'][$alias] = new OneToManyRelation($this, $name, $foreignKey, $sortable);

    }

    /**
     * Declarates Many-To-Many relation with another table
     * @param string $name The name of the related table
     * @param array $options
     *
     * @param string $options[alias] Decpalartes an alias to the relation which will be used for dynamic
     * getter and setter definition
     *
     * @param boolen $options[reverse] If TRUE indicates that the foreign key is in the target table
     */
    protected function hasManyToMany($name, array $options = array())
    {
        if (isset($options['alias'])) {
            $alias = $options['alias'];
        } else {
            $alias = $name . 's';
        }

        if (isset($options['reverse']) && $options['reverse']) {
            $mappedBy = $name . '_' . $this->getTable();
        } else {
            $mappedBy = $this->getTable() . '_' . $name;
        }

        $sortable = isset($options['sortable']) ? (boolean) $options['sortable'] : false;
        $this->relations['hasMany'][$alias] = new ManyToManyRelation($this, $name, $mappedBy, $sortable);
    }


    /**
     * Magic method caller
     * Magic method caller which does all the logic for getting and setting values
     * from the registered fields which represents the db table columns
     *
     * @param string $name
     * @param array $args
     * @throws MapperException If the field or relation does not exists
     */
    public function __call($name, $args)
    {

        $op = substr($name, 0, 3);
        $rest = substr($name, 3);


        if (! in_array($op, array('get', 'set'))) {
            throw new MapperException('Invalid entity method "' . $name . '"');
        }

        $camelName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $rest));


        if (isset($this->fields[$camelName])) {

            if ($op == 'get') {
                return $this->$camelName;
            } else {
                $this->$camelName = $args[0];
            }

        } else if(isset($this->relations['hasMany'][$camelName])){

            if ($op == 'get') {
                return $this->relations['hasMany'][$camelName];
            }

        } else if(isset($this->relations['hasOne'][$camelName])){

            if ($op == 'get') {
                return $this->relations['hasOne'][$camelName]->getRelatedEntity();
            } else {
                $this->relations['hasOne'][$camelName]->setRelatedEntity($args[0]);
            }

        } else {
            throw new MapperException('Access over invalid entity field "' . $camelName . '"');
        }

    }

    /**
     * Runs the entity's save procedure
     * @throws Exception
     */
    public function save()
    {

        $fields = $this->fields;


        unset($fields[$this->getPk()]);
        foreach ($fields as $key => $fieldOptions) {
            if (isset($this->$key)) {
                $values[$key] = $this->$key;
            }
        }

        $adapter = Flame::getAdapter();

        $adapter->beginTransaction();

        try {
            $query = new Query($adapter);

            if (! is_null($this->getPkValue())) {

                $query->update($this->table)
                    ->set($values)
                    ->where("{$this->getPk()} =  :pk")
                    ->setParam('pk', $this->getPkValue())
                    ->execute();

            } else {

                $values[$this->getPk()] = null;
                $query->insert($this->table)
                    ->values($values)
                    ->execute();

                $insertId = Flame::getAdapter()->getLastInsertId();
                $this->{$this->getPk()} = $insertId;
            }
            foreach ($this->relations['hasOne'] as $relation) {
                $relation->processChanges();
            }
            foreach ($this->relations['hasMany'] as $relation) {
                $relation->processChanges();
            }
            $adapter->commit();
        } catch (\Exception $ex) {
            $adapter->rollBack($ex);
            throw $ex;
        }
    }

}