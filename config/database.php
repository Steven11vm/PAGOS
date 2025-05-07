<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'invoice_approval_system');

// Create database connection
function getDbConnection() {
    static $conn;
    
    if ($conn === null) {
        try {
            // First, connect without specifying a database
            $conn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if database exists, if not create it
            $conn->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`");
            
            // Select the database
            $conn->exec("USE `" . DB_NAME . "`");
            
            // Set additional attributes
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $conn->exec("SET NAMES utf8");
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $conn;
}

// Ensure connection is available before proceeding
$conn = getDbConnection();


// Create database tables if they don't exist
function createDatabaseTables() {
    $conn = getDbConnection();
    
    // Users table
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'subgerente', 'gerente', 'contador') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Invoices table
    $conn->exec("CREATE TABLE IF NOT EXISTS invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_number VARCHAR(50) NOT NULL,
        date DATE NOT NULL,
        supplier_name VARCHAR(100) NOT NULL,
        nit VARCHAR(50) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        subtotal DECIMAL(12,2) DEFAULT 0,
        tax DECIMAL(12,2) DEFAULT 0,
        description TEXT,
        file_path VARCHAR(255),
        sap_code VARCHAR(50),
        purchase_order VARCHAR(50),
        cost_center VARCHAR(50),
        payment_method VARCHAR(50),
        bank_account VARCHAR(100),
        due_date DATE,
        payment_terms VARCHAR(100),
        status ENUM('pendiente', 'aprobado_subgerente', 'aprobado_gerente', 'aprobado_contador', 'completado', 'rechazado') DEFAULT 'pendiente',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");
    
    // Invoice items table
    $conn->exec("CREATE TABLE IF NOT EXISTS invoice_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        concept VARCHAR(100) NOT NULL,
        description TEXT,
        quantity INT NOT NULL,
        unit_price DECIMAL(12,2) NOT NULL,
        total DECIMAL(12,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
    )");
    
    // Invoice approvals table
    $conn->exec("CREATE TABLE IF NOT EXISTS invoice_approvals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        user_id INT NOT NULL,
        user_role VARCHAR(20) NOT NULL,
        action ENUM('approve', 'reject') NOT NULL,
        comments TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    
    // Invoice views table
    $conn->exec("CREATE TABLE IF NOT EXISTS invoice_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        user_id INT NOT NULL,
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    $conn->exec("CREATE TABLE IF NOT EXISTS invoice_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        user_id INT NOT NULL,
        view_date DATETIME NOT NULL,
        UNIQUE KEY unique_view (invoice_id, user_id)
    )");
    
    
    $conn->exec ("CREATE TABLE IF NOT EXISTS approval_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        user_id INT NOT NULL,
        approval_time DATETIME NOT NULL,
        INDEX (invoice_id),
        INDEX (user_id)
    )");
    
    // Create admin user if not exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = 'admin@example.com'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES ('Administrador', 'admin@example.com', :password, 'admin')");
        $stmt->bindParam(':password', $password);
        $stmt->execute();
    }
}

// Create database tables
createDatabaseTables();

?>
