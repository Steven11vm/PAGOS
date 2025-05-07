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

// Verificar si se proporcionó ID de factura
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$invoice_id = $_GET['id'];
$invoice = getInvoiceById($invoice_id);

// Verificar si la factura existe
if (!$invoice) {
    header("Location: index.php");
    exit();
}

// Verificar si el usuario puede rechazar esta factura
if (!canRejectInvoice($role, $invoice['status'])) {
    $_SESSION['error_message'] = "No tiene permisos para rechazar esta factura en su estado actual.";
    header("Location: view_invoice.php?id=$invoice_id");
    exit();
}

$error = '';
$success = '';

// Procesar formulario de rechazo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['comments'])) {
        $error = 'Debe proporcionar un motivo para el rechazo';
    } else {
        $comments = $_POST['comments'];
        $result = rejectInvoice($invoice_id, $user_id, $role, $comments);
        
        if ($result) {
            $_SESSION['success_message'] = "Factura rechazada correctamente.";
            header("Location: view_invoice.php?id=$invoice_id");
            exit();
        } else {
            $error = 'Error al rechazar la factura';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechazar Factura - Sistema de Aprobación</title>
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
                    <h1 class="h2">Rechazar Factura #<?php echo $invoice['id']; ?></h1>
                    <a href="view_invoice.php?id=<?php echo $invoice_id; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Información de la Factura</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tr>
                                        <th style="width: 30%">Número de Factura:</th>
                                        <td><?php echo $invoice['invoice_number']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Fecha:</th>
                                        <td><?php echo formatDate($invoice['date']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Proveedor:</th>
                                        <td><?php echo $invoice['supplier_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>NIT:</th>
                                        <td><?php echo $invoice['nit']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Valor:</th>
                                        <td>$<?php echo number_format($invoice['amount'], 2, ',', '.'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Estado Actual:</th>
                                        <td>
                                            <span class="badge <?php echo getStatusBadgeClass($invoice['status']); ?>">
                                                <?php echo getStatusLabel($invoice['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Formulario de Rechazo</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="comments" class="form-label">Motivo del Rechazo *</label>
                                        <textarea class="form-control" id="comments" name="comments" rows="5" required></textarea>
                                        <div class="form-text">Por favor, proporcione un motivo detallado para el rechazo de esta factura.</div>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <p class="mb-0">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <strong>Advertencia:</strong> Al rechazar esta factura, se detendrá el proceso de aprobación y se notificará al proveedor.
                                            Esta acción quedará registrada en el sistema con su nombre y fecha.
                                        </p>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-times me-1"></i> Rechazar Factura
                                        </button>
                                        <a href="view_invoice.php?id=<?php echo $invoice_id; ?>" class="btn btn-outline-secondary">
                                            Cancelar
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
