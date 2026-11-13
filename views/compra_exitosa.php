<?php
require_once __DIR__ . '/../config/conexion.php';
requerir_login();

if (isset($_GET['salir'])) {
    unset($_SESSION['ultima_compra'], $_SESSION['candy_entrada']);
    header('Location: inicio.php');
    exit();
}

if (!is_array($_SESSION['ultima_compra'] ?? null)) {
    header('Location: inicio.php');
    exit();
}

function ver(string $ruta): string
{
    $f = __DIR__ . '/' . $ruta;
    return $ruta . '?v=' . (is_file($f) ? filemtime($f) : 1);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineMegapolis</title>
    <link rel="stylesheet" href="<?= ver('../assets/css/candy.css') ?>">
    <link rel="stylesheet" href="<?= ver('../assets/css/compra_exitosa.css') ?>">
    <link rel="icon" href="../assets/img/Logo.png">
</head>
<body>

<?php include __DIR__ . '/../includes/navbar_usuario.php'; ?>
<main class="exito-container">
    <div class="exito-card">
        <div class="exito-icono">✔</div>
        <h1>Compra exitosa</h1>

        <div class="exito-botones">
            <a href="compra_exitosa.php?salir=1" class="btn-inicio">Volver al inicio</a>
        </div>
    </div>
</main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>