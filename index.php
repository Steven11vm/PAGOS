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

// Obtener filtros
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$supplier_filter = isset($_GET['supplier']) ? $_GET['supplier'] : '';

// Obtener facturas según filtros
$invoices = getFilteredInvoices($date_filter, $status_filter, $supplier_filter);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Aprobación de Facturas</title>
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
                    <h1 class="h2">Panel de Control</h1>
                    <?php if (in_array($role, ['admin', 'contador'])): ?>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add_invoice.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Factura
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Filtros -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Filtros</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label for="date" class="form-label">Fecha</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Estado</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="pendiente" <?php echo $status_filter == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="aprobado_subgerente" <?php echo $status_filter == 'aprobado_subgerente' ? 'selected' : ''; ?>>Aprobado por Subgerente</option>
                                    <option value="aprobado_gerente" <?php echo $status_filter == 'aprobado_gerente' ? 'selected' : ''; ?>>Aprobado por Gerente</option>
                                    <option value="aprobado_contador" <?php echo $status_filter == 'aprobado_contador' ? 'selected' : ''; ?>>Aprobado por Contador</option>
                                    <option value="completado" <?php echo $status_filter == 'completado' ? 'selected' : ''; ?>>Completado</option>
                                    <option value="rechazado" <?php echo $status_filter == 'rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="supplier" class="form-label">Proveedor</label>
                                <input type="text" class="form-control" id="supplier" name="supplier" value="<?php echo $supplier_filter; ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                                <a href="index.php" class="btn btn-secondary">Limpiar</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabla de Facturas -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Facturas</h5>
                    </div>
                    <div class="card-body">
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
                                    <?php if (count($invoices) > 0): ?>
                                        <?php foreach ($invoices as $invoice): ?>
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
                                                     
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No se encontraron facturas</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>
</html>
