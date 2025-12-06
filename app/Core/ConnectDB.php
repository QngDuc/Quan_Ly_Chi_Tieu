<?php
namespace App\Core;

class ConnectDB
{
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $connection;

    public function __construct()
    {
        // Load database configuration
        $config = require dirname(__DIR__, 2) . '/config/database.php';

        $this->host = $config['host'] ?? 'localhost';
        $this->dbname = $config['dbname'] ?? 'mydatabase';
        $this->username = $config['username'] ?? 'root';
        $this->password = $config['password'] ?? '';

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->connection = new \PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }
}