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

// Insert sample invoices if they don't exist
$stmt = $conn->prepare("SELECT COUNT(*) FROM invoices");
$stmt->execute();
$invoiceCount = $stmt->fetchColumn();

if ($invoiceCount == 0) {
    // Insert sample data for invoices
    $conn->exec("
        INSERT INTO invoices (invoice_number, date, supplier_name, nit, amount, subtotal, tax, description, sap_code, purchase_order, cost_center, payment_method, bank_account, due_date, payment_terms, status, created_by) 
        VALUES 
        ('FAC-001', '2023-05-15', 'Proveedor A', '900123456-7', 1190000.00, 1000000.00, 190000.00, 'Compra de materiales de oficina', 'SAP001', 'OC-2023-001', 'CC-ADM', 'Transferencia', '123456789', '2023-06-15', 'Pago a 30 días', 'pendiente', 1),
        ('FAC-002', '2023-05-20', 'Proveedor B', '800987654-3', 2380000.00, 2000000.00, 380000.00, 'Servicios de consultoría', 'SAP002', 'OC-2023-002', 'CC-IT', 'Cheque', '987654321', '2023-06-20', 'Pago a 30 días', 'aprobado_subgerente', 1),
        ('FAC-003', '2023-05-25', 'Proveedor C', '700456789-1', 3570000.00, 3000000.00, 570000.00, 'Equipos de cómputo', 'SAP003', 'OC-2023-003', 'CC-IT', 'Transferencia', '456789123', '2023-06-25', 'Pago a 30 días', 'aprobado_gerente', 1),
        ('FAC-004', '2023-06-01', 'Proveedor D', '600123789-5', 4760000.00, 4000000.00, 760000.00, 'Servicios de mantenimiento', 'SAP004', 'OC-2023-004', 'CC-MNT', 'Transferencia', '789123456', '2023-07-01', 'Pago a 30 días', 'completado', 1),
        ('FAC-005', '2023-06-05', 'Proveedor E', '500987321-2', 5950000.00, 5000000.00, 950000.00, 'Materiales de construcción', 'SAP005', 'OC-2023-005', 'CC-CONST', 'Cheque', '321789456', '2023-07-05', 'Pago a 30 días', 'rechazado', 1);
    ");
}

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
