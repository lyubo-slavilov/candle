<?php
namespace Flame\ORM;

use Flame\Exception\RollbackException;

/**
 * Simple PDO abstraction layer
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
class Adapter {

    /**
     *
     * @var \PDO
     */
    protected $pdo;

    private $transactionLevel = 0;

    public function connect($dsn, $username = '', $password = '', $options = null)
    {
        //TODO handle options if necessary
        try {
            $this->pdo = new \PDO($dsn, $username, $password, $options);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $this->pdo->exec('SET NAMES utf8');
        } catch (\Exception $ex) {
            throw new \Flame\Exception\CannotConnectException();
        }
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function beginTransaction()
    {
        if ($this->transactionLevel == 0) {
            $this->pdo->beginTransaction();
        }
        $this->transactionLevel ++;
    }

    public function commit()
    {
        $this->transactionLevel -- ;
        if ($this->transactionLevel == 0) {
            $this->pdo->commit();
        }
    }

    public function rollBack(\Exception $ex)
    {
        $this->transactionLevel -- ;
        if ($this->transactionLevel == 0) {
            $this->pdo->rollBack();
        }
        throw $ex;

    }


    public function getLastInsertId()
    {
        return $this->pdo->lastInsertId();
    }


    public function exec(\PDOStatement $statement, $parameters = null)
    {
        return $statement->execute($parameters);
    }
}