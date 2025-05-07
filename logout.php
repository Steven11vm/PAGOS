<?php
session_start();
require_once 'includes/functions.php';

// Eliminar el token de recordar contraseña si existe
if (isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    deleteRememberToken($_SESSION['user_id']);
    clearRememberCookie();
}

// Destruir la sesión
session_unset();
session_destroy();

// Redirigir al login
header("Location: login.php");
exit();