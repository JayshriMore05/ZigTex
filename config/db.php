<?php
// config/db.php - FINAL WORKING VERSION FOR PORT 3307
class Database
{
    private $host = "localhost";
    private $port = "3307";      // Your MySQL port
    private $db_name = "zigtex_db";
    private $username = "root";
    private $password = "";      // Usually empty for XAMPP
    
    public function getConnection()
    {
        try {
            $conn = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password
            );
            
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            return $conn;
            
        } catch (PDOException $e) {
            // If empty password fails, try "root"
            if ($this->password === "") {
                try {
                    $conn = new PDO(
                        "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4",
                        $this->username,
                        "root"
                    );
                    
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    
                    return $conn;
                    
                } catch (PDOException $e2) {
                    die("❌ Database connection failed. Please check:<br>
                        1. MySQL is running in XAMPP<br>
                        2. Database 'zigtex_db' exists<br>
                        3. Try password 'root' if empty doesn't work<br>
                        Error: " . $e2->getMessage());
                }
            }
            die("❌ Connection error: " . $e->getMessage());
        }
    }
}

// Helper function for easy database access
function db() {
    static $connection = null;
    if ($connection === null) {
        $database = new Database();
        $connection = $database->getConnection();
    }
    return $connection;
}
?>
