<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user role
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$role = $user['role'];

// Get report type
$report_type = isset($_GET['type']) ? $_GET['type'] : 'monthly';

// Get report data
$conn = getDbConnection();
$report_data = [];
$chart_data = [];

if ($report_type == 'monthly') {
    // Monthly report - invoices by month
    $current_year = date('Y');
    $stmt = $conn->prepare("
        SELECT 
            MONTH(date) as month, 
            COUNT(*) as total_invoices,
            SUM(CASE WHEN status = 'completado' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rechazado' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status NOT IN ('completado', 'rechazado') THEN 1 ELSE 0 END) as pending,
            SUM(amount) as total_amount
        FROM invoices 
        WHERE YEAR(date) = :year
        GROUP BY MONTH(date)
        ORDER BY MONTH(date)
    ");
    $stmt->bindParam(':year', $current_year);
    $stmt->execute();
    $report_data = $stmt->fetchAll();
    
    // Prepare chart data
    $months = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $chart_labels = [];
    $chart_values_approved = [];
    $chart_values_rejected = [];
    $chart_values_pending = [];
    
    foreach ($report_data as $row) {
        $chart_labels[] = $months[$row['month']];
        $chart_values_approved[] = $row['approved'];
        $chart_values_rejected[] = $row['rejected'];
        $chart_values_pending[] = $row['pending'];
    }
    
    $chart_data = [
        'labels' => json_encode($chart_labels),
        'approved' => json_encode($chart_values_approved),
        'rejected' => json_encode($chart_values_rejected),
        'pending' => json_encode($chart_values_pending)
    ];
} elseif ($report_type == 'supplier') {
    // Supplier report - invoices by supplier
    $stmt = $conn->prepare("
        SELECT 
            supplier_name,
            COUNT(*) as total_invoices,
            SUM(CASE WHEN status = 'completado' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rechazado' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status NOT IN ('completado', 'rechazado') THEN 1 ELSE 0 END) as pending,
            SUM(amount) as total_amount
        FROM invoices 
        GROUP BY supplier_name
        ORDER BY COUNT(*) DESC
        LIMIT 10
    ");
    $stmt->execute();
    $report_data = $stmt->fetchAll();
    
    // Prepare chart data
    $chart_labels = [];
    $chart_values = [];
    
    foreach ($report_data as $row) {
        $chart_labels[] = $row['supplier_name'];
        $chart_values[] = $row['total_amount'];
    }
    
    $chart_data = [
        'labels' => json_encode($chart_labels),
        'values' => json_encode($chart_values)
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Sistema de Aprobación</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reportes</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="reports.php?type=monthly" class="btn btn-sm <?php echo $report_type == 'monthly' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                <i class="fas fa-calendar-alt me-1"></i> Mensual
                            </a>
                            <a href="reports.php?type=supplier" class="btn btn-sm <?php echo $report_type == 'supplier' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                <i class="fas fa-building me-1"></i> Por Proveedor
                            </a>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Imprimir
                        </button>
                    </div>
                </div>
                
                <?php if ($report_type == 'monthly'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Facturas por Mes (<?php echo date('Y'); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <canvas id="monthlyChart" height="300"></canvas>
                                </div>
                                <div class="col-md-4">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr class="table-light">
                                                    <th>Mes</th>
                                                    <th>Facturas</th>
                                                    <th>Monto Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $total_invoices = 0;
                                                $total_amount = 0;
                                                
                                                foreach ($report_data as $row): 
                                                    $total_invoices += $row['total_invoices'];
                                                    $total_amount += $row['total_amount'];
                                                ?>
                                                    <tr>
                                                        <td><?php echo $months[$row['month']]; ?></td>
                                                        <td><?php echo $row['total_invoices']; ?></td>
                                                        <td>$<?php echo number_format($row['total_amount'], 2, ',', '.'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-light">
                                                    <th>Total</th>
                                                    <th><?php echo $total_invoices; ?></th>
                                                    <th>$<?php echo number_format($total_amount, 2, ',', '.'); ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Detalles por Mes</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Mes</th>
                                            <th>Total Facturas</th>
                                            <th>Aprobadas</th>
                                            <th>Rechazadas</th>
                                            <th>Pendientes</th>
                                            <th>Monto Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $row): ?>
                                            <tr>
                                                <td><?php echo $months[$row['month']]; ?></td>
                                                <td><?php echo $row['total_invoices']; ?></td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo $row['approved']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger"><?php echo $row['rejected']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning"><?php echo $row['pending']; ?></span>
                                                </td>
                                                <td>$<?php echo number_format($row['total_amount'], 2, ',', '.'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                        // Monthly chart
                        var ctx = document.getElementById('monthlyChart').getContext('2d');
                        var monthlyChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: <?php echo $chart_data['labels']; ?>,
                                datasets: [
                                    {
                                        label: 'Aprobadas',
                                        data: <?php echo $chart_data['approved']; ?>,
                                        backgroundColor: 'rgba(40, 167, 69, 0.7)',
                                        borderColor: 'rgba(40, 167, 69, 1)',
                                        borderWidth: 1
                                    },
                                    {
                                        label: 'Rechazadas',
                                        data: <?php echo $chart_data['rejected']; ?>,
                                        backgroundColor: 'rgba(220, 53, 69, 0.7)',
                                        borderColor: 'rgba(220, 53, 69, 1)',
                                        borderWidth: 1
                                    },
                                    {
                                        label: 'Pendientes',
                                        data: <?php echo $chart_data['pending']; ?>,
                                        backgroundColor: 'rgba(255, 193, 7, 0.7)',
                                        borderColor: 'rgba(255, 193, 7, 1)',
                                        borderWidth: 1
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        }
                                    }
                                }
                            }
                        });
                    </script>
                <?php elseif ($report_type == 'supplier'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Top 10 Proveedores por Monto</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <canvas id="supplierChart" height="300"></canvas>
                                </div>
                                <div class="col-md-4">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr class="table-light">
                                                    <th>Proveedor</th>
                                                    <th>Monto Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $total_amount = 0;
                                                
                                                foreach ($report_data as $row): 
                                                    $total_amount += $row['total_amount'];
                                                ?>
                                                    <tr>
                                                        <td><?php echo $row['supplier_name']; ?></td>
                                                        <td>$<?php echo number_format($row['total_amount'], 2, ',', '.'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-light">
                                                    <th>Total</th>
                                                    <th>$<?php echo number_format($total_amount, 2, ',', '.'); ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Detalles por Proveedor</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Proveedor</th>
                                            <th>Total Facturas</th>
                                            <th>Aprobadas</th>
                                            <th>Rechazadas</th>
                                            <th>Pendientes</th>
                                            <th>Monto Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $row): ?>
                                            <tr>
                                                <td><?php echo $row['supplier_name']; ?></td>
                                                <td><?php echo $row['total_invoices']; ?></td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo $row['approved']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger"><?php echo $row['rejected']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning"><?php echo $row['pending']; ?></span>
                                                </td>
                                                <td>$<?php echo number_format($row['total_amount'], 2, ',', '.'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                        // Supplier chart
                        var ctx = document.getElementById('supplierChart').getContext('2d');
                        var supplierChart = new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: <?php echo $chart_data['labels']; ?>,
                                datasets: [{
                                    data: <?php echo $chart_data['values']; ?>,
                                    backgroundColor: [
                                        'rgba(54, 162, 235, 0.7)',
                                        'rgba(255, 99, 132, 0.7)',
                                        'rgba(255, 206, 86, 0.7)',
                                        'rgba(75, 192, 192, 0.7)',
                                        'rgba(153, 102, 255, 0.7)',
                                        'rgba(255, 159, 64, 0.7)',
                                        'rgba(199, 199, 199, 0.7)',
                                        'rgba(83, 102, 255, 0.7)',
                                        'rgba(40, 159, 64, 0.7)',
                                        'rgba(210, 199, 199, 0.7)'
                                    ],
                                    borderColor: [
                                        'rgba(54, 162, 235, 1)',
                                        'rgba(255, 99, 132, 1)',
                                        'rgba(255, 206, 86, 1)',
                                        'rgba(75, 192, 192, 1)',
                                        'rgba(153, 102, 255, 1)',
                                        'rgba(255, 159, 64, 1)',
                                        'rgba(199, 199, 199, 1)',
                                        'rgba(83, 102, 255, 1)',
                                        'rgba(40, 159, 64, 1)',
                                        'rgba(210, 199, 199, 1)'
                                    ],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {
                                        position: 'right',
                                    }
                                }
                            }
                        });
                    </script>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
