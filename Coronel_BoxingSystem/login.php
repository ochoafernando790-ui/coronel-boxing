<?php
session_start();
require_once("claseBoxing.php");

if(isset($_POST['login'])){

    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    $conexion = new claseConexionCRUD();
 $resultado = $conexion->login($usuario);

if($resultado->num_rows > 0){
    $fila = $resultado->fetch_assoc();

    if(password_verify($password, $fila['contrasena'])){
        $_SESSION['usuario'] = $fila['nombre'];
        $_SESSION['rol'] = $fila['rol']; 

        header("Location: implementacionBoxing.php");
        exit();
    }
}

echo "<script>alert('Usuario o contraseña incorrectos');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Coronel Boxing</title>
</head>
<body>

<form method="POST">
    <div>
        Usuario:
        <input type="text" name="usuario" required>

        Contraseña:
        <input type="password" name="password" required>
    </div>  

    <button type="submit" name="login">Ingresar</button>
</form>

</body>
</html>