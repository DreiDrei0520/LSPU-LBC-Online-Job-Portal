<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'appjobsystem';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            throw $e;
        }

        return $this->conn;
    }
}
?>