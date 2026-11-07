<?php

session_start();




// 1. Verificar autenticación
if (
    !isset($_SESSION['usuario_autenticado']) ||
    $_SESSION['usuario_autenticado'] !== true ||
    !isset($_SESSION['nombre_usuario'])
) {
    http_response_code(403);
    exit("Acceso denegado.");
}

// 2. Directorio del usuario
$usuario_actual = basename($_SESSION['nombre_usuario']);

$user_dir = "/srv/cloud/users/" . $usuario_actual;

$directorio_real = realpath($user_dir);

// 3. Verificar parámetro
if (!isset($_GET['file']) || !is_string($_GET['file'])) {
    http_response_code(400);
    exit("Petición inválida.");
}

// 4. Resolver ruta del archivo
$archivo_solicitado = $_GET['file'];

$ruta_completa = realpath(
    $directorio_real . '/' . $archivo_solicitado
);

// 5. Verificar que el archivo pertenece al usuario
if (
    $directorio_real === false ||
    $ruta_completa === false ||
    !is_file($ruta_completa) ||
    strpos($ruta_completa, $directorio_real . DIRECTORY_SEPARATOR) !== 0
) {
    http_response_code(404);
    exit("Archivo no encontrado.");
}

// 6. Preparar descarga
$nombre_archivo = basename($ruta_completa);

while (ob_get_level() > 0) {
    ob_end_clean();
}

// 7. Cabeceras HTTP
header('Content-Type: application/octet-stream');
header(
    'Content-Disposition: attachment; filename="' .
    addcslashes($nombre_archivo, '"\\') .
    '"; filename*=UTF-8\'\'' . rawurlencode($nombre_archivo)
);
header('Content-Length: ' . filesize($ruta_completa));
header('Cache-Control: no-store');

// 8. Enviar archivo
readfile($ruta_completa);
exit;

?>
