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

// Check if user has admin privileges
if ($role !== 'admin') {
    $_SESSION['error_message'] = "No tiene permisos para acceder a esta página.";
    header("Location: index.php");
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$edit_id = $_GET['id'];
$edit_user = getUserById($edit_id);

// Check if user exists
if (!$edit_user) {
    $_SESSION['error_message'] = "Usuario no encontrado.";
    header("Location: users.php");
    exit();
}

$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_role = $_POST['role'] ?? '';
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($user_role)) {
        $error = 'Por favor complete todos los campos obligatorios';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor ingrese un correo electrónico válido';
    } elseif ($email !== $edit_user['email'] && emailExists($email)) {
        $error = 'Este correo electrónico ya está registrado';
    } else {
        $conn = getDbConnection();
        
        // Update user
        if (!empty($password)) {
            // Update with new password
            if (strlen($password) < 6) {
                $error = 'La contraseña debe tener al menos 6 caracteres';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email, password = :password, role = :role WHERE id = :id");
                $stmt->bindParam(':password', $hashed_password);
            }
        } else {
            // Update without changing password
            $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email, role = :role WHERE id = :id");
        }
        
        if (empty($error)) {
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':role', $user_role);
            $stmt->bindParam(':id', $edit_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Usuario actualizado exitosamente.";
                header("Location: users.php");
                exit();
            } else {
                $error = 'Error al actualizar el usuario. Por favor intente nuevamente.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Sistema de Aprobación</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Editar Usuario</h1>
                    <a href="users.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Información del Usuario</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $edit_user['name']; ?>" required>
                                <div class="invalid-feedback">
                                    Por favor ingrese el nombre completo.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $edit_user['email']; ?>" required>
                                <div class="invalid-feedback">
                                    Por favor ingrese un correo electrónico válido.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password">
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                        <i class="fas fa-eye" id="password-icon"></i>
                                    </button>
                                </div>
                                <div class="form-text">Deje en blanco para mantener la contraseña actual. La nueva contraseña debe tener al menos 6 caracteres.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">Rol *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="" disabled>Seleccione un rol</option>
                                    <option value="admin" <?php echo $edit_user['role'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                    <option value="subgerente" <?php echo $edit_user['role'] == 'subgerente' ? 'selected' : ''; ?>>Subgerente</option>
                                    <option value="gerente" <?php echo $edit_user['role'] == 'gerente' ? 'selected' : ''; ?>>Gerente</option>
                                    <option value="contador" <?php echo $edit_user['role'] == 'contador' ? 'selected' : ''; ?>>Contador</option>
                                </select>
                                <div class="form-text">
                                    <strong>Administrador:</strong> Acceso completo al sistema.<br>
                                    <strong>Subgerente:</strong> Primera aprobación de facturas.<br>
                                    <strong>Gerente:</strong> Segunda aprobación de facturas.<br>
                                    <strong>Contador:</strong> Aprobación final de facturas y gestión de pagos.
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        });
        
        // Form validation
        (function() {
            'use strict';
            
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.needs-validation');
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        
                        form.classList.add('was-validated');
                    }, false);
                });
        })();
    </script>
</body>
</html>
