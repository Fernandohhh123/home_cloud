<?php
session_start();
// Verificamos que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    die("Acceso denegado.");
}

$usuario_actual = $_SESSION['usuario'];
$user_dir = "/srv/cloud/users/" . $usuario_actual . "/";

// 2. Verificamos que se haya enviado el parámetro del archivo por la URL
if (isset($_GET['file'])) {
    $archivo_solicitado = $_GET['file'];
    
    // Seguridad básica: evitamos que intenten salir de la carpeta principal usando '..'
    $archivo_solicitado = str_replace('..', '', $archivo_solicitado);
    
    // Ruta física completa del archivo dentro del servidor
    $ruta_completa = $user_dir . '/' . $archivo_solicitado;

    // 3. Verificamos que el archivo exista realmente en el disco
    if (file_exists($ruta_completa) && is_file($ruta_completa)) {
        
        // Limpiamos cualquier búfer previo para evitar corrupción en la descarga
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Forzamos las cabeceras HTTP para que el navegador sepa que es una descarga
        header('Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($ruta_completa) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($ruta_completa));

        // Leemos el archivo del servidor y lo enviamos al navegador del usuario
        readfile($ruta_completa);
        exit;
    } else {
        http_response_code(404);
        echo "Error: El archivo no existe.";
    }
} else {
    http_response_code(400);
    echo "Error: Petición inválida.";
}
?>