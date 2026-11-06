<?php
require_once __DIR__ . '/../config/sesion.php';
?>
<?php if (usuario_logueado()):
  require_once __DIR__ . '/../config/conexion.php'; 

  $navNombre = (string) ($_SESSION['usuario_nombre'] ?? '');
  $navApellido = '';
  $navTieneFoto = false;
  try {
    $navQ = $pdo->prepare("SELECT nombre, apellido, (foto IS NOT NULL) AS tiene_foto FROM clientes WHERE id_cliente = ?");
    $navQ->execute([usuario_id()]);
    if ($navFila = $navQ->fetch(PDO::FETCH_ASSOC)) {
      $navNombre = $navFila['nombre'];
      $navApellido = $navFila['apellido'];
      $navTieneFoto = (bool) $navFila['tiene_foto'];
    }
  } catch (PDOException $navEx) {

  }
  if ($navNombre === '') {
    $navNombre = (string) ($_SESSION['usuario_email'] ?? 'Mi cuenta');
  }
  $navIniciales = mb_strtoupper(mb_substr($navNombre, 0, 1) . mb_substr($navApellido, 0, 1));
  ?>
  <style>
    .user-menu {
      position: relative;
      display: inline-flex;
      align-items: center;
      cursor: pointer
    }

    .user-menu .user-link {
      display: inline-flex;
      align-items: center;
      gap: 9px;
      text-decoration: none
    }

    .user-menu .user-avatar {
      width: 34px !important;
      height: 34px !important;
      max-width: none !important;
      border-radius: 50% !important;
      border: 2px solid #c4a052;
      box-sizing: border-box;
      object-fit: cover;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      background: #2a1210;
      color: #c4a052;
      font-size: 12px;
      font-weight: 800;
      letter-spacing: .5px
    }

    .user-menu .user-name {
      color: #fff;
      font-size: 14px;
      font-weight: 600;
      max-width: 140px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap
    }

    .user-menu .user-dropdown {
      display: none;
      position: absolute;
      right: 0;
      top: 100%;
      padding-top: 10px;
      min-width: 170px;
      z-index: 999
    }

    .user-menu .user-dropdown-inner {
      background: #12100d;
      border: 1px solid rgba(255, 255, 255, .15);
      border-radius: 8px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, .6);
      overflow: hidden
    }

    .user-menu:hover .user-dropdown,
    .user-menu:focus-within .user-dropdown {
      display: block
    }

    .user-menu .user-dropdown a {
      display: block;
      padding: 11px 14px;
      color: #fff;
      text-decoration: none;
      font-size: 13px;
      font-weight: 700
    }

    .user-menu .user-dropdown a:hover {
      background: rgba(255, 255, 255, .08)
    }

    .user-menu .user-dropdown a.salir {
      color: #ff6b6b;
      border-top: 1px solid rgba(255, 255, 255, .1)
    }
  </style>
  <div class="user-menu" tabindex="0">
    <a href="perfil.php" class="user-link" title="Mi perfil">
      <?php if ($navTieneFoto): ?>
        <img class="user-avatar" src="perfil.php?foto=1" alt="Foto de perfil">
      <?php else: ?>
        <span class="user-avatar"><?= e($navIniciales) ?></span>
      <?php endif; ?>
      <span class="user-name"><?= e($navNombre) ?></span>
    </a>
    <div class="user-dropdown">

    </div>
  </div>
<?php else: ?>
  <a href="login.php" class="icon-link" title="Iniciar sesión"><span class="icon">👤</span></a>
<?php endif; ?>