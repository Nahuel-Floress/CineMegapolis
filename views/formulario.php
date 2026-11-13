<?php require_once __DIR__ . '/../config/sesion.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineMegapolis-Contactanos</title>
    <link rel="stylesheet" href="../assets/css/pagina2.css">
    <link rel="icon" href="../assets/img/Logo.png">
</head>
<body>

  <?php include __DIR__ . '/../includes/navbar_usuario.php'; ?>
   <br>
   <form action="=registrardatos.php" method="post">
        Nombre:
        <input type="text" name="nombre">
        <br>
        Correo electrónico:
        <input type="text" name="correo">
        <br>
        Problema:
        <br>
        <textarea name="Temas" rows="5" cols="60"></textarea><br>
        <input type="submit" value="Enviar">
        <input type="reset" value="Borrar">
    </form><br>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>
</body>