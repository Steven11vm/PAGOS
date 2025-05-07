<nav id="sidebarMenu" class="sidebar col-md-3 col-lg-2 d-md-block bg-light collapse">
    <div class="position-sticky pt-3">
        <!-- Logo de la empresa -->
        <div class="text-center mb-3">
            <?php if (file_exists('assets/img/logo.png')): ?>
                <img src="assets/img/logo.png" alt="Logo de la empresa" class="img-fluid sidebar-logo">
            <?php else: ?>
                <div class="sidebar-logo-placeholder">
                    <i class="fas fa-building"></i>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Información del usuario -->
        <div class="user-info mb-3">
            <div class="d-flex align-items-center p-3 bg-white rounded shadow-sm">
                <div class="user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="ms-2 flex-grow-1">
                    <div class="fw-bold"><?php echo $user['name']; ?></div>
                    <small class="text-muted"><?php echo ucfirst($role); ?></small>
                </div>
                <a href="logout.php" class="btn btn-sm btn-outline-danger" title="Cerrar sesión">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Panel de Control
                </a>
            </li>
            <?php if (in_array($role, ['admin'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'add_invoice.php' ? 'active' : ''; ?>" href="add_invoice.php">
                    <i class="fas fa-plus-circle"></i>
                    Nueva Factura
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pending_approvals.php' ? 'active' : ''; ?>" href="pending_approvals.php">
                    <i class="fas fa-clock"></i>
                    Pendientes de Aprobación
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'approved_invoices.php' ? 'active' : ''; ?>" href="approved_invoices.php">
                    <i class="fas fa-check-circle"></i>
                    Facturas Aprobadas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'rejected_invoices.php' ? 'active' : ''; ?>" href="rejected_invoices.php">
                    <i class="fas fa-times-circle"></i>
                    Facturas Rechazadas
                </a>
            </li>
            <?php if ($role == 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i>
                    Gestión de Usuarios
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Reportes</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="reports.php?type=monthly">
                    <i class="fas fa-chart-bar"></i>
                    Reporte Mensual
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php?type=supplier">
                    <i class="fas fa-building"></i>
                    Por Proveedor
                </a>
            </li>
        </ul>
    </div>
</nav>
<style>
    body {
  font-size: .875rem;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.feather {
  width: 16px;
  height: 16px;
  vertical-align: text-bottom;
}

/*
* Sidebar
*/

.sidebar {
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  z-index: 100; /* Behind the navbar */
  padding: 48px 0 0; /* Height of navbar */
  box-shadow: inset -1px 0 0 rgba(0, 0, 0, 0.1);
  width: 100%;
  transition: all 0.3s;
}

@media (min-width: 768px) {
  .sidebar {
    width: 16.66667%; /* col-lg-2 */
  }
}

@media (max-width: 767.98px) {
  .sidebar {
    top: 5rem;
    padding-top: 0;
  }
}

.sidebar-sticky {
  position: relative;
  top: 0;
  height: calc(100vh - 48px);
  padding-top: .5rem;
  overflow-x: hidden;
  overflow-y: auto; /* Scrollable contents if viewport is shorter than content. */
}

.sidebar .nav-link {
  font-weight: 500;
  color: #333;
  padding: 0.75rem 1.25rem;
  border-left: 3px solid transparent;
  transition: all 0.2s ease;
}

.sidebar .nav-link .feather {
  margin-right: 4px;
  color: #727272;
}

.sidebar .nav-link:hover {
  color: #0d6efd;
  background-color: rgba(13, 110, 253, 0.05);
}

.sidebar .nav-link.active {
  color: #0d6efd;
  border-left: 3px solid #0d6efd;
  background-color: rgba(13, 110, 253, 0.05);
  font-weight: 600;
}

.sidebar .nav-link:hover .feather,
.sidebar .nav-link.active .feather {
  color: inherit;
}

.sidebar-heading {
  padding: 1rem 1.25rem 0.5rem;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #6c757d;
  font-weight: 600;
}

/* Logo de la empresa */
.sidebar-logo {
  max-width: 80%;
  max-height: 80px;
  margin: 0 auto;
}

.sidebar-logo-placeholder {
  width: 80px;
  height: 80px;
  margin: 0 auto;
  background-color: #f8f9fa;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: #6c757d;
}

/* Información del usuario */
.user-info {
  padding: 0 1rem;
}

.user-avatar {
  font-size: 2rem;
  color: #6c757d;
}

/*
* Navbar
*/

.navbar-brand {
  padding-top: .75rem;
  padding-bottom: .75rem;
  font-size: 1rem;
  background-color: rgba(0, 0, 0, 0.25);
  box-shadow: inset -1px 0 0 rgba(0, 0, 0, 0.25);
}

.navbar .navbar-toggler {
  top: .25rem;
  right: 1rem;
}

.navbar .form-control {
  padding: .75rem 1rem;
  border-width: 0;
  border-radius: 0;
}

.form-control-dark {
  color: #fff;
  background-color: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.1);
}

.form-control-dark:focus {
  border-color: transparent;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.25);
}

/* Custom styles */
.pdf-container {
  overflow: auto;
  text-align: center;
}

.pdf-container canvas {
  margin: 0 auto;
  display: block;
}

.footer {
  margin-top: auto;
  padding: 1rem 0;
  background-color: #f8f9fa;
  text-align: center;
  font-size: 0.85rem;
  color: #6c757d;
  border-top: 1px solid #dee2e6;
}

/* Status badges */
.badge {
  padding: 0.5em 0.75em;
  font-weight: 500;
  border-radius: 0.25rem;
}

/* Form styles */
.form-floating > .form-control,
.form-floating > .form-select {
  height: calc(3.5rem + 2px);
  line-height: 1.25;
}

/* Card styles */
.card {
  margin-bottom: 1.5rem;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
  background-color: rgba(0, 0, 0, 0.03);
  border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

/* Table styles */
.table-responsive {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

/* Button styles */
.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.765625rem;
  border-radius: 0.2rem;
}

/* Main content */
.main-content {
  padding: 1.5rem;
  transition: margin-left 0.3s ease;
}

@media (min-width: 768px) {
  .main-content {
    margin-left: 16.66667%; /* col-lg-2 */
    width: calc(100% - 16.66667%);
  }
}

/* Sidebar icons */
.sidebar .nav-link i {
  margin-right: 8px;
  width: 20px;
  text-align: center;
}

/* Improved sidebar for mobile */
@media (max-width: 767.98px) {
  .sidebar {
    position: static;
    width: 100%;
    padding-top: 0;
    height: auto;
  }

  .sidebar-sticky {
    height: auto;
  }

  .main-content {
    margin-left: 0;
    width: 100%;
  }
}

</style>