<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener rol del usuario
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$role = $user['role'];

// Procesar formulario de aprobación si se ha enviado
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_invoice'])) {
    $invoice_id = $_POST['invoice_id'];
    $comments = $_POST['comments'] ?? '';
    
    // Verificar que el usuario haya visto los detalles de la factura
    if (!hasUserViewedInvoice($invoice_id, $user_id)) {
        $_SESSION['error_message'] = "Debe ver los detalles de la factura antes de aprobarla";
    }
    // Verificar que se haya confirmado la aprobación
    elseif (!isset($_POST['confirm_approval'])) {
        $_SESSION['error_message'] = "Debe confirmar la aprobación de la factura";
    }
    else {
        $result = approveInvoice($invoice_id, $user_id, $role, $comments);
        
        if ($result) {
            // Registrar la hora exacta de la aprobación
            logApprovalTime($invoice_id, $user_id, date('Y-m-d H:i:s'));
            $_SESSION['success_message'] = "Factura #$invoice_id aprobada correctamente";
        } else {
            $_SESSION['error_message'] = "Error al aprobar la factura #$invoice_id";
        }
    }
    
    // Redireccionar para evitar reenvío del formulario
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Obtener facturas pendientes según el rol del usuario
$pending_invoices = getPendingInvoicesForRole($role);

// Función auxiliar para obtener facturas pendientes para un rol específico
function getPendingInvoicesForRole($role) {
    $conn = getDbConnection();
    $sql = "SELECT * FROM invoices WHERE 1=1";
    
    switch ($role) {
        case 'subgerente':
            $sql .= " AND status = 'pendiente'";
            break;
        case 'gerente':
            $sql .= " AND status = 'aprobado_subgerente'";
            break;
        case 'contador':
            $sql .= " AND status = 'aprobado_gerente'";
            break;
        case 'admin':
            $sql .= " AND status IN ('pendiente', 'aprobado_subgerente', 'aprobado_gerente')";
            break;
        default:
            return [];
    }
    
    $sql .= " ORDER BY date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Función para verificar si un usuario ha visto los detalles de una factura
function hasUserViewedInvoice($invoice_id, $user_id) {
    $conn = getDbConnection();
    $sql = "SELECT COUNT(*) as viewed FROM invoice_views WHERE invoice_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$invoice_id, $user_id]);
    $result = $stmt->fetch();
    return ($result['viewed'] > 0);
}

// Función para registrar la hora exacta de aprobación
function logApprovalTime($invoice_id, $user_id, $timestamp) {
    $conn = getDbConnection();
    $sql = "INSERT INTO approval_logs (invoice_id, user_id, approval_time) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$invoice_id, $user_id, $timestamp]);
    return ($stmt->rowCount() > 0);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendientes de Aprobación - Sistema de Aprobación</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Facturas Pendientes de Aprobación</h1>
                </div>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Facturas que Requieren su Aprobación</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($pending_invoices) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Fecha</th>
                                            <th>Proveedor</th>
                                            <th>NIT</th>
                                            <th>Valor</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_invoices as $invoice): ?>
                                            <tr>
                                                <td><?php echo $invoice['id']; ?></td>
                                                <td><?php echo formatDate($invoice['date']); ?></td>
                                                <td><?php echo $invoice['supplier_name']; ?></td>
                                                <td><?php echo $invoice['nit']; ?></td>
                                                <td>$<?php echo number_format($invoice['amount'], 2, ',', '.'); ?></td>
                                                <td>
                                                    <span class="badge <?php echo getStatusBadgeClass($invoice['status']); ?>">
                                                        <?php echo getStatusLabel($invoice['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        
                                                        <?php if (canApproveInvoice($role, $invoice['status'])): ?>
                                                        <!-- Solo mostrar botón de aprobar si la factura ha sido vista -->
                                                        <?php 
                                                        // Verificar si el usuario ha visto los detalles de esta factura
                                                        $has_viewed = hasUserViewedInvoice($invoice['id'], $user_id);
                                                        if ($has_viewed): 
                                                        ?>
                                                        <button type="button" class="btn btn-sm btn-success" title="Aprobar" 
                                                                data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $invoice['id']; ?>">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-secondary" disabled title="Debe ver los detalles antes de aprobar">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Modal de Aprobación -->
                                                        <div class="modal fade" id="approveModal<?php echo $invoice['id']; ?>" tabindex="-1" 
                                                             aria-labelledby="approveModalLabel<?php echo $invoice['id']; ?>" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header bg-success text-white">
                                                                        <h5 class="modal-title" id="approveModalLabel<?php echo $invoice['id']; ?>">
                                                                            Aprobar Factura #<?php echo $invoice['id']; ?>
                                                                        </h5>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <form method="POST" action="">
                                                                        <div class="modal-body">
                                                                            <div class="alert alert-warning">
                                                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                                                <strong>Aviso importante:</strong> Al aprobar esta factura, quedará registrado su nombre (<?php echo $user['name']; ?>), 
                                                                                rol (<?php echo ucfirst($role); ?>) y la fecha/hora exacta de la acción.
                                                                            </div>
                                                                            
                                                                            <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                                                            <div class="mb-3">
                                                                                <label for="comments<?php echo $invoice['id']; ?>" class="form-label">Comentarios (opcional)</label>
                                                                                <textarea class="form-control" id="comments<?php echo $invoice['id']; ?>" name="comments" rows="3"></textarea>
                                                                            </div>
                                                                            
                                                                            <div class="mb-3 form-check">
                                                                                <input type="checkbox" class="form-check-input" id="confirmCheck<?php echo $invoice['id']; ?>" name="confirm_approval" required>
                                                                                <label class="form-check-label" for="confirmCheck<?php echo $invoice['id']; ?>">
                                                                                    Confirmo que he revisado los detalles de esta factura y autorizo su aprobación
                                                                                </label>
                                                                                <div class="invalid-feedback">
                                                                                    Debe confirmar la aprobación para continuar
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                            <button type="submit" name="approve_invoice" class="btn btn-success">
                                                                                <i class="fas fa-check me-1"></i> Confirmar Aprobación
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (canRejectInvoice($role, $invoice['status'])): ?>
                                                        <a href="reject_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-danger" title="Rechazar">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No hay facturas pendientes de aprobación para su rol.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>