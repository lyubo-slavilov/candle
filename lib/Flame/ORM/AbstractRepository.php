<?php
namespace Flame\ORM;

/**
 * Repository abstraction which introduces all the basic methods for fetching entities from a table
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
abstract class AbstractRepository {
    
    protected $table;
    /**
     * @var Adapter
     */
    protected $adapter;
    protected $entityClass;
    
    protected $findQuery;
    protected $findByQuery;
    protected $findManyQuery;
    
    
    /**
     * Preparation of a query used in different *findBy() methods
     * @param array $conditions
     * @return \Flame\ORM\Query
     */
    private function prepareFindByQuery($conditions)
    {
        $query = new Query($this->adapter);
        
        $query
            ->select('*')
            ->from($this->table)
            ->fetchAsClass($this->entityClass);
        
        foreach ($conditions as $key => $value) {
            $query->where("$key = :{$key}");
        }
        $query->setParams($conditions);
        
        return $query;
    }
    
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        
        $classParts = explode('\\', get_class($this));
        $class = array_pop($classParts);
        
        $table = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $class));
        $this->table = $table;
        
        array_pop($classParts);
        $this->entityClass = implode('\\', $classParts) . '\\Entity\\' . $class;
        
    }
    
    /**
     * Fetches an entity identified by its primary key
     * @param mixed $id The primary key value
     * 
     * @return object  An entity
     */
    public function find($id)
    {
        
        if (is_null($this->findQuery)){
            $this->findQuery = new Query($this->adapter);
            $this->findQuery
                ->select('*')
                ->from($this->table)
                ->where('id = :id')
                ->fetchAsClass($this->entityClass);
        }
        
        return $this->findQuery
            ->setParam('id', $id)
            ->execute()
            ->fetchOne();
        
    }
    
    /**
     * Fetches a collection of entities which meet a set of conditions
     * @param array $conditions
     * @return array An array of entities
     */
    public function findBy(array $conditions)
    {
       
        $query = $this->prepareFindByQuery($conditions);
        
        return $query
            ->execute()
            ->fetchAll();
    }
    
    /**
     * Counts a rows of a collection which is selected by a set of conditions
     * @param array $conditions
     * @return integer
     */
    public function countBy(array $conditions)
    {
        $query = new Query($this->adapter);
        
        $query->select('COUNT(id) AS cnt')
            ->from($this->table);
        
        foreach ($conditions as $key => $value) {
            $query->where("$key = :{$key}");
        }
        
        $query->setParams($conditions);
        
        $result = $query->execute()->fetchSingle();
        return (int) $result[0];
    } 
    
    /**
     * Fetches one entity selected by a set of conditions
     * @param unknown_type $conditions
     * @return object An instance of the entity
     */
    public function findOneBy($conditions)
    {
        $query = $this->prepareFindByQuery($conditions);
        
        return $query
            ->execute()
            ->fetchOne();
    }
    
    /**
     * Fetches one entity selected by a set of conditions
     * 
     * It uses Query::fetchSingle() which coudl throw an exception
     * @param unknown_type $conditions
     * 
     * @return object An instance of the entity
     */
    public function findSingleBy($conditions)
    {
        $query = $this->prepareFindByQuery($conditions);
        
        return $query
            ->execute()
            ->fetchSingle();
    }
    
    /**
     * Finder with different options
     * @param array $options
     * 
     * @param array $options[conditions] Optional. WHERE clause conditions
     * @param array $options[order] Optional. A <column> => <direction> pairs for the resultset ordering
     * @param integer $options[limit] Optional. Limit of the resultset
     * @param integer $options[offset] Optional. Start offset for the resultset
     * 
     * @return array Array of entities
     */
    public function findMany(array $options = array())
    {
        
        $conditions = isset($options['conditions']) ? $options['conditions'] : null; 
        $order = isset($options['order']) && is_array($options['order']) ? $options['order'] : array();
        $limit = isset($options['limit']) ? $options['limit'] : null; 
        $offset = isset($options['offset']) ? $options['offset'] : 0; 
        
        
        $query = new Query($this->adapter);
    
        $query
            ->select('*')
            ->from($this->table)
            ->fetchAsClass($this->entityClass);
        
        if(!is_null($conditions) && is_array($conditions)) {
            foreach ($conditions as $key => $value) {
                $query->where("$key = :{$key}");
            }
            $query->setParams($conditions);
        }
        
        foreach ($order as $column => $direction) {
            $query->order($column, $direction);
        }
        
        if (!is_null($limit) && is_numeric($limit) && is_numeric($offset)) {
            $query->limit((int) $limit, (int) $offset);
        }
        
        
        return $query
            ->execute()
            ->fetchAll();
    }
}