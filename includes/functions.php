<?php
// User functions
function getUserById($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch();
}

function getUserByEmail($email) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    return $stmt->fetch();
}

function emailExists($email) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}

function createUser($name, $email, $password, $role) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':role', $role);
    return $stmt->execute();
}

// Invoice functions
function getFilteredInvoices($date = '', $status = '', $supplier = '') {
    $conn = getDbConnection();
    
    $sql = "SELECT * FROM invoices WHERE 1=1";
    $params = [];
    
    if (!empty($date)) {
        $sql .= " AND date = :date";
        $params[':date'] = $date;
    }
    
    if (!empty($status)) {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }
    
    if (!empty($supplier)) {
        $sql .= " AND supplier_name LIKE :supplier";
        $params[':supplier'] = "%$supplier%";
    }
    
    $sql .= " ORDER BY id DESC";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

function getInvoiceById($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM invoices WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch();
}

function getInvoiceItems($invoice_id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM invoice_items WHERE invoice_id = :invoice_id");
    $stmt->bindParam(':invoice_id', $invoice_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getInvoiceApprovals($invoice_id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT a.*, u.name as user_name, u.role as user_role
        FROM invoice_approvals a
        JOIN users u ON a.user_id = u.id
        WHERE a.invoice_id = :invoice_id
        ORDER BY a.created_at ASC
    ");
    $stmt->bindParam(':invoice_id', $invoice_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

function markInvoiceAsViewed($invoice_id, $user_id) {
    $conn = getDbConnection();
    
    // Check if already viewed
    $stmt = $conn->prepare("SELECT COUNT(*) FROM invoice_views WHERE invoice_id = :invoice_id AND user_id = :user_id");
    $stmt->bindParam(':invoice_id', $invoice_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        // Insert new view record
        $stmt = $conn->prepare("INSERT INTO invoice_views (invoice_id, user_id) VALUES (:invoice_id, :user_id)");
        $stmt->bindParam(':invoice_id', $invoice_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    } else {
        // Update view timestamp
        $stmt = $conn->prepare("UPDATE invoice_views SET viewed_at = CURRENT_TIMESTAMP WHERE invoice_id = :invoice_id AND user_id = :user_id");
        $stmt->bindParam(':invoice_id', $invoice_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }
}

function hasViewedInvoice($invoice_id, $user_id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM invoice_views WHERE invoice_id = :invoice_id AND user_id = :user_id");
    $stmt->bindParam(':invoice_id', $invoice_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}

function addInvoice($invoice_number, $date, $supplier_name, $nit, $amount, $description, $file_path, $sap_code, $purchase_order, $cost_center, $payment_method, $bank_account, $due_date, $payment_terms, $user_id) {
    $conn = getDbConnection();
    
    // Calculate subtotal and tax
    $subtotal = $amount / 1.19; // Assuming 19% tax
    $tax = $amount - $subtotal;
    
    $stmt = $conn->prepare("
        INSERT INTO invoices (
            invoice_number, date, supplier_name, nit, amount, subtotal, tax,
            description, file_path, sap_code, purchase_order, cost_center,
            payment_method, bank_account, due_date, payment_terms, created_by
        ) VALUES (
            :invoice_number, :date, :supplier_name, :nit, :amount, :subtotal, :tax,
            :description, :file_path, :sap_code, :purchase_order, :cost_center,
            :payment_method, :bank_account, :due_date, :payment_terms, :created_by
        )
    ");
    
    $stmt->bindParam(':invoice_number', $invoice_number);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':supplier_name', $supplier_name);
    $stmt->bindParam(':nit', $nit);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':subtotal', $subtotal);
    $stmt->bindParam(':tax', $tax);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':file_path', $file_path);
    $stmt->bindParam(':sap_code', $sap_code);
    $stmt->bindParam(':purchase_order', $purchase_order);
    $stmt->bindParam(':cost_center', $cost_center);
    $stmt->bindParam(':payment_method', $payment_method);
    $stmt->bindParam(':bank_account', $bank_account);
    $stmt->bindParam(':due_date', $due_date);
    $stmt->bindParam(':payment_terms', $payment_terms);
    $stmt->bindParam(':created_by', $user_id);
    
    if ($stmt->execute()) {
        return $conn->lastInsertId();
    }
    
    return false;
}

function addInvoiceItem($invoice_id, $concept, $description, $quantity, $unit_price, $total) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        INSERT INTO invoice_items (invoice_id, concept, description, quantity, unit_price, total)
        VALUES (:invoice_id, :concept, :description, :quantity, :unit_price, :total)
    ");
    
    $stmt->bindParam(':invoice_id', $invoice_id);
    $stmt->bindParam(':concept', $concept);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':unit_price', $unit_price);
    $stmt->bindParam(':total', $total);
    
    return $stmt->execute();
}

function approveInvoice($invoice_id, $user_id, $role, $comments = '') {
    $conn = getDbConnection();

    try {
        $conn->beginTransaction();

        // Registrar la aprobación del rol actual
        $stmt = $conn->prepare("
            INSERT INTO invoice_approvals (invoice_id, user_id, user_role, action, comments)
            VALUES (:invoice_id, :user_id, :user_role, 'approve', :comments)
        ");
        $stmt->bindParam(':invoice_id', $invoice_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':user_role', $role);
        $stmt->bindParam(':comments', $comments);
        $stmt->execute();

        // Verificar si ya existe una aprobación de cada uno de los 3 roles
        $stmt = $conn->prepare("
            SELECT DISTINCT user_role FROM invoice_approvals
            WHERE invoice_id = :invoice_id AND action = 'approve'
        ");
        $stmt->bindParam(':invoice_id', $invoice_id);
        $stmt->execute();
        $roles_approved = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Si los tres roles han aprobado, marcar como completado
        $required_roles = ['subgerente', 'gerente', 'contador'];
        if (count(array_intersect($required_roles, $roles_approved)) === 3) {
            $stmt = $conn->prepare("UPDATE invoices SET status = 'completado' WHERE id = :invoice_id");
            $stmt->bindParam(':invoice_id', $invoice_id);
            $stmt->execute();
        } else {
            // Si no está completado aún, poner el estado como 'en proceso'
            $stmt = $conn->prepare("UPDATE invoices SET status = 'en_proceso' WHERE id = :invoice_id");
            $stmt->bindParam(':invoice_id', $invoice_id);
            $stmt->execute();
        }

        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error al aprobar factura: " . $e->getMessage());
        return false;
    }
}


function rejectInvoice($invoice_id, $user_id, $role, $comments = '') {
    $conn = getDbConnection();
    
    try {
        $conn->beginTransaction();
        
        // Update invoice status to rejected
        $status = 'rechazado';
        $stmt = $conn->prepare("UPDATE invoices SET status = :status WHERE id = :invoice_id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':invoice_id', $invoice_id);
        $stmt->execute();
        
        // Record rejection action
        $action = 'reject';
        $stmt = $conn->prepare("
            INSERT INTO invoice_approvals (invoice_id, user_id, user_role, action, comments)
            VALUES (:invoice_id, :user_id, :user_role, :action, :comments)
        ");
        
        $stmt->bindParam(':invoice_id', $invoice_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':user_role', $role);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':comments', $comments);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        return false;
    }
}

// Modificar la función canApproveInvoice para dar permisos completos a todos los roles
function canApproveInvoice($role, $status) {
    // Si la factura ya está completada o rechazada, nadie puede aprobarla
    if ($status == 'completado' || $status == 'rechazado') {
        return false;
    }
    
    // Todos los roles pueden aprobar en cualquier estado válido
    switch ($status) {
        case 'pendiente':
        case 'aprobado_subgerente':
        case 'aprobado_gerente':
            return true;
        default:
            return false;
    }
}

function canRejectInvoice($role, $status) {
    // Cualquier rol puede rechazar si no está completado o rechazado
    return $status != 'completado' && $status != 'rechazado';
}

// Helper functions
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

function getStatusLabel($status) {
    switch ($status) {
        case 'pendiente':
            return 'Pendiente';
        case 'aprobado_subgerente':
            return 'Aprobado por Subgerente';
        case 'aprobado_gerente':
            return 'Aprobado por Gerente';
        case 'aprobado_contador':
            return 'Aprobado por Contador';
        case 'completado':
            return 'Completado';
        case 'rechazado':
            return 'Rechazado';
        default:
            return 'En proceso';
    }
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pendiente':
            return 'bg-warning';
        case 'aprobado_subgerente':
            return 'bg-info';
        case 'aprobado_gerente':
            return 'bg-primary';
        case 'aprobado_contador':
            return 'bg-info';
        case 'completado':
            return 'bg-success';
        case 'rechazado':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?>