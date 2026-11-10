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

// Directorio del usuario
$usuario_actual = basename($_SESSION['nombre_usuario']);
$user_dir = "/srv/cloud/users/" . $usuario_actual;

// 2. Obtener y validar el directorio solicitado
$directorio = $_GET['dir'] ?? '';

$base = realpath($user_dir);
$ruta = realpath($user_dir . '/' . $directorio);

// Comprobar que existe y que es un directorio
if (!$base || !$ruta || !is_dir($ruta)) {
    http_response_code(404);
    exit("Directorio no encontrado.");
}

// Evitar salir del directorio del usuario
if ($ruta !== $base && !str_starts_with($ruta, $base . DIRECTORY_SEPARATOR)) {
    http_response_code(403);
    exit("Acceso denegado.");
}

// 3. Crear archivo ZIP temporal
$nombre_zip = basename($ruta) . '.zip';
$zip_path = tempnam(sys_get_temp_dir(), 'zip_');

$zip = new ZipArchive();

if ($zip->open($zip_path, ZipArchive::OVERWRITE) !== true) {
    unlink($zip_path);
    http_response_code(500);
    exit("No se pudo crear el archivo ZIP.");
}

// 4. Recorrer recursivamente la carpeta
$archivos = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $ruta,
        FilesystemIterator::SKIP_DOTS
    ),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($archivos as $archivo) {
    if (!$archivo->isFile() || $archivo->isLink()) {
        continue;
    }

    $ruta_absoluta = $archivo->getRealPath();
    $ruta_relativa = substr($ruta_absoluta, strlen($ruta) + 1);

    $zip->addFile($ruta_absoluta, $ruta_relativa);
}

// 5. Finalizar ZIP y enviarlo al navegador
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $nombre_zip . '"');
header('Content-Length: ' . filesize($zip_path));

readfile($zip_path);
unlink($zip_path);
exit;

?>