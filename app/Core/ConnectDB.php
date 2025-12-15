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
        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $this->host = $config['host'] ?? 'localhost';
        $this->dbname = $config['dbname'] ?? 'quan_ly_chi_tieu';
        $this->username = $config['username'] ?? 'root';
        $this->password = $config['password'] ?? '';

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->connection = new \PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // [BẢO MẬT] Không echo lỗi chi tiết ra màn hình
            error_log("DB Connection Error: " . $e->getMessage()); // Ghi vào log server
            die("Hệ thống đang bảo trì kết nối cơ sở dữ liệu. Vui lòng quay lại sau."); 
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }
}