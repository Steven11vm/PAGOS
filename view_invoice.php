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

// Marcar factura como vista por el usuario actual
markInvoiceAsViewed($invoice_id, $user_id);

// Obtener historial de aprobaciones
$approvals = getInvoiceApprovals($invoice_id);

// Verificar qué aprobaciones faltan
$has_subgerente_approval = false;
$has_gerente_approval = false;
$has_contador_approval = false;

foreach ($approvals as $approval) {
    if ($approval['action'] == 'approve') {
        if ($approval['user_role'] == 'subgerente' || ($approval['user_role'] == 'admin' && !$has_subgerente_approval)) {
            $has_subgerente_approval = true;
        } elseif ($approval['user_role'] == 'gerente' || ($approval['user_role'] == 'admin' && !$has_gerente_approval)) {
            $has_gerente_approval = true;
        } elseif ($approval['user_role'] == 'contador' || ($approval['user_role'] == 'admin' && !$has_contador_approval)) {
            $has_contador_approval = true;
        }
    }
}

// Determinar qué aprobaciones faltan
$pending_approvals = [];
if (!$has_subgerente_approval) {
    $pending_approvals[] = 'Subgerente';
}
if (!$has_gerente_approval) {
    $pending_approvals[] = 'Gerente';
}
if (!$has_contador_approval) {
    $pending_approvals[] = 'Contador';
}

