<?php
require_once '../config/conexion.php';

if (usuario_logueado()) {
    header("Location: perfil.php");
    exit();
}

$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? '';
if (!preg_match('/^[A-Za-z0-9_\-]+\.php$/', $redirect) || $redirect === 'login.php') {
    $redirect = 'inicio.php';
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['pass'] ?? '';

    if (!empty($email) && !empty($pass)) {
        $sql = "SELECT * FROM clientes WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($pass, $usuario['password'])) {
            session_regenerate_id(true); // evita fijación de sesión
            $_SESSION['usuario_id'] = $usuario['id_cliente'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];

            header("Location: " . $redirect);
            exit();
        } else {
            $error = "Email o contraseña incorrectos.";
        }
    } else {
        $error = "Por favor, completá todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Iniciar sesión</title>
  <link rel="stylesheet" href="../assets/css/peliculas.css">
  <link rel="icon" href="../assets/img/Logo.png">
</head>

<body>

  <?php include __DIR__ . '/../includes/navbar_usuario.php'; ?>
  <main class="compra-container">

    <a href="javascript:history.back()" class="btn-volver">&larr; Volver</a>

    <div id="vistaLogin">
      <h2 class="compra-titulo">Iniciar sesión</h2>

      <?php if (!empty($error)): ?>
        <div style="max-width:420px;background:rgba(255,0,0,0.1);color:#ff6b6b;padding:10px;border-radius:6px;margin-bottom:12px;font-size:13px;">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" class="qty-card" style="max-width:420px">
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
        <div style="display:flex;flex-direction:column;gap:14px">
          <div>
            <label for="email" style="display:block;color:#bfbfbf;font-size:13px;margin-bottom:6px">Email</label>
            <input type="email" id="email" name="email" placeholder="tu@email.com" required
              style="width:100%;padding:12px;border-radius:8px;border:none;background:rgba(255,255,255,0.06);color:#fff;font-size:14px;box-sizing:border-box">
          </div>
          <div>
            <label for="pass" style="display:block;color:#bfbfbf;font-size:13px;margin-bottom:6px">Contraseña</label>
            <input type="password" id="pass" name="pass" placeholder="••••••••" required
              style="width:100%;padding:12px;border-radius:8px;border:none;background:rgba(255,255,255,0.06);color:#fff;font-size:14px;box-sizing:border-box">
          </div>
          <button type="submit" class="btn-confirmar" id="btnLogin" style="margin-top:6px">Ingresar</button>
          <div style="text-align:center;color:#bfbfbf;font-size:13px">
            ¿No tenés cuenta? <a href="register.php" style="color:var(--accent-2);font-weight:700">Registrate</a>
          </div>
        </div>
      </form>
    </div>

  </main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>