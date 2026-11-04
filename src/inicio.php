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

    # Subir un archivo
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['mi_archivo'])){
        $nombre_original = $_FILES['mi_archivo']['name'];
        $temporal = $_FILES['mi_archivo']['tmp_name'];

        $destino = $user_dir . '/' . basename($nombre_original);

        if(move_uploaded_file($temporal, $destino)){
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Error al subir el archivo";
        }
    } 

?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <title>Home cloud - Inicio</title>
    </head>
    <body>
        <h1>Bienvenido, <?php echo htmlspecialchars($usuario_actual, ENT_QUOTES, 'UTF-8'); ?> </h1>

        <form action="" method="POST" enctype="multipart/form-data">
            <button type="submit">Subir archivo</button>
            <br>
            <br>
            <input type="file" name="mi_archivo" required>
            <br>
        </form>

        <?php
            # verificamos si es una carpeta la ruta
            if(is_dir($user_dir)){
                # Tomamos los archivos de la carpeta
                $archivos = scandir($user_dir);

                echo "<h2>Contenido</h2>";
                echo "<ul>";

                foreach ($archivos as $archivo){
                    if($archivo === '.' || $archivo === ".."){
                        continue;
                    }

                    $ruta_archivo = "/srv/cloud/users/" . $usuario_actual . "/" . $archivo;

                    echo "<li><a href='$ruta_archivo' download>$archivo</a></li>";
                }

                echo "<ul>";

            } else {
                echo "La carpeta no existe o no se puede leer";
            }

        ?>

    </body>
</html>