// Procesar formulario de aprobación
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve'])) {
    $comments = $_POST['comments'] ?? '';
    $result = approveInvoice($invoice_id, $user_id, $role, $comments);
    
    if ($result) {
        $message = '<div class="alert alert-success">Factura aprobada correctamente</div>';
        // Actualizar datos de la factura
        $invoice = getInvoiceById($invoice_id);
        $approvals = getInvoiceApprovals($invoice_id);
        
        // Actualizar estado de aprobaciones
        $has_subgerente_approval = false;
        $has_gerente_approval = false;
        $has_contador_approval = false;
        
        foreach ($approvals as $approval) {
            if ($approval['action'] == 'approve') {
                if ($approval['user_role'] == 'subgerente' || ($approval['user_role'] == 'admin' && !$has_subgerente_approval)) {
                    $has_subgerente_approval = true;
                } elseif ($approval['user_role'] == 'gerente' || ($approval['user_role'] == 'admin' && !$has_gerente_approval)) {
                    $has_gerente_approval = true;
                } elseif ($approval['user_role'] == 'contador' || ($approval['user_role'] == 'admin' && !$has_contador_approval)) {
                    $has_contador_approval = true;
                }
            }
        }
        
        // Actualizar lista de aprobaciones pendientes
        $pending_approvals = [];
        if (!$has_subgerente_approval) {
            $pending_approvals[] = 'Subgerente';
        }
        if (!$has_gerente_approval) {
            $pending_approvals[] = 'Gerente';
        }
        if (!$has_contador_approval) {
            $pending_approvals[] = 'Contador';
        }
    } else {
        $message = '<div class="alert alert-danger">Error al aprobar la factura</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Factura - Sistema de Aprobación</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.0.279/web/pdf_viewer.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Detalles de Factura #<?php echo $invoice['id']; ?></h1>
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
                
                <?php echo $message; ?>
                
                <?php if (!empty($pending_approvals) && $invoice['status'] != 'completado' && $invoice['status'] != 'rechazado'): ?>
                <div class="alert alert-warning">
                    <strong><i class="fas fa-exclamation-triangle me-2"></i>Aprobaciones pendientes:</strong> 
                    <?php echo implode(', ', $pending_approvals); ?>
                </div>
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
                                        <th>Estado:</th>
                                        <td>
                                            <span class="badge <?php echo getStatusBadgeClass($invoice['status']); ?>">
                                                <?php echo getStatusLabel($invoice['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Descripción:</th>
                                        <td><?php echo nl2br($invoice['description']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Historial de Aprobaciones</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($approvals) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Usuario</th>
                                                    <th>Rol</th>
                                                    <th>Acción</th>
                                                    <th>Fecha</th>
                                                    <th>Comentarios</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($approvals as $approval): ?>
                                                    <tr>
                                                        <td><?php echo $approval['user_name']; ?></td>
                                                        <td><?php echo ucfirst($approval['user_role']); ?></td>
                                                        <td>
                                                            <span class="badge <?php echo $approval['action'] == 'approve' ? 'bg-success' : 'bg-danger'; ?>">
                                                                <?php echo $approval['action'] == 'approve' ? 'Aprobado' : 'Rechazado'; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo formatDateTime($approval['created_at']); ?></td>
                                                        <td><?php echo $approval['comments']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No hay historial de aprobaciones</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (canApproveInvoice($role, $invoice['status'])): ?>
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Aprobar Factura</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="comments" class="form-label">Comentarios (opcional)</label>
                                        <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                                    </div>
                                    <button type="submit" name="approve" class="btn btn-success">
                                        <i class="fas fa-check me-1"></i> Aprobar Factura
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Documento de Factura</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($invoice['file_path'])): ?>
                                    <div id="pdf-viewer" class="pdf-container" style="height: 600px; border: 1px solid #ddd;"></div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        No hay documento adjunto para esta factura
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Detalles SAP</h5>
                            </div>
                            <div class="card-body">
                                <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#sapDetailsModal">
                                    <i class="fas fa-list-ul me-1"></i> Ver Detalles SAP
                                </button>
                                
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <tr>
                                            <th>Código SAP:</th>
                                            <td><?php echo $invoice['sap_code']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Orden de Compra:</th>
                                            <td><?php echo $invoice['purchase_order']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Centro de Costos:</th>
                                            <td><?php echo $invoice['cost_center']; ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal de Detalles SAP -->
    <div class="modal fade" id="sapDetailsModal" tabindex="-1" aria-labelledby="sapDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="sapDetailsModalLabel">Detalles SAP de la Factura #<?php echo $invoice['id']; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Concepto</th>
                                    <th>Descripción</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $invoice_items = getInvoiceItems($invoice_id);
                                if (count($invoice_items) > 0):
                                    foreach ($invoice_items as $item):
                                ?>
                                <tr>
                                    <td><?php echo $item['concept']; ?></td>
                                    <td><?php echo $item['description']; ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['unit_price'], 2, ',', '.'); ?></td>
                                    <td>$<?php echo number_format($item['total'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center">No hay detalles disponibles</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end">Subtotal:</th>
                                    <td>$<?php echo number_format($invoice['subtotal'], 2, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">IVA (19%):</th>
                                    <td>$<?php echo number_format($invoice['tax'], 2, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Total:</th>
                                    <td>$<?php echo number_format($invoice['amount'], 2, ',', '.'); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Información Adicional:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <tr>
                                        <th>Forma de Pago:</th>
                                        <td><?php echo $invoice['payment_method']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Cuenta Bancaria:</th>
                                        <td><?php echo $invoice['bank_account']; ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <tr>
                                        <th>Fecha de Vencimiento:</th>
                                        <td><?php echo formatDate($invoice['due_date']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Condiciones de Pago:</th>
                                        <td><?php echo $invoice['payment_terms']; ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.0.279/build/pdf.min.js"></script>
    <script>
        <?php if (!empty($invoice['file_path'])): ?>
        // PDF.js viewer
        const pdfUrl = '<?php echo $invoice['file_path']; ?>';
        
        // The workerSrc property needs to be specified
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.0.279/build/pdf.worker.min.js';
        
        // Load the PDF
        const loadingTask = pdfjsLib.getDocument(pdfUrl);
        loadingTask.promise.then(function(pdf) {
            // Load the first page
            pdf.getPage(1).then(function(page) {
                const scale = 1.5;
                const viewport = page.getViewport({ scale: scale });
                
                // Prepare canvas using PDF page dimensions
                const container = document.getElementById('pdf-viewer');
                const canvas = document.createElement('canvas');
                container.appendChild(canvas);
                
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                // Render PDF page into canvas context
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                
                page.render(renderContext);
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>
