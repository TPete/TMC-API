<?php

namespace TinyMediaCenter\API\Service\Store;

use TinyMediaCenter\API\Model\Database;

/**
 * Class AbstractStore
 */
abstract class AbstractStore implements StoreInterface
{
    /**
     * @var Database
     */
    private $dbModel;

    /**
     * @var array
     */
    private $tables;

    /**
     * Store constructor.
     *
     * @param Database $dbModel
     * @param array    $tables
     */
    public function __construct(Database $dbModel, $tables)
    {
        $this->dbModel = $dbModel;
        $this->tables = $tables;
    }

    /**
     * @param Database $dbModel
     *
     * @return bool
     */
    public function checkSetup(Database $dbModel = null)
    {
        $db = $this->connect($dbModel);
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
    public function setup()
    {
        $db = $this->connect();
        foreach ($this->tables as $table) {
            $sql = file_get_contents("sql/".$table.".sql");
            $stmt = $db->prepare($sql);
            $stmt->execute();
        }
    }

    /**
     * @param Database $dbModel
     *
     * @return \PDO
     */
    protected function connect(Database $dbModel = null)
    {
        if (null === $dbModel) {
            $dbModel = $this->dbModel;
        }

        $db = new \PDO(
            'mysql:host='.$dbModel->getHost().';dbname='.$dbModel->getName(),
            $dbModel->getUser(),
            $dbModel->getPassword()
        );

        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $db;
    }
}
