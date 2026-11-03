<?php

    # Validamos el login de forma correcta
    # Eliminamos los espacios al princupio o al final

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        exit("Debe completar los campos");
    }

    if($username ==="" || $password ===""){
        exit("Campos vacios");
    }

    # tomamos los datos de la base para comprobar el usuario
    try{
        # Nos conectamos a la base de datos de sqlite
        $db_path = '/srv/cloud/data/users.db';
        $pdo = new PDO("sqlite:" . $db_path);

        #configuramos pdo para lanzar excepciones en caso de errores
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        #buscamos al usuario en la db de forma segura
        $stmt = $pdo->prepare("SELECT * FROM users WHERE Name = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        #comprobamos que existe el usuario
        if(!$user){
            exit("El usuario no existe.<br><a href='index.html'>Volver</a>");
        }

        # comprobamos la contraseña
        # EN ESTE CASO SOLO ES TEXTO PLANDO DESDE LA DB
        # Si todo esta bien, se complira el inicio de sesion
        #  es mejor usar esto: password_verify($password, $user['password'])
        #  pero solo si la contraseña esta cifrada
        if($password == $user['password']){

            session_start();

            $_SESSION['usuario_autenticado'] = true;
            $_SESSION['nombre_usuario'] = $username;

            header('Location: inicio.php');

            exit;

        } else {
            exit("Contraseña incorrecta.<br><a href='index.html'>Volver</a>");
        }


        /*
         * IMPORTANTE (Seguridad futura):
         * Si en el futuro guardas las contraseñas cifradas con password_hash(),
         * la comparación correcta sería usando password_verify():
         *
         * if (password_verify($password, $user['password'])) {
         *     echo "Login exitoso";
         * } else {
         *     exit("Contraseña incorrecta");
         * }
         */

    } catch (PDOException $e) {
        echo "<br>Error en base de datos.<br>";
        exit("Error de conexion: " . $e->getMessage());
    }

?>
