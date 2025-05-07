-- Create database
CREATE DATABASE IF NOT EXISTS invoice_approval_system;
USE invoice_approval_system;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'subgerente', 'gerente', 'contador') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Invoices table
CREATE TABLE IF NOT EXISTS invoices (
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
);

-- Invoice items table
CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    concept VARCHAR(100) NOT NULL,
    description TEXT,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

-- Invoice approvals table
CREATE TABLE IF NOT EXISTS invoice_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    user_id INT NOT NULL,
    user_role VARCHAR(20) NOT NULL,
    action ENUM('approve', 'reject') NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Invoice views table
CREATE TABLE IF NOT EXISTS invoice_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    user_id INT NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert admin user
INSERT INTO users (name, email, password, role) 
VALUES ('Administrador', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample users
INSERT INTO users (name, email, password, role) 
VALUES 
('Juan Pérez', 'subgerente@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'subgerente'),
('María López', 'gerente@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerente'),
('Carlos Rodríguez', 'contador@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'contador');

-- Insert sample invoices
INSERT INTO invoices (invoice_number, date, supplier_name, nit, amount, subtotal, tax, description, sap_code, purchase_order, cost_center, payment_method, bank_account, due_date, payment_terms, status, created_by) 
VALUES 
('FAC-001', '2023-05-15', 'Proveedor A', '900123456-7', 1190000.00, 1000000.00, 190000.00, 'Compra de materiales de oficina', 'SAP001', 'OC-2023-001', 'CC-ADM', 'Transferencia', '123456789', '2023-06-15', 'Pago a 30 días', 'pendiente', 1),
('FAC-002', '2023-05-20', 'Proveedor B', '800987654-3', 2380000.00, 2000000.00, 380000.00, 'Servicios de consultoría', 'SAP002', 'OC-2023-002', 'CC-IT', 'Cheque', '987654321', '2023-06-20', 'Pago a 30 días', 'aprobado_subgerente', 1),
('FAC-003', '2023-05-25', 'Proveedor C', '700456789-1', 3570000.00, 3000000.00, 570000.00, 'Equipos de cómputo', 'SAP003', 'OC-2023-003', 'CC-IT', 'Transferencia', '456789123', '2023-06-25', 'Pago a 30 días', 'aprobado_gerente', 1),
('FAC-004', '2023-06-01', 'Proveedor D', '600123789-5', 4760000.00, 4000000.00, 760000.00, 'Servicios de mantenimiento', 'SAP004', 'OC-2023-004', 'CC-MNT', 'Transferencia', '789123456', '2023-07-01', 'Pago a 30 días', 'completado', 1),
('FAC-005', '2023-06-05', 'Proveedor E', '500987321-2', 5950000.00, 5000000.00, 950000.00, 'Materiales de construcción', 'SAP005', 'OC-2023-005', 'CC-CONST', 'Cheque', '321789456', '2023-07-05', 'Pago a 30 días', 'rechazado', 1);

-- Insert sample invoice items
INSERT INTO invoice_items (invoice_id, concept, description, quantity, unit_price, total) 
VALUES 
(1, 'Papel carta', 'Resmas de papel carta', 20, 12000.00, 240000.00),
(1, 'Bolígrafos', 'Cajas de bolígrafos', 10, 24000.00, 240000.00),
(1, 'Carpetas', 'Paquetes de carpetas', 15, 18000.00, 270000.00),
(1, 'Grapadoras', 'Grapadoras de escritorio', 5, 50000.00, 250000.00),
(2, 'Consultoría IT', 'Horas de consultoría en sistemas', 40, 50000.00, 2000000.00),
(3, 'Computadores', 'Computadores portátiles', 3, 1000000.00, 3000000.00),
(4, 'Mantenimiento AC', 'Servicio de mantenimiento de aires acondicionados', 10, 400000.00, 4000000.00),
(5, 'Cemento', 'Bultos de cemento', 100, 25000.00, 2500000.00),
(5, 'Arena', 'Metros cúbicos de arena', 20, 75000.00, 1500000.00),
(5, 'Varillas', 'Varillas de acero', 100, 10000.00, 1000000.00);

-- Insert sample approvals
INSERT INTO invoice_approvals (invoice_id, user_id, user_role, action, comments, created_at) 
VALUES 
(2, 2, 'subgerente', 'approve', 'Aprobado según presupuesto', '2023-05-21 10:15:00'),
(3, 2, 'subgerente', 'approve', 'Aprobado según presupuesto', '2023-05-26 09:30:00'),
(3, 3, 'gerente', 'approve', 'Aprobado, equipos necesarios', '2023-05-27 14:45:00'),
(4, 2, 'subgerente', 'approve', 'Aprobado según contrato', '2023-06-02 11:20:00'),
(4, 3, 'gerente', 'approve', 'Aprobado, servicio programado', '2023-06-03 10:10:00'),
(4, 4, 'contador', 'approve', 'Aprobado para pago', '2023-06-04 15:30:00'),
(5, 2, 'subgerente', 'approve', 'Aprobado según presupuesto', '2023-06-06 09:45:00'),
(5, 3, 'gerente', 'reject', 'Rechazado, precios por encima del mercado', '2023-06-07 14:20:00');

-- Insert sample views
INSERT INTO invoice_views (invoice_id, user_id, viewed_at) 
VALUES 
(1, 1, '2023-05-15 15:30:00'),
(1, 2, '2023-05-16 10:15:00'),
(2, 1, '2023-05-20 16:45:00'),
(2, 2, '2023-05-21 09:30:00'),
(3, 1, '2023-05-25 14:20:00'),
(3, 2, '2023-05-26 08:45:00'),
(3, 3, '2023-05-27 13:10:00'),
(4, 1, '2023-06-01 11:30:00'),
(4, 2, '2023-06-02 10:15:00'),
(4, 3, '2023-06-03 09:45:00'),
(4, 4, '2023-06-04 14:20:00'),
(5, 1, '2023-06-05 15:40:00'),
(5, 2, '2023-06-06 08:30:00'),
(5, 3, '2023-06-07 13:15:00');
