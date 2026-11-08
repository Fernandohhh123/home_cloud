<?php
session_start();

if (
    !isset($_SESSION['usuario_autenticado']) ||
    $_SESSION['usuario_autenticado'] !== true ||
    !isset($_SESSION['nombre_usuario'])
) {
    http_response_code(403);
    exit("Acceso denegado.");
}

$usuario_actual = $_SESSION['nombre_usuario'];
$user_dir = "/srv/cloud/users/" . $usuario_actual . "/";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ruta'])) {
    $elemento = $_POST['ruta'];
    $dir_actual = isset($_POST['dir_actual']) ? $_POST['dir_actual'] : '';

    // Seguridad básica contra directory traversal
    $elemento = str_replace('..', '', $elemento);
    $elemento = ltrim($elemento, '/');
    
    $ruta_absoluta = $user_dir . $elemento;

    if (file_exists($ruta_absoluta)) {
        if (is_dir($ruta_absoluta)) {
            // Función recursiva para borrar carpetas con contenido
            function eliminarDir($dir) {
                $archivos = array_diff(scandir($dir), ['.', '..']);
                foreach ($archivos as $archivo) {
                    $path = $dir . '/' . $archivo;
                    is_dir($path) ? eliminarDir($path) : unlink($path);
                }
                return rmdir($dir);
            }
            eliminarDir($ruta_absoluta);
        } else {
            // Borrar archivo individual
            unlink($ruta_absoluta);
        }
    }

    // Redireccionamos de vuelta a la misma carpeta donde estaba el usuario
    $redireccion = 'inicio.php';
    if (!empty($dir_actual)) {
        $redireccion .= '?dir=' . urlencode($dir_actual);
    }
    
    header('Location: ' . $redireccion);
    exit;
} else {
    header('Location: inicio.php');
    exit;
}
?>