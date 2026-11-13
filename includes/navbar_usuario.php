<?php
/**
 * includes/navbar_usuario.php
 * Navbar único para todas las páginas.
 * Uso en cada página (dentro del <body>):  <?php include __DIR__ . '/../includes/navbar_usuario.php'; ?>
 */
require_once __DIR__ . '/../config/sesion.php';

// Ruta del CSS (ajustala si tu carpeta es distinta). Es relativa a la página que incluye el navbar.
$navCssHref = '../assets/css/navbar_usuario.css';
$navLogoSrc = '../assets/img/Logo.png';

// Enlaces del menú (archivo => texto)
$navLinks = [
  'inicio.php'     => 'PELÍCULAS',
  'candy.php'      => 'CANDY',
  'Cinefan.php'    => 'CINE FAN',
  'regalacine.php' => 'REGALÁ CINE',
];
$navPaginaActual = basename($_SERVER['SCRIPT_NAME'] ?? '');

// Datos del usuario logueado
$navNombre = '';
$navApellido = '';
$navTieneFoto = false;

if (usuario_logueado()) {
  require_once __DIR__ . '/../config/conexion.php';

  $navNombre = (string) ($_SESSION['usuario_nombre'] ?? '');
  try {
    $navQ = $pdo->prepare("SELECT nombre, apellido, (foto IS NOT NULL) AS tiene_foto FROM clientes WHERE id_cliente = ?");
    $navQ->execute([usuario_id()]);
    if ($navFila = $navQ->fetch(PDO::FETCH_ASSOC)) {
      $navNombre = $navFila['nombre'];
      $navApellido = $navFila['apellido'];
      $navTieneFoto = (bool) $navFila['tiene_foto'];
    }
  } catch (PDOException $navEx) {
    // Si falla la consulta, se usan los datos de la sesión
  }
  if ($navNombre === '') {
    $navNombre = (string) ($_SESSION['usuario_email'] ?? 'Mi cuenta');
  }
  $navIniciales = mb_strtoupper(mb_substr($navNombre, 0, 1) . mb_substr($navApellido, 0, 1));
}
?>
<link rel="stylesheet" href="<?= e($navCssHref) ?>">

<header class="nu-header">
  <nav class="nu-navbar">

    <!-- Izquierda: logo + enlaces -->
    <div class="nu-left">
      <a href="inicio.php" class="nu-logo-link">
        <img src="<?= e($navLogoSrc) ?>" alt="Logo de la página" class="nu-logo">
      </a>
      <?php foreach ($navLinks as $archivo => $texto): ?>
        <a href="<?= e($archivo) ?>"
           class="nu-link<?= strcasecmp($navPaginaActual, $archivo) === 0 ? ' active' : '' ?>">
          <?= e($texto) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Derecha: selector de cine + usuario -->
    <div class="nu-right">
      <div class="nu-cinema">
        <span class="nu-pin">📍</span>
        <select id="selectCine" aria-label="Elegí tu cine">
          <option value="" disabled selected>ELEGÍ CINE</option>
          <option value="abasto">Cine Abasto</option>
          <option value="palermo">Cine Palermo</option>
          <option value="unicenter">Cine Unicenter</option>
        </select>
      </div>

      <?php if (usuario_logueado()): ?>
        <div class="nu-user" tabindex="0">
          <a href="perfil.php" class="nu-user-link" title="Mi perfil">
            <span class="nu-avatar">
              <?= e($navIniciales) ?>
              <?php if ($navTieneFoto): ?>
                <img class="nu-avatar-img" src="perfil.php?foto=1" alt="" onerror="this.remove()">
              <?php endif; ?>
            </span>
            <span class="nu-user-name"><?= e($navNombre) ?></span>
          </a>
        </div>
      <?php else: ?>
        <a href="login.php" class="nu-login" title="Iniciar sesión"><span>👤</span></a>
      <?php endif; ?>
    </div>

  </nav>
  <div class="nu-underline"></div>
</header>

<script>
  // El cine elegido se mantiene al cambiar de página
  (function () {
    var sel = document.getElementById('selectCine');
    if (!sel) return;
    try {
      var guardado = localStorage.getItem('cineElegido');
      if (guardado) sel.value = guardado;
      sel.addEventListener('change', function () {
        localStorage.setItem('cineElegido', sel.value);
      });
    } catch (e) { /* localStorage no disponible */ }
  })();
</script>