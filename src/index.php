<!DOCTYPE html>

    <head>
        <title>Home Cloud</title>
        <link rel="stylesheet" href="assets/css/style.css">
        <meta name="viewport" content+"width=device-width, initial-scale=1.0">
    </head>
    
    <body>
        <h1>Home Cloud</h1>
        
        <! formulario para el archivo login.php >
        <form method="POST" action="login.php">
            <h2>Iniciar sesión</h2>

            <p>Nombre</p>
            <input name="username" type="text" required>

            <p>Contraseña</p>
            <input name="password" type="password" required>
            
            <br>
            <br>
            
            <button type="submit">Entrar</button>
        </form>

        <br>

        <input type="submit" value="Cambiar contraseña">

    </body>
</html>
