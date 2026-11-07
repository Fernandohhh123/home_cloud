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

    # Subir uno o varios archivos
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['mis_archivos'])) {

        $multiples_archivos = $_FILES['mis_archivos'];

        for ($i = 0; $i < count($multiples_archivos['name']); $i++) {

            if ($multiples_archivos['error'][$i] !== UPLOAD_ERR_OK) {
                echo "Error al subir el archivo: " .
                    htmlspecialchars($multiples_archivos['name'][$i]);
                continue;
            }

            $nombre_original = basename(
                str_replace('\\', '/', $multiples_archivos['name'][$i])
            );

            $temporal = $multiples_archivos['tmp_name'][$i];

            $destino = $user_dir . $nombre_original;

            if (!move_uploaded_file($temporal, $destino)){
                echo "No se pudo subir: " . htmlspecialchars($nombre_original) . "<br>";
            }
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    # Subir un directorio
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivos_carpeta'])){
        
        $dir_archivos_de_la_carpeta = $_FILES['archivos_carpeta'];

        for($i = 0; $i < count($dir_archivos_de_la_carpeta['name']); $i++){
            $dir_nombre_temporal = $dir_archivos_de_la_carpeta['tmp_name'][$i];
            $dir_ruta_relativa = $dir_archivos_de_la_carpeta['full_path'][$i];
            if (empty($dir_ruta_relativa)){
                $dir_ruta_relativa = $dir_archivos_de_la_carpeta['name'][$i];
            }
            $dir_destino = $user_dir . '/' . $dir_ruta_relativa;
            $dir_directorio_destino = dirname($dir_destino);
            if(!is_dir($dir_directorio_destino)){
                mkdir($dir_directorio_destino, 0755, true);
            }
            if($dir_nombre_temporal != ""){
                move_uploaded_file($dir_nombre_temporal, $dir_destino);
            }
        } # for
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } # subir un directorio

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
            <input type="file" name="mis_archivos[]" multiple required>
            <br>
        </form>

        <br>

        <form action="" method="POST" enctype="multipart/form-data">
            <button type="submit">Subir carpeta</button>
            <br>
            <input type="file" name="archivos_carpeta[]" webkitdirectory multiple required>
            <br>
        </form>

        <?php

            # Listar archivos del usuario
            $subcarpeta_actual = isset($_GET['dir']) ? $_GET['dir'] : '';
            $subcarpeta_actual = str_replace('..', '', $subcarpeta_actual);

            $ruta_explorar = rtrim($user_dir . '/' . $subcarpeta_actual, '/');

            # verificamos si es una carpeta la ruta
            if(is_dir($ruta_explorar)){
                # Tomamos los archivos de la carpeta
                $archivos = scandir($ruta_explorar);

                echo "<h2>Mis archivos</h2>";
                echo "<ul>";

                # boton para retrpceder de un directorio
                if($subcarpeta_actual != ''){
                    $dir_padre = dirname($subcarpeta_actual);
                    $url_padre = ($dir_padre === '.' || $dir_padre === '') ? $_SERVER['PHP_SELF'] : $_SERVER['PHP_SELF'] . '?dir=' . urldecode($dir_padre);
                    echo "<li><a href='$url_padre'> <strong>[ <- Volver]</strong></a></li>";
                }

                foreach ($archivos as $archivo){
                    if($archivo === '.' || $archivo === ".."){
                        continue;
                    }

                    $ruta_fisica = $ruta_explorar . '/' . $archivo;
                    $ruta_relativa_elemento = ($subcarpeta_actual === '') ? $archivo : $subcarpeta_actual . '/' . $archivo;
                    
                    if(is_dir($ruta_fisica)){
                        echo "<li> <a href='?dir=" . urlencode($ruta_relativa_elemento) . "'>" . htmlspecialchars($archivo) . "</a>/</li>";
                    } else {
                        echo "<li> <a href='descargar.php?file=" . urlencode($ruta_relativa_elemento) . "'>" . htmlspecialchars($archivo) . "</a></li>";
                    }
                }

                echo "</ul>";

            } else {
                echo "La carpeta no existe o no se puede leer";
            }
            
        ?>

    </body>
</html>
