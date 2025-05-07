    <?php
    session_start();
    require_once 'config/database.php';
    require_once 'includes/functions.php';

    // Check if already logged in
    if (isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }

    $error = '';

    // Process login form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Por favor ingrese su correo y contraseña';
        } else {
            $user = getUserByEmail($email);
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Redirect to dashboard
                header("Location: index.php");
                exit();
            } else {
                $error = 'Correo o contraseña incorrectos';
            }
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Iniciar Sesión - Sistema de Aprobación de Facturas</title>
        <link rel="stylesheet" href="assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            :root {
                --primary-color: #4361ee;
                --secondary-color: #3a56e4;
                --accent-color: #f8f9fc;
                --text-color: #5a5c69;
                --border-color: #e4e6ef;
                --box-shadow: 0 5px 20px rgba(67, 97, 238, 0.1);
            }
            
            body {
                font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
                background-color: #f0f2f5;
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                padding: 0;
            }
            
            .login-container {
                width: 100%;
                max-width: 420px;
                padding: 15px;
            }
            
            .card {
                border: none;
                border-radius: 12px;
                box-shadow: var(--box-shadow);
                overflow: hidden;
            }
            
            .logo-container {
                display: flex;
                justify-content: center;
                margin-bottom: 10px;
            }
            
            .logo-circle {
                width: 80px;
                height: 80px;
                background-color: var(--primary-color);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: var(--box-shadow);
            }
            
            .logo-circle i {
                font-size: 34px;
                color: white;
            }
            
            .card-header {
                background-color: var(--primary-color);
                color: white;
                text-align: center;
                padding: 20px;
                border-bottom: none;
            }
            
            .card-header h3 {
                margin: 0;
                font-weight: 600;
                font-size: 1.5rem;
            }
            
            .card-body {
                padding: 30px;
                background-color: white;
            }
            
            .input-group {
                position: relative;
                margin-bottom: 25px;
            }
            
            .input-group label {
                display: block;
                font-size: 0.85rem;
                color: #6c757d;
                margin-bottom: 5px;
            }
            
            .input-field {
                width: 100%;
                padding: 12px 15px;
                font-size: 1rem;
                border: 1px solid var(--border-color);
                border-radius: 8px;
                transition: all 0.2s ease;
                outline: none;
            }
            
            .input-field:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            }
            
            .input-field::placeholder {
                color: #adb5bd;
            }
            
            .input-icon {
                position: absolute;
                right: 15px;
                top: 38px;
                color: #adb5bd;
                cursor: pointer;
            }
            
            .checkbox-container {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
            }
            
            .checkbox-container input {
                margin-right: 8px;
            }
            
            .checkbox-container label {
                font-size: 0.9rem;
                color: var(--text-color);
            }
            
            .forgot-password {
                font-size: 0.9rem;
                color: var(--primary-color);
                text-decoration: none;
                transition: color 0.2s ease;
            }
            
            .forgot-password:hover {
                color: var(--secondary-color);
                text-decoration: underline;
            }
            
            .btn-login {
                background-color: var(--primary-color);
                color: white;
                border: none;
                border-radius: 8px;
                padding: 12px 20px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .btn-login i {
                margin-right: 8px;
            }
            
            .btn-login:hover {
                background-color: var(--secondary-color);
            }
            
            .login-footer {
                background-color: white;
                text-align: center;
                padding: 20px;
                border-top: 1px solid var(--border-color);
            }
            
            .login-footer a {
                color: var(--primary-color);
                text-decoration: none;
                font-size: 0.9rem;
                transition: color 0.2s ease;
            }
            
            .login-footer a:hover {
                color: var(--secondary-color);
                text-decoration: underline;
            }
            
            .alert {
                padding: 12px 15px;
                margin-bottom: 20px;
                border-radius: 8px;
                border: none;
            }
            
            .alert-danger {
                background-color: #feebed;
                color: #d63031;
            }
            
            @media (max-width: 576px) {
                .login-container {
                    padding: 10px;
                }
                
                .card-body {
                    padding: 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="logo-container">
                <div class="logo-circle">
                    <i class="fas fa-file-invoice"></i>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Sistema de Aprobación</h3>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="input-group">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" id="email" name="email" class="input-field" placeholder="nombre@ejemplo.com" required>
                            <span class="input-icon">
                                <i class="fas fa-envelope"></i>
                            </span>
                        </div>
                        
                        <div class="input-group">
                            <label for="password">Contraseña</label>
                            <input type="password" id="password" name="password" class="input-field" placeholder="••••••••" required>
                            <span class="input-icon" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </span>
                        </div>
                        
                    
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            
                            <button type="submit" class="btn-login">
                                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="login-footer">
                    <a href="register.php">¿Necesita una cuenta? ¡Regístrese!</a>
                </div>
            </div>
        </div>
        
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script>
            function togglePassword() {
                const passwordInput = document.getElementById('password');
                const toggleIcon = document.getElementById('toggleIcon');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                }
            }
        </script>
    </body>
    </html>