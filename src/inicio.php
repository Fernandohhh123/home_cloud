<?php
session_start();

/* =========================================
   1. AUTENTICACIÓN
========================================= */

if (
    !isset($_SESSION['usuario_autenticado']) ||
    $_SESSION['usuario_autenticado'] != true
) {
    header('Location: index.html');
    exit;
}


/* =========================================
   2. DIRECTORIO DEL USUARIO
========================================= */

$usuario_actual = basename($_SESSION['nombre_usuario']);

$user_dir = "/srv/cloud/users/" . $usuario_actual;

// Crear el directorio del usuario si no existe
if (!is_dir($user_dir)) {
    if (!mkdir($user_dir, 02770, true)) {
        http_response_code(500);
        exit("No se pudo crear el directorio del usuario");
    }
}

$raiz_real = realpath($user_dir);

if ($raiz_real === false) {
    http_response_code(500);
    exit("No se pudo acceder al directorio del usuario");
}


/* =========================================
   3. IDENTIFICAR DIRECTORIO ACTUAL
========================================= */

// Aceptamos el directorio mediante GET o POST
$dir_actual_post = $_POST['dir'] ?? $_GET['dir'] ?? '';

$dir_actual_post = str_replace('\\', '/', $dir_actual_post);

// No permitimos rutas absolutas
if (str_starts_with($dir_actual_post, '/') ||
    preg_match('/^[a-zA-Z]:/', $dir_actual_post)) {
    http_response_code(400);
    exit("Ruta no válida");
}

// Validar los componentes de la ruta
$partes = explode('/', $dir_actual_post);

foreach ($partes as $parte) {
    if ($parte === '.' || $parte === '..' ||
        str_contains($parte, "\0")) {
        http_response_code(400);
        exit("Ruta no válida");
    }
}

$dir_actual_post = trim($dir_actual_post, '/');

// Construir la ruta absoluta
$destino_base = $raiz_real;

if ($dir_actual_post !== '') {
    $destino_base .= '/' . $dir_actual_post;
}

// Comprobar que el directorio existe y está dentro del usuario
$destino_real = realpath($destino_base);

if (
    $destino_real === false ||
    !is_dir($destino_real) ||
    (
        $destino_real !== $raiz_real &&
        !str_starts_with(
            $destino_real,
            $raiz_real . DIRECTORY_SEPARATOR
        )
    )
) {
    http_response_code(400);
    exit("Directorio no válido");
}

$destino_base = $destino_real;


/* =========================================
   4. SUBIR UNO O VARIOS ARCHIVOS
========================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_FILES['mis_archivos'])
) {

    $multiples_archivos = $_FILES['mis_archivos'];

    for ($i = 0; $i < count($multiples_archivos['name']); $i++) {

        if ($multiples_archivos['error'][$i] !== UPLOAD_ERR_OK) {
            error_log(
                "Error al subir: " . $multiples_archivos['name'][$i]
            );
            continue;
        }

        $nombre_original = basename(
            str_replace('\\', '/', $multiples_archivos['name'][$i])
        );

        if ($nombre_original === '' ||
            $nombre_original === '.' ||
            $nombre_original === '..') {
            continue;
        }

        $temporal = $multiples_archivos['tmp_name'][$i];

        $destino = $destino_base . '/' . $nombre_original;

        if (move_uploaded_file($temporal, $destino)) {

            // Permisos de lectura y escritura para el grupo
            chmod($destino, 0660);

        } else {

            error_log(
                "No se pudo subir: " . $nombre_original
            );
        }
    }

    // Redireccionar conservando la subcarpeta actual
    $redireccion = $_SERVER['PHP_SELF'];

    if ($dir_actual_post !== '') {
        $redireccion .= '?' . http_build_query([
            'dir' => $dir_actual_post
        ]);
    }

    header("Location: " . $redireccion);
    exit;
}


/* =========================================
   5. SUBIR UN DIRECTORIO COMPLETO
========================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_FILES['archivos_carpeta'])
) {

    $dir_archivos_de_la_carpeta = $_FILES['archivos_carpeta'];

    for (
        $i = 0;
        $i < count($dir_archivos_de_la_carpeta['name']);
        $i++
    ) {

        if (
            $dir_archivos_de_la_carpeta['error'][$i] !== UPLOAD_ERR_OK
        ) {
            continue;
        }

        $dir_nombre_temporal =
            $dir_archivos_de_la_carpeta['tmp_name'][$i];

        // Obtener la ruta relativa de la carpeta subida
        $dir_ruta_relativa =
            $dir_archivos_de_la_carpeta['full_path'][$i]
            ?? $dir_archivos_de_la_carpeta['name'][$i];

        $dir_ruta_relativa = str_replace(
            '\\',
            '/',
            $dir_ruta_relativa
        );

        // Rechazar rutas absolutas
        if (
            str_starts_with($dir_ruta_relativa, '/') ||
            preg_match('/^[a-zA-Z]:/', $dir_ruta_relativa)
        ) {
            continue;
        }

        $componentes = explode('/', $dir_ruta_relativa);

        // Validar cada componente
        $ruta_valida = true;

        foreach ($componentes as $componente) {
            if (
                $componente === '' ||
                $componente === '.' ||
                $componente === '..' ||
                str_contains($componente, "\0")
            ) {
                $ruta_valida = false;
                break;
            }
        }

        if (!$ruta_valida || $dir_nombre_temporal === '') {
            continue;
        }

        // Separar directorios y nombre del archivo
        $nombre_archivo = array_pop($componentes);

        // Empezar desde el directorio actual del usuario
        $directorio_actual = $destino_base;

        // Crear los directorios necesarios
        foreach ($componentes as $componente) {

            $siguiente = $directorio_actual . '/' . $componente;

            // No seguir enlaces simbólicos
            if (is_link($siguiente)) {
                $ruta_valida = false;
                break;
            }

            if (!is_dir($siguiente)) {

                if (!mkdir($siguiente, 02770)) {
                    $ruta_valida = false;
                    break;
                }

                chmod($siguiente, 02770);
            }

            $siguiente_real = realpath($siguiente);

            if (
                $siguiente_real === false ||
                !str_starts_with(
                    $siguiente_real,
                    $raiz_real . DIRECTORY_SEPARATOR
                )
            ) {
                $ruta_valida = false;
                break;
            }

            $directorio_actual = $siguiente_real;
        }

        if (!$ruta_valida) {
            continue;
        }

        // Construir destino final
        $dir_destino = $directorio_actual . '/' . $nombre_archivo;

        // Mover el archivo
        if (move_uploaded_file($dir_nombre_temporal, $dir_destino)) {

            chmod($dir_destino, 0660);

        } else {

            error_log(
                "No se pudo subir: " . $dir_ruta_relativa
            );
        }

    }

    // Redireccionar conservando la ubicación
    $redireccion = $_SERVER['PHP_SELF'];

    if ($dir_actual_post !== '') {
        $redireccion .= '?' . http_build_query([
            'dir' => $dir_actual_post
        ]);
    }

    header("Location: " . $redireccion);
    exit;

}

?>


<!DOCTYPE html>
<html lang="es">
    <head>
        <title>Home cloud - Inicio</title>
        <link rel="stylesheet" href="assets/css/inicio.css">
        <meta name="viewport" content+"width=device-width, initial-scale=1.0">
    </head>
    <body>
        <h1>Bienvenido, <?php echo htmlspecialchars($usuario_actual, ENT_QUOTES, 'UTF-8'); ?> </h1>

        <?php
            # Recuperamos la subcarpeta actual al inicio para usarla en los formularios
            $subcarpeta_actual = isset($_GET['dir']) ? $_GET['dir'] : '';
            $subcarpeta_actual = str_replace('..', '', $subcarpeta_actual);
        ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="dir_actual" value="<?php echo htmlspecialchars($subcarpeta_actual, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit">Subir archivo</button>
            <br>
            <input type="file" name="mis_archivos[]" multiple required>
            <br>
        </form>

        <br>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="dir_actual" value="<?php echo htmlspecialchars($subcarpeta_actual, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit">Subir carpeta</button>
            <br>
            <input type="file" name="archivos_carpeta[]" webkitdirectory multiple required>
            <br>
        </form>

        <br>

        <?php

            # Listar archivos del usuario
            $subcarpeta_actual = isset($_GET['dir']) ? $_GET['dir'] : '';
            $subcarpeta_actual = str_replace('..', '', $subcarpeta_actual);

            $ruta_explorar = rtrim($user_dir . '/' . $subcarpeta_actual, '/');

            # verificamos si es una carpeta la ruta
            if(is_dir($ruta_explorar)){
                # Tomamos los archivos de la carpeta
                $archivos = scandir($ruta_explorar);

                echo "<h2>Mis archivos/" . $subcarpeta_actual ."</h2>";
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
                    
                    echo "<li>";
                    
                    // Enlace principal (Carpeta o Archivo)
                    if(is_dir($ruta_fisica)){
                        echo "<a href='?dir=" . urlencode($ruta_relativa_elemento) . "'>📁 " . htmlspecialchars($archivo) . "/</a>";
                    } else {
                        echo "<a href='descargar.php?file=" . urlencode($ruta_relativa_elemento) . "'>📄 " . htmlspecialchars($archivo) . "</a>";
                    }

                    // Botón de tres puntos y menú desplegable
                    echo "<button class='menu-btn' onclick='toggleMenu(event, this)'>⋮</button>";
                    echo "<div class='dropdown-menu'>";
                    
                    // Si es un archivo, mostramos la opción de descargar
                    // Opciones de descarga
                    if(is_dir($ruta_fisica)){
                        echo "<a href='descargar_carpeta.php?dir=" 
                            . urlencode($ruta_relativa_elemento) 
                            . "'>Descargar carpeta (.zip)</a>";
                    } else {
                        echo "<a href='descargar.php?file=" 
                            . urlencode($ruta_relativa_elemento) 
                            . "'>Descargar</a>";
                    }
                    
                    // Opción de borrar (funciona tanto para archivos como para carpetas)
                    echo "<form action='borrar.php' method='POST' style='margin:0; padding:0; border:none; background:none; min-width:0;'>";
                    echo "<input type='hidden' name='ruta' value='" . htmlspecialchars($ruta_relativa_elemento) . "'>";
                    echo "<input type='hidden' name='dir_actual' value='" . htmlspecialchars($subcarpeta_actual) . "'>";
                    echo "<button type='submit' class='btn-delete' onclick='return confirm(\"¿Estás seguro de eliminar este elemento?\")'>Borrar</button>";
                    echo "</form>";

                    echo "</div>"; // Fin dropdown-menu
                    echo "</li>";
                }

                echo "</ul>";

            } else {
                echo "La carpeta no existe o no se puede leer";
            }

            ?>

            <!-- Script de JavaScript para controlar el menú desplegable -->
            <script>
            function toggleMenu(event, button) {
                event.stopPropagation();
                
                // Cierra otros menús abiertos
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    if (menu !== button.nextElementSibling) {
                        menu.classList.remove('show');
                    }
                });

                // Alterna el menú actual
                button.nextElementSibling.classList.toggle('show');
            }

            // Cierra el menú al hacer clic fuera
            window.addEventListener('click', () => {document.querySelectorAll('.dropdown-menu.show').forEach(menu => {menu.classList.remove('show')})});
            </script>

    </body>
</html>