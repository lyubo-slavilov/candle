<?php
namespace Flame\ORM;

use Flame\Exception\EmptyResultException;

use Flame\Exception\NotASingleResultException;

use Flame\Exception\QueryException;


/**
 * Class for query building and execution
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
class Query {

    const MODE_NONE = 0;
    const MODE_SELECT = 1;
    const MODE_UPDATE = 2;
    const MODE_INSERT = 3;
    const MODE_DELETE = 4;

    private $adapter;


    private $select  = '';
    private $update  = '';
    private $insert  = '';
    private $values  = null;
    private $set  = null;
    private $from  = '';
    private $in  = null;
    private $where = '';
    private $onduplicate = null;
    private $order = array();
    private $limit = '';

    private $parameters = array();

    private $mode = self::MODE_NONE;

    private $prepared = false;
    private $sql = '';

    /**
     *
     * @var \PDOStatement
     */
    private $statement;

    private $fetchAsClass = false;


    /**
     * Gests the current mode name
     * @param unknown_type $mode
     * @return Ambigous <>
     */
    private function getModeName($mode)
    {
        $reflection = new \ReflectionClass($this);

        $constants = array_flip($reflection->getConstants());

        return $constants[$mode];

    }

    /**
     * Sets the current mode name
     * @param unknown_type $mode
     * @throws QueryException
     */
    private function setMode($mode)
    {

        $allowed = array(
                self::MODE_NONE,
                self::MODE_SELECT,
                self::MODE_UPDATE,
                self::MODE_INSERT,
                self::MODE_DELETE,
        );


        if (! in_array($mode, $allowed)) {
            throw new QueryException("Invalid query mode {$mode}");
        }

        if ($this->mode != self::MODE_NONE) {
            $newName = $this->getModeName($mode);
            $currentName = $this->getModeName($this->mode);
            throw new QueryException("Can not change query mode to {$newName}. Query already in mode {$currentName}");
        }

        $this->mode = $mode;

    }

    /**
     * Prepares the SQL from the current object's state
     * @throws QueryException
     */
    private function prepareSql()
    {
        if ($this->prepared) {
            throw new QueryException("Query already prepared");
        }

        $sql = '';
        switch ($this->mode) {
            case self::MODE_SELECT:
                $this->sql = $this->prepareSelect();
                break;
            case self::MODE_UPDATE:
                $this->sql = $this->prepareUpdate();
                break;
            case self::MODE_INSERT:
                $this->sql = $this->prepareInsert();
                break;
            case self::MODE_DELETE:
                $this->sql = $this->prepareDelete();
                break;
            default:
                throw new QueryException("Can not execute query in mode '{$this->getModeName($this->mode)}'");
        }
        $this->prepared = true;

    }

    /**
     * Prepares a SELECT sql statement
     * @return string
     */
    private function prepareSelect()
    {
        $sql = 'SELECT ' . $this->select;
        $sql .= $this->from != '' ? ' FROM ' . $this->from : '';
        $sql .= $this->where != '' ? ' WHERE ' . $this->where : '';

        $sql .= $this->prepareIn();

        $order = '';
        foreach($this->order as $o) {
            $order .= "{$o['column']} {$o['direction']},";
        }

        $order = substr($order, 0, -1);

        $sql .=  $order != '' ? ' ORDER BY ' . $order : '';

        $sql .=  $this->limit != '' ? ' LIMIT ' . $this->limit : '';

        return $sql;

    }


    /**
     * Prepares the IN clause statement
     * @return string
     */
    private function prepareIn()
    {
        if (is_null($this->in)) {
            return '';
        }

        if (count($this->in['values']) == 0) {
            return '';
        }

        if ($this->in['useNames']) {
            $keys = array_keys($this->in['values']);
            $prefix = 'in_' . str_replace('.', '', $this->in['what']);
            $seq = ":{$prefix}" . implode(", :{$prefix}", $keys);
        } else {
            $seq = str_repeat('?, ', count($this->in['values']) - 1) . '?';
        }

        if ($this->where != '') {
            return ' AND ' . $this->in['what'] . " IN ({$seq})";
        } else {
            return ' WHERE ' . $this->in['what'] . " IN ({$seq})";
        }
    }

    /**
     * Prepares the UPDATE sql statement
     * @return string
     */
    private function prepareUpdate()
    {
       $sql = 'UPDATE ' . $this->update;

        $set = '';
        foreach ($this->set as $key => $value) {

            $set .= "{$key} = :set_{$key}, ";
        }

        $set = substr($set, 0, -2);

        $sql .= " SET {$set}";
        $sql .= $this->where != '' ? ' WHERE ' . $this->where : '';
        $sql .= $this->prepareIn();



        return $sql;

    }

    /**
     * Prepares the INSERT sql statement
     * @return string
     */
    private function prepareInsert()
    {
        $sql = 'INSERT INTO ' . $this->insert;

        $columns = '';
        $values = '';
        foreach ($this->values as $key => $value) {

            $columns .= "{$key}, ";
            $values .= ":val_{$key}, ";
        }

        $columns = substr($columns, 0, -2);
        $values = substr($values, 0, -2);

        $sql .= " ({$columns}) VALUES ({$values})";

        if ($this->onduplicate) {
            $set = '';
            foreach($this->onduplicate as $key => $value) {
                if (is_callable($value)) {
                    $result = $value($key);
                    $set .= "{$key} = {$result}, ";
                    unset($this->onduplicate[$key]);
                } else {
                    $set = "{$key} = :dpk_{$key}, ";

                }
            }
            $set = substr($set, 0, -2);
            $sql .= " ON DUPLICATE KEY UPDATE {$set}";
        }

        return $sql;
    }


    /**
     * Prepares the DELETE sql statement
     * @return string
     */
    private function prepareDelete()
    {

        $sql = 'DELETE FROM ' . $this->from;

        $sql .= $this->where != '' ? ' WHERE ' . $this->where : '';

        $sql .= $this->prepareIn();

        return $sql;
    }

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }


    /**
     * Store information about the class we want to fetch
     * @param string $className
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function fetchAsClass($className)
    {
        $this->fetchAsClass = $className;
        return $this;
    }

    /**
     * Gets the affected query rows
     * @return int
     */
    public function getAffectedRows()
    {

        return $this->statement ? $this->statement->rowCount() : null;
    }


    /**
     * Executes a query
     * @throws QueryException
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function execute()
    {

        if ($this->mode == self::MODE_NONE) {
            throw new QueryException("Can not execute an empty query.");
        }

        if (! $this->prepared) {
            $this->prepareSql();
        }

        $this->statement = $this->adapter->getPdo()->prepare($this->sql);


       // print $this->sql . "\n";


        foreach ($this->parameters as $key => $value) {
            $this->statement->bindValue($key, $value);
        }

        if (!is_null($this->in)) {
            $prefix = 'in_' . str_replace('.', '', $this->in['what']);

            foreach ($this->in['values'] as $key => $value) {
                $this->statement->bindValue($prefix . $key, $value);
            }
        }

        if ($this->mode == self::MODE_UPDATE) {
            foreach ($this->set as $key => $value) {
                $this->statement->bindValue('set_' . $key, $value);
            }
        }

        if ($this->mode == self::MODE_INSERT) {
            foreach ($this->values as $key => $value) {
                $this->statement->bindValue('val_' . $key, $value);
            }
            if (is_array($this->onduplicate)) {
                foreach ($this->onduplicate as $key => $value) {
                    $this->statement->bindValue('dpk_' . $key, $value);
                }
            }
        }


        if ($this->fetchAsClass) {
            $this->statement->setFetchMode(\PDO::FETCH_CLASS, $this->fetchAsClass);
        }

        $this->adapter->exec($this->statement);


        return $this;

    }

    /**
     * Fetches from the resultset.
     * Closes the cursor if this is beyound the last fetch
     * @return unknown
     */
    public function fetch()
    {
        $result =  $this->statement->fetch();

        if (!$result) {
            $this->statement->closeCursor();
        }

        return $result;
    }

    /**
     * Fetches one from the resultset and closes the cursor
     * @return Ambigous <NULL, mixed>
     */
    public function fetchOne()
    {
        $result = null;

        if ($this->statement->rowCount() > 0) {
            $result = $this->statement->fetch();
            $this->statement->closeCursor();
        }

        return $result;
    }

    /**
     * Fetches one from the resultset and closes the cursor
     * @throws EmptyResultException
     * @throws NotASingleResultException
     * @return mixed
     */
    public function fetchSingle()
    {
        if ($this->statement->rowCount() == 0) {
            throw new EmptyResultException('Result is empty');
        } else if ($this->statement->rowCount() > 1) {
            throw new NotASingleResultException('Not a single result');
        }


        $result = $this->statement->fetch();
        $this->statement->closeCursor();
        return $result;
    }

    /**
     * Fetches everithing
     * @return multitype:
     */
    public function fetchAll()
    {
        $result = $this->statement->fetchAll();
        $this->statement->closeCursor();
        return $result;
    }

    /**
     * Stores select info
     * @param string $expression SELECT expression
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function select($expression){
        $this->setMode(self::MODE_SELECT);
        $this->select = $expression;
        return $this;
    }

    /**
     * Stores UPDATE info
     * @param string $expression The UPDATE expression
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function update($expression){
        $this->setMode(self::MODE_UPDATE);
        $this->update = $expression;
        return $this;
    }

    /**
     * Stores the INSERT info
     * @param string $expression The UPDATE expression
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function insert($expression)
    {
        $this->setMode(self::MODE_INSERT);
        $this->insert = $expression;
        return $this;
    }

    /**
     * Stores values info for VALUES expression
     * @param array $values
     * @throws QueryException
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function values(array $values)
    {
        if ($this->mode != self::MODE_INSERT) {
            $currentName = $this->getModeName($this->mode);
            throw new QueryException("Can not use values() in mode different than MODE_INSERT. Current query mode is: {$currentName}");
        }
        $this->values = $values;
        return $this;
    }

    /**
     * Stores On DUPLICATE KEY info
     * @param array $values Values o be setted. Supports callables
     * @throws QueryException The current instance for chaining
     */
    public function onDuplicateSet($values)
    {
        if ($this->mode != self::MODE_INSERT) {
            $currentName = $this->getModeName($this->mode);
            throw new QueryException("Can not use onDuplicateSet() in mode different than MODE_INSERT. Current query mode is: {$currentName}");
        }
        $this->onduplicate = $values;
    }

    /**
     * Stores delete info for DELETE expression
     * @param string $from The DELETE FROM expression
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function delete($from){
        $this->setMode(self::MODE_DELETE);
        $this->from = $from;
        return $this;
    }

    /**
     * Stores the FROM info
     * @param string $expression the FROM clause info
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function from($expression){
        $this->from = $expression;
        return $this;
    }

    /**
     * Stores the set information for the SET clause
     * @param array $values Array of <key> => <value> pairs
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function set(array $values)
    {
        $this->set = $values;
        return $this;
    }

    /**
     * Stores a WHERE info
     * @param string $expression the WHERE epxpression
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function where($expression){
        $this->where = $expression;
        return $this;
    }

    /**
     * Adds an AND sub expression
     * @param string $expression
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function andWhere($expression)
    {
        if ($this->where == '') {
            $this->where = '1';
        }
        $this->where .= ' AND ' . $expression;
        return $this;
    }

    /**
     * Adds an OR sub expression
     * @param string $expression
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function orWhere($expression)
    {
        if ($this->where == '') {
            $this->where = '0';
        }
        $this->where .= ' OR ' . $expression;
        return $this;
    }

    /**
     * Stores an IN info
     * @param string $what Column identifier
     * @param array $values
     * @param boolean $useNames if to use names for the placeholders
     * @throws QueryException
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function in($what, array $values, $useNames = true)
    {

        if (count($values) == 0) {
            throw new QueryException('Cant use IN clause with empty array of values');
        }

        $this->in = array(
                'what' => $what,
                'values' => $values,
                'useNames' => $useNames,
        );
        return $this;
    }

    /**
     * Stores the ORDER info
     * @param string $column column name
     * @param string $direction ASC or DESC
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function order($column, $direction)
    {
        $this->order[] = array(
            'column' => $column,
            'direction' => $direction,
        );
        return $this;
    }

    /**
     * Stores LIMIT info
     * @param integer $count Limit count
     * @param integer $start The start offset
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function limit($count, $start = 0)
    {
        $this->limit = "{$start}, {$count}";
        return $this;
    }

    /**
     * Sets a parameter for later PDO bounding
     * @param string $name
     * @param mixed $value
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function setParam($name, $value)
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * Sets a bunch of parameteres for later PDO bounding
     * @param array $params Array of <name> => <value> pairs
     * @return \Flame\ORM\Query The current instance for chaining
     */
    public function setParams(array $params)
    {
        foreach($params as $key => $value) {
            $this->setParam($key, $value);
        }

        return $this;
    }
}