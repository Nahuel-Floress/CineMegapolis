<?php
require_once '../config/conexion.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $genero = $_POST['genero'] ?? '';
    $complejo = $_POST['complejo'] ?? '';

    if (!empty($nombre) && !empty($apellido) && !empty($email) && !empty($password)) {
        $sql_check = "SELECT id_cliente FROM clientes WHERE email = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$email]);

        if ($stmt_check->rowCount() > 0) {
            $error = "El correo electrónico ya está registrado.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO clientes (nombre, apellido, fecha_nacimiento, email, password, telefono, genero, complejo_preferencia) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$nombre, $apellido, $fecha_nacimiento, $email, $password_hash, $telefono, $genero, $complejo])) {
                header("Location: login.php");
                exit();
            } else {
                $error = "Hubo un error al registrar el usuario.";
            }
        }
    } else {
        $error = "Por favor, completá todos los campos obligatorios.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Crear Cuenta</title>
  <link rel="stylesheet" href="../assets/css/peliculas.css">
  <link rel="icon" href="../assets/img/Logo.png">
</head>

<body>

  <nav class="navbar">
    <div class="uno">
      <img src="../assets/img/Logo.png" alt="logo de la pagina" class="logo">
      <a href="inicio.php" class="nav-link">PELÍCULAS</a>
      <a href="candy.php" class="nav-link">CANDY</a>
      <a href="Cinefan.php" class="nav-link">CINE FAN</a>
      <a href="regalacine.php" class="nav-link">REGALÁ CINE</a>
      <a href="#" class="nav-link">STORE</a>
    </div>
    <div class="dos">
      <a href="#" class="nav-link">📍 ELEGÍ CINE ▼</a>
      <a href="#" class="nav-link">ELEGÍ PELÍCULA ▼</a>
      <a href="login.php" class="icon-link active" title="Iniciar sesión"><span class="icon">👤</span></a>
    </div>
  </nav>
  <div class="navbar-underline"></div>

  <main class="compra-container">

    <a href="javascript:history.back()" class="btn-volver">&larr; Volver</a>

    <div id="vistaLogin">
      <h2 class="compra-titulo">Crear Cuenta</h2>

      <?php if (!empty($error)): ?>
        <div style="max-width:420px;background:rgba(255,0,0,0.1);color:#ff6b6b;padding:10px;border-radius:6px;margin-bottom:12px;font-size:13px;">
          <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <h3>Datos personales</h3>
      
      <form action="register.php" method="POST">
        <div class="qty-card" style="max-width:420px">
          <div style="display:flex;flex-direction:column;gap:14px">
            <div>
              <label for="nombre" style="display:block;color:#bfbfbf;font-size:13px;margin-bottom:6px">Nombre*</label>
              <input type="text" id="nombre" name="nombre" placeholder="Nombre" required
                style="width:100%;padding:12px;border-radius:8px;border:none;background:rgba(255,255,255,0.06);color:#fff;font-size:14px;box-sizing:border-box">
            </div>
            <div>
              <label for="apellido" style="display:block;color:#bfbfbf;font-size:13px;margin-bottom:6px">Apellido*</label>
              <input type="text" id="apellido" name="apellido" placeholder="Apellido" required
                style="width:100%;padding:12px;border-radius:8px;border:none;background:rgba(255,255,255,0.06);color:#fff;font-size:14px;box-sizing:border-box">
            </div>
            <div>
              <label for="fecha" style="display:block;color:#bfbfbf;font-size:13px;margin-bottom:6px">Fecha de nacimiento*</label>
              <input type="date" id="fecha" name="fecha_nacimiento" required
                style="width:100%;padding:12px;border-radius:8px;border:none;background:rgba(255,255,255,0.06);color:#fff;font-size:14px;box-sizing:border-box">
            </div>
            <div>
              <label for="email" style="display:block;color:#bfbfbf;font-size:13px;margin-bottom:6px">Email*</label>
              <input type="email" id="email" name="email" placeholder="tu@email.com" required
                style="width:100%;padding:12px;border-radius:8px;border:none;background:rgba(255,255,255,0.06);color:#fff;font-size:14px;box-sizing:border-box">
            </div>
            <div>
              <label for="password" style="display:block;color:#bfbfbf;font-size:13px;margin-bottom:6px">Contraseña*</label>
              <input type="password" id="password" name="password" placeholder="••••••••" required
                style="width:100%;padding:12px;border-radius:8px;border:none;background:rgba(255,255,255,0.06);color:#fff;font-size:14px;box-sizing:border-box">
            </div>
            <div>
              <label for="numero" style="display:block;color:#bfbfbf;font-size:13px;margin-bottom:6px">Número de teléfono*</label>
              <input type="tel" id="numero" name="telefono" placeholder="1122334455" required
                style="width:100%;padding:12px;border-radius:8px;border:none;background:rgba(255,255,255,0.06);color:#fff;font-size:14px;box-sizing:border-box">
            </div>
            <div>
              <label for="genero" style="display:block;color:#bfbfbf;font-size:13px;margin-bottom:6px">Género*</label>
              <select id="genero" name="genero" required 
                style="width:100%;padding:12px;border-radius:8px;border:none;background:rgba(255,255,255,0.06);color:#fff;font-size:14px;box-sizing:border-box;outline:none;cursor:pointer;">
                <option value="" disabled selected hidden style="background:#222;color:#a09d98">Selecciona una opción</option>
                <option value="masculino" style="background:#222;color:#fff">Masculino</option>
                <option value="femenino" style="background:#222;color:#fff">Femenino</option>
                <option value="otro" style="background:#222;color:#fff">Otro</option>
                <option value="no_especificar" style="background:#222;color:#fff">Prefiero no especificarlo</option>
              </select>
            </div>
            <div>
              <label for="complejo" style="display:block;color:#bfbfbf;font-size:13px;margin-bottom:6px">Complejo de preferencia*</label>
              <select id="complejo" name="complejo" required 
                style="width:100%;padding:12px;border-radius:8px;border:none;background:rgba(255,255,255,0.06);color:#fff;font-size:14px;box-sizing:border-box;outline:none;cursor:pointer;">
                <option value="" disabled selected hidden style="background:#222;color:#a09d98">Selecciona una opción</option>
                <option value="congreso" style="background:#222;color:#fff">Congreso</option>
                <option value="san nicolas" style="background:#222;color:#fff">San Nicolas</option>
                <option value="palermo" style="background:#222;color:#fff">Palermo</option>
                <option value="recoleta" style="background:#222;color:#fff">Recoleta</option>
                <option value="belgrano" style="background:#222;color:#fff">Belgrano</option>
              </select>
            </div>

            <button type="submit" class="btn-confirmar" id="btnRegister" style="margin-top:6px">Crear cuenta</button>
            <div style="text-align:center;color:#bfbfbf;font-size:13px">
              ¿Ya tenés cuenta? <a href="login.php" style="color:var(--accent-2);font-weight:700">Iniciá sesión</a>
            </div>
          </div>
        </div>
      </form>
    </div>

  </main>

  <footer class="footer">
    <div class="footer-bottom">
      <div class="footer-info">
        <p>&copy; 2024 CineMegapolis</p>
      </div>
    </div>
  </footer>

</body>
</html>