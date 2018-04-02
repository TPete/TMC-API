<?php

namespace TinyMediaCenter\API\Service;

use TinyMediaCenter\API\Model\DBModel;

/**
 * Class AbstractStore
 */
abstract class AbstractStore
{
    /**
     * @var DBModel
     */
    private $dbModel;

    /**
     * @var array
     */
    private $tables;

    /**
     * Store constructor.
     *
     * @param DBModel $dbModel
     * @param array   $tables
     */
    public function __construct(DBModel $dbModel, $tables)
    {
        $this->dbModel = $dbModel;
        $this->tables = $tables;
    }

    /**
     * @return bool
     */
    public function checkSetup()
    {
        $db = $this->connect();
        $result = true;
        foreach ($this->tables as $table) {
            try {
                $sql = "SELECT 1 FROM ".$table." LIMIT 1;";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $result = $result && true;
            } catch (\PDOException $e) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Setup database.
     */
    public function setupDB()
    {
        $db = $this->connect();
        foreach ($this->tables as $table) {
            $sql = file_get_contents("sql/".$table.".sql");
            $stmt = $db->prepare($sql);
            $stmt->execute();
        }
    }

    /**
     * @return \PDO
     */
    protected function connect()
    {
        $db = new \PDO(
            'mysql:host='.$this->dbModel->getHost().';dbname='.$this->dbModel->getName(),
            $this->dbModel->getUser(),
            $this->dbModel->getPassword()
        );

        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $db;
    }
}
