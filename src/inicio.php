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

        <form action="" method="POST" enctype="multipart/form-data">
            <button type="submit">Subir archivo</button>
            <br>
            <label>
                Seleccionar archivo                
                <input type="file" name="Mi_archivo">
            </label>
        </form>

        <?php
            # verificamos si es una carpeta la ruta
            if(is_dir($user_dir)){
                # Tomamos los archivos de la carpeta
                $archivos = scandir($user_dir);

                echo "<h2>Contenido</h2>";
                echo "<ul>";

                foreach ($archivos as $archivo){
                    echo "<li>$archivo</li>";
                }

                echo "<ul>";

            } else {
                echo "La carpeta no existe o no se puede leer";
            }

        ?>

    </body>
</html>
