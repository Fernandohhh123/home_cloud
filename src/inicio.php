<?php
session_start();

    if(!isset($_SESSION['usuario_autenticado']) || $_SESSION['usuario_autenticado'] != true){
        header('Location: index.html');
        exit;
    }

    $usuario_actual = basename($_SESSION['nombre_usuario']);

    # Asignamos el directorio al usuario
    $user_dir = "/srv/cloud/users/" . $usuario_actual . "/";

    # Creamos la carpeta en caso de que no exista
    if(!is_dir($user_dir)){
        mkdir($user_dir, 0755, true);
    }

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Home cloud - Inicio</title>
</head>
<body>
    <h1>Bienvenido <?php echo htmlspecialchars($usuario_actual, ENT_QUOTES, 'UTF-8'); ?> </h1>
</body>
</html>
