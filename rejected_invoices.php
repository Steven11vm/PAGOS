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

// Obtener facturas rechazadas
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT * FROM invoices WHERE status = 'rechazado' ORDER BY date DESC");
$stmt->execute();
$rejected_invoices = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturas Rechazadas - Sistema de Aprobación</title>
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
                    <h1 class="h2">Facturas Rechazadas</h1>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Listado de Facturas Rechazadas</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($rejected_invoices) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Fecha</th>
                                            <th>Proveedor</th>
                                            <th>NIT</th>
                                            <th>Valor</th>
                                            <th>Rechazada por</th>
                                            <th>Motivo</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rejected_invoices as $invoice): 
                                            // Obtener detalles del rechazo
                                            $stmt = $conn->prepare("
                                                SELECT a.*, u.name as user_name, u.role as user_role
                                                FROM invoice_approvals a
                                                JOIN users u ON a.user_id = u.id
                                                WHERE a.invoice_id = :invoice_id AND a.action = 'reject'
                                                ORDER BY a.created_at DESC LIMIT 1
                                            ");
                                            $stmt->bindParam(':invoice_id', $invoice['id']);
                                            $stmt->execute();
                                            $rejection = $stmt->fetch();
                                        ?>
                                            <tr>
                                                <td><?php echo $invoice['id']; ?></td>
                                                <td><?php echo formatDate($invoice['date']); ?></td>
                                                <td><?php echo $invoice['supplier_name']; ?></td>
                                                <td><?php echo $invoice['nit']; ?></td>
                                                <td>$<?php echo number_format($invoice['amount'], 2, ',', '.'); ?></td>
                                                <td>
                                                    <?php if ($rejection): ?>
                                                        <?php echo $rejection['user_name']; ?> (<?php echo ucfirst($rejection['user_role']); ?>)
                                                    <?php else: ?>
                                                        <span class="text-muted">No disponible</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($rejection): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo htmlspecialchars($rejection['comments']); ?>">
                                                            Ver motivo
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">No disponible</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No hay facturas rechazadas en el sistema.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>
