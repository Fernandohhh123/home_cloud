<?php
session_start();

    if(!isset($_SESSION['usuario_autenticado']) || $_SESSION['usuario_autenticado'] != true){
        header('Location: index.html');
        exit;
    }

    $usuario_actual = $_SESSION['nombre_usuario'];
?>

<!DOCTYPE html>
<html lang="es">

    <title>Home cloud - Inicio</title>

    <h1>Bienvenido <?php echo $_SESSION['nombre_usuario']; ?> </h1>

</html>
