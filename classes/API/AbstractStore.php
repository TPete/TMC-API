<?php
namespace TinyMediaCenter\API;

/**
 * Class AbstractStore
 */
abstract class AbstractStore
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $db;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @var array
     */
    private $tables;

    /**
     * Store constructor.
     *
     * @param array $config
     * @param array $tables
     */
    public function __construct($config, $tables)
    {
        $this->host = $config["host"];
        $this->db = $config["name"];
        $this->user = $config["user"];
        $this->password = $config["password"];
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
        $db = new \PDO("mysql:host=".$this->host.";dbname=".$this->db, $this->user, $this->password);
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $db;
    }
}
