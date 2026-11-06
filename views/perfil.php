<?php
require_once '../config/conexion.php';
requerir_login();

$id = usuario_id();

/* ---- Servir la foto de perfil guardada en la base de datos (perfil.php?foto=1) ---- */
if (isset($_GET['foto'])) {
  session_write_close(); // no bloquear la sesión mientras se envía la imagen

  $q = $pdo->prepare("SELECT foto, foto_tipo FROM clientes WHERE id_cliente = ?");
  $q->execute([$id]);
  $fila = $q->fetch(PDO::FETCH_ASSOC);

  $bin = $fila['foto'] ?? null;
  if (is_resource($bin)) {
    $bin = stream_get_contents($bin);
  }
  $tiposOk = ['image/jpeg', 'image/png', 'image/webp'];

  if (!$fila || $bin === null || $bin === '' || !in_array($fila['foto_tipo'], $tiposOk, true)) {
    http_response_code(404);
    exit();
  }

  $etag = '"' . md5($bin) . '"';
  header('ETag: ' . $etag);
  header('Cache-Control: private, no-cache'); // el navegador pregunta si cambió; si no, usa su copia
  header('X-Content-Type-Options: nosniff');
  if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    exit();
  }
  header('Content-Type: ' . $fila['foto_tipo']);
  header('Content-Length: ' . strlen($bin));
  echo $bin;
  exit();
}

/** Separa "XD • 2D · Castellano" en [formato, idioma]. Si ya hay un idioma guardado, lo usa. */
function separar_detalle(string $detalle, string $idioma = ''): array
{
  $formato = trim($detalle);
  $partes = explode('·', $detalle);
  if (count($partes) > 1) {
    $ultimo = trim((string) array_pop($partes));
    $formato = trim(implode('·', $partes));
    if ($idioma === '') {
      $idioma = $ultimo;
    }
  }
  return [$formato, trim($idioma)];
}

$generos = ['masculino' => 'Masculino', 'femenino' => 'Femenino', 'otro' => 'Otro', 'no_especificar' => 'Prefiero no especificarlo'];
$complejos = ['congreso' => 'Congreso', 'san nicolas' => 'San Nicolas', 'palermo' => 'Palermo', 'recoleta' => 'Recoleta', 'belgrano' => 'Belgrano'];

/**
 * Recorta la imagen a un cuadrado de 400x400 y la convierte a JPEG (así pesa poco en la base de datos).
 * Devuelve [binario, mime] o null si el servidor no tiene la extensión GD.
 */
function procesar_imagen(string $ruta, int $tipo): ?array
{
  if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
    return null;
  }
  $img = @imagecreatefromstring((string) file_get_contents($ruta));
  if (!$img) {
    return null;
  }

  // Respetar la orientación de las fotos sacadas con el celular
  if ($tipo === IMAGETYPE_JPEG && function_exists('exif_read_data') && function_exists('imagerotate')) {
    $exif = @exif_read_data($ruta);
    $orientacion = $exif['Orientation'] ?? 1;
    $angulos = [3 => 180, 6 => -90, 8 => 90];
    if (isset($angulos[$orientacion])) {
      $rotada = imagerotate($img, $angulos[$orientacion], 0);
      if ($rotada) {
        imagedestroy($img);
        $img = $rotada;
      }
    }
  }

  $ancho = imagesx($img);
  $alto = imagesy($img);
  $lado = min($ancho, $alto);
  $x = intdiv($ancho - $lado, 2);
  $y = intdiv($alto - $lado, 2);

  $destino = imagecreatetruecolor(400, 400);
  imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255)); // fondo para PNG con transparencia
  imagecopyresampled($destino, $img, 0, 0, $x, $y, 400, 400, $lado, $lado);

  ob_start();
  imagejpeg($destino, null, 85);
  $binario = (string) ob_get_clean();

  imagedestroy($img);
  imagedestroy($destino);

  return $binario !== '' ? [$binario, 'image/jpeg'] : null;
}

/** Vuelve a la página (evita reenviar el formulario al refrescar). Muestra un aviso si hay error u ok. */
function volver(?string $error = null, string $panel = 'config', ?string $ok = null): void
{
  if ($error !== null) {
    $_SESSION['flash'] = ['tipo' => 'error', 'texto' => $error];
  } elseif ($ok !== null) {
    $_SESSION['flash'] = ['tipo' => 'ok', 'texto' => $ok];
  }
  header('Location: perfil.php#' . $panel);
  exit();
}

$errConfig = "";
$post = []; // valores escritos si hay error, para no perderlos

/* ================= ACCIONES ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  /* ---- Marcar las entradas como vistas (respuesta JSON, la llama perfil.js) ---- */
  if (($_POST['accion'] ?? '') === 'marcar_vistas') {
    header('Content-Type: application/json; charset=utf-8');
    if (!csrf_valido()) {
      http_response_code(400);
      echo json_encode(['ok' => false]);
      exit();
    }
    try {
      $q = $pdo->prepare("UPDATE entradas SET vista = 1 WHERE id_cliente = ? AND vista = 0");
      $q->execute([$id]);
      echo json_encode(['ok' => true]);
    } catch (PDOException $ex) {
      http_response_code(500);
      echo json_encode(['ok' => false]);
    }
    exit();
  }

  if (!csrf_valido()) {
    volver('La sesión del formulario expiró. Probá de nuevo.');
  }

  $accion = $_POST['accion'] ?? '';

  /* ---- Cancelar una entrada comprada (solo si es del usuario logueado) ---- */
  if ($accion === 'cancelar_entrada') {
    $idEntrada = filter_var($_POST['id_entrada'] ?? null, FILTER_VALIDATE_INT);
    if ($idEntrada === false || $idEntrada < 1) {
      volver('No se pudo identificar la entrada a cancelar.', 'entradas');
    }

    try {
      $q = $pdo->prepare("DELETE FROM entradas WHERE id_entrada = ? AND id_cliente = ?");
      $q->execute([$idEntrada, $id]);
    } catch (PDOException $ex) {
      error_log('perfil.php (cancelar): ' . $ex->getMessage());
      volver('No se pudo cancelar la entrada. Probá de nuevo.', 'entradas');
    }

    if ($q->rowCount() === 0) {
      volver('La entrada no existe o ya fue cancelada.', 'entradas');
    }
    volver(null, 'entradas', 'La entrada fue cancelada correctamente.');
  }

  /* ---- Subir / cambiar foto (se guarda dentro de la base de datos) ---- */
  if ($accion === 'foto') {
    $f = $_FILES['foto'] ?? null;

    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
      volver('No elegiste ninguna imagen.');
    }
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > 2 * 1024 * 1024) {
      volver('La imagen pesa demasiado (máximo 2 MB).');
    }
    if ($f['error'] !== UPLOAD_ERR_OK) {
      volver('No se pudo subir la imagen. Probá de nuevo.');
    }

    $info = @getimagesize($f['tmp_name']);
    $permitidos = [IMAGETYPE_JPEG => 'image/jpeg', IMAGETYPE_PNG => 'image/png', IMAGETYPE_WEBP => 'image/webp'];
    if (!$info || !isset($permitidos[$info[2]])) {
      volver('Formato no válido. Usá JPG, PNG o WEBP.');
    }

    $procesada = procesar_imagen($f['tmp_name'], $info[2]);
    if ($procesada) {
      [$binario, $mime] = $procesada;
    } else {
      // Sin GD: se guarda tal cual, pero chica para no pasarse del límite de MySQL (max_allowed_packet)
      if ($f['size'] > 900 * 1024) {
        volver('No se pudo reducir la imagen. Probá con una de menos de 900 KB.');
      }
      $binario = (string) file_get_contents($f['tmp_name']);
      $mime = $permitidos[$info[2]];
    }

    try {
      $q = $pdo->prepare("UPDATE clientes SET foto = ?, foto_tipo = ? WHERE id_cliente = ?");
      $q->bindValue(1, $binario, PDO::PARAM_LOB);
      $q->bindValue(2, $mime);
      $q->bindValue(3, $id, PDO::PARAM_INT);
      $q->execute();
    } catch (PDOException $ex) {
      error_log('perfil.php (foto): ' . $ex->getMessage());
      volver('No se pudo guardar la foto en la base de datos. ¿Ejecutaste migracion_completa.sql?');
    }

    volver();
  }

  /* ---- Restaurar foto (volver a las iniciales) ---- */
  if ($accion === 'restaurar_foto') {
    $q = $pdo->prepare("UPDATE clientes SET foto = NULL, foto_tipo = NULL WHERE id_cliente = ?");
    $q->execute([$id]);
    volver();
  }

  /* ---- Guardar configuración ---- */
  if ($accion === 'config') {
    $post = [
      'nombre' => trim($_POST['nombre'] ?? ''),
      'apellido' => trim($_POST['apellido'] ?? ''),
      'email' => trim($_POST['email'] ?? ''),
      'fecha' => $_POST['fecha_nacimiento'] ?? '',
      'telefono' => trim($_POST['telefono'] ?? ''),
      'genero' => $_POST['genero'] ?? '',
      'complejo' => $_POST['complejo'] ?? '',
      'biografia' => trim($_POST['biografia'] ?? ''),
    ];
    $passActual = $_POST['pass_actual'] ?? '';
    $passNueva = $_POST['pass_nueva'] ?? '';

    if ($post['nombre'] === '' || $post['apellido'] === '' || $post['email'] === '' || $post['fecha'] === '' || $post['telefono'] === '') {
      $errConfig = "Completá todos los campos obligatorios.";
    } elseif (mb_strlen($post['nombre']) > 50 || mb_strlen($post['apellido']) > 50) {
      $errConfig = "El nombre y el apellido pueden tener hasta 50 caracteres.";
    } elseif (!filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
      $errConfig = "El email no es válido.";
    } elseif (!isset($generos[$post['genero']]) || !isset($complejos[$post['complejo']])) {
      $errConfig = "Elegí un género y un complejo válidos.";
    } elseif (mb_strlen($post['biografia']) > 300) {
      $errConfig = "La biografía puede tener hasta 300 caracteres.";
    } else {
      $q = $pdo->prepare("SELECT id_cliente FROM clientes WHERE email = ? AND id_cliente <> ?");
      $q->execute([$post['email'], $id]);
      if ($q->fetch()) {
        $errConfig = "Ese email ya está en uso por otra cuenta.";
      }
    }

    // Cambio de contraseña (opcional)
    $nuevoHash = null;
    if ($errConfig === "" && $passNueva !== '') {
      $q = $pdo->prepare("SELECT password FROM clientes WHERE id_cliente = ?");
      $q->execute([$id]);
      $hashActual = $q->fetchColumn();

      if (!$hashActual || !password_verify($passActual, $hashActual)) {
        $errConfig = "Para cambiar la contraseña tenés que ingresar la actual correctamente.";
      } elseif (strlen($passNueva) < 8) {
        $errConfig = "La nueva contraseña debe tener al menos 8 caracteres.";
      } else {
        $nuevoHash = password_hash($passNueva, PASSWORD_DEFAULT);
      }
    }

    if ($errConfig === "") {
      $sql = "UPDATE clientes SET nombre=?, apellido=?, email=?, fecha_nacimiento=?, telefono=?,
                           genero=?, complejo_preferencia=?, biografia=?" . ($nuevoHash ? ", password=?" : "") . "
                    WHERE id_cliente=?";
      $params = [
        $post['nombre'],
        $post['apellido'],
        $post['email'],
        $post['fecha'],
        $post['telefono'],
        $post['genero'],
        $post['complejo'],
        $post['biografia']
      ];
      if ($nuevoHash) {
        $params[] = $nuevoHash;
      }
      $params[] = $id;
      $pdo->prepare($sql)->execute($params);

      $_SESSION['usuario_nombre'] = $post['nombre'];
      $_SESSION['usuario_email'] = $post['email'];
      if ($nuevoHash) {
        session_regenerate_id(true);
      }

      volver();
    }
  }
}

/* ================= DATOS PARA MOSTRAR ================= */
$q = $pdo->prepare("SELECT id_cliente, nombre, apellido, email, fecha_nacimiento, telefono, genero,
                           complejo_preferencia, biografia, (foto IS NOT NULL) AS tiene_foto
                    FROM clientes WHERE id_cliente = ?");
$q->execute([$id]);
$u = $q->fetch(PDO::FETCH_ASSOC);

if (!$u) { // la cuenta ya no existe
  header('Location: logout.php');
  exit();
}

// Si hubo error, mostrar lo que la persona había escrito
$v = [
  'nombre' => $post['nombre'] ?? $u['nombre'],
  'apellido' => $post['apellido'] ?? $u['apellido'],
  'email' => $post['email'] ?? $u['email'],
  'fecha' => $post['fecha'] ?? $u['fecha_nacimiento'],
  'telefono' => $post['telefono'] ?? $u['telefono'],
  'genero' => $post['genero'] ?? $u['genero'],
  'complejo' => $post['complejo'] ?? $u['complejo_preferencia'],
  'biografia' => $post['biografia'] ?? ($u['biografia'] ?? ''),
];

// Foto (se sirve desde la base de datos con perfil.php?foto=1)
$fotoUrl = !empty($u['tiene_foto']) ? 'perfil.php?foto=1' : null;
$iniciales = mb_strtoupper(mb_substr($u['nombre'], 0, 1) . mb_substr($u['apellido'], 0, 1));

// Mensaje flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
if ($errConfig !== "") {
  $flash = ['tipo' => 'error', 'texto' => $errConfig];
}

// Entradas compradas
$entradas = [];
$nuevas = 0; // entradas que todavía no viste
$errEntradas = false;
try {
  $q = $pdo->prepare("
        SELECT e.id_entrada, e.codigo, e.cantidad, e.precio, e.asiento, e.fecha_compra, e.vista,
               COALESCE(NULLIF(e.pelicula, ''), p.titulo) AS pelicula,
               e.cine, e.dia, e.horario, e.detalle, e.idioma, s.numero_sala
        FROM entradas e
        LEFT JOIN funciones f ON f.id_funcion = e.id_funcion
        LEFT JOIN peliculas p ON p.id_pelicula = f.id_pelicula
        LEFT JOIN salas s     ON s.id_sala = f.id_sala
        WHERE e.id_cliente = ?
        ORDER BY e.id_entrada DESC
        LIMIT 50");
  $q->execute([$id]);
  $entradas = $q->fetchAll(PDO::FETCH_ASSOC);

  $q = $pdo->prepare("SELECT COUNT(*) FROM entradas WHERE id_cliente = ? AND vista = 0");
  $q->execute([$id]);
  $nuevas = (int) $q->fetchColumn();
} catch (PDOException $ex) {
  $entradas = [];
  $nuevas = 0;
  $errEntradas = true;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Mi perfil - CineMegapolis</title>
  <link rel="stylesheet" href="../assets/css/peliculas.css">
  <link rel="stylesheet" href="../assets/css/perfil.css">
  <link rel="icon" href="../assets/img/Logo.png">
  <style>
    /* Botón de cancelar entrada */
    .form-cancelar { margin: 0; }
    .btn-cancelar {
      background: transparent;
      color: #ff6b6b;
      border: 1px solid #ff6b6b;
      border-radius: 6px;
      padding: 4px 10px;
      font-size: 12px;
      cursor: pointer;
    }
    .btn-cancelar:hover { background: #ff6b6b; color: #fff; }
  </style>
</head>

<body class="pagina-perfil">

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
      <?php include __DIR__ . '/../includes/navbar_usuario.php'; ?>
    </div>
  </nav>
  <div class="navbar-underline"></div>

  <main class="perfil-wrap" data-csrf="<?= e(csrf_token()) ?>">
    <div class="perfil-card">

      <?php if ($flash): ?>
        <div class="alerta <?= $flash['tipo'] === 'ok' ? 'alerta-ok' : 'alerta-error' ?>" id="alerta" role="alert">
          <span><?= e($flash['texto']) ?></span>
          <button type="button" class="alerta-cerrar" aria-label="Cerrar">&times;</button>
        </div>
      <?php endif; ?>

      <!-- ====== Columna foto ====== -->
      <section class="col-foto">
        <div class="avatar" id="avatar">
          <?php if ($fotoUrl): ?>
            <img src="<?= e($fotoUrl) ?>" alt="Foto de perfil" id="avatarImg">
          <?php else: ?>
            <span class="avatar-iniciales" id="avatarIniciales"><?= e($iniciales) ?></span>
            <img src="" alt="Foto de perfil" id="avatarImg" hidden>
          <?php endif; ?>
        </div>

        <form method="POST" action="perfil.php" enctype="multipart/form-data" id="formFoto">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="accion" value="foto">
          <input type="file" name="foto" id="inputFoto" accept="image/jpeg,image/png,image/webp" hidden>
        </form>

        <div class="foto-botones">
          <button type="button" class="btn btn-chico" id="btnCambiarFoto">Cambiar foto</button>
          <form method="POST" action="perfil.php" id="formRestaurar">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="accion" value="restaurar_foto">
            <button type="submit" class="btn btn-chico btn-borde" <?= $fotoUrl ? '' : 'disabled' ?>>Restaurar
              foto</button>
          </form>
        </div>
        <small class="ayuda">JPG, PNG o WEBP · hasta 2 MB</small>
      </section>

      <!-- ====== Columna datos principales ====== -->
      <section class="col-cabecera">
        <label for="nombre" class="etiqueta-blanca">Nombre de usuario</label>
        <input type="text" id="nombre" name="nombre" form="formConfig" class="campo campo-grande" maxlength="50"
          required value="<?= e($v['nombre']) ?>">

        <p class="ubicacion">Complejo preferido:
          <strong><?= e($complejos[$v['complejo']] ?? ucfirst($v['complejo'])) ?></strong></p>

        <textarea id="biografia" name="biografia" form="formConfig" class="campo campo-bio" maxlength="300"
          placeholder="Escribí algo sobre vos..."><?= e($v['biografia']) ?></textarea>
        <small class="contador"><span id="contBio">0</span>/300</small>
      </section>

      <!-- ====== Menú lateral ====== -->
      <aside class="menu">
        <button type="button" class="menu-item activo" data-panel="config">Configuración</button>
        <button type="button" class="menu-item" data-panel="entradas">
          Entradas compradas
          <?php if ($nuevas > 0): ?><span class="menu-badge" id="badgeEntradas"
              title="Entradas nuevas"><?= $nuevas ?></span><?php endif; ?>
        </button>
        <a href="logout.php" class="btn btn-ancho">Cerrar sesión</a>
      </aside>

      <!-- ====== Paneles ====== -->
      <section class="paneles">

        <!-- Configuración -->
        <div class="panel activo" id="panel-config">
          <h2 class="panel-titulo">Configuración de cuenta</h2>

          <form method="POST" action="perfil.php" id="formConfig" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="accion" value="config">

            <div class="grid-campos">
              <div class="grupo">
                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" class="campo" maxlength="50" required
                  value="<?= e($v['apellido']) ?>">
              </div>
              <div class="grupo">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" class="campo" required value="<?= e($v['email']) ?>">
              </div>
              <div class="grupo">
                <label for="fecha">Fecha de nacimiento</label>
                <input type="date" id="fecha" name="fecha_nacimiento" class="campo" required
                  value="<?= e($v['fecha']) ?>">
              </div>
              <div class="grupo">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" class="campo" required value="<?= e($v['telefono']) ?>">
              </div>
              <div class="grupo">
                <label for="genero">Género</label>
                <select id="genero" name="genero" class="campo" required>
                  <?php foreach ($generos as $val => $txt): ?>
                    <option value="<?= e($val) ?>" <?= $v['genero'] === $val ? 'selected' : '' ?>><?= e($txt) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="grupo">
                <label for="complejo">Complejo de preferencia</label>
                <select id="complejo" name="complejo" class="campo" required>
                  <?php foreach ($complejos as $val => $txt): ?>
                    <option value="<?= e($val) ?>" <?= $v['complejo'] === $val ? 'selected' : '' ?>><?= e($txt) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <h3 class="subtitulo">Cambiar contraseña <small>(dejalo vacío si no querés cambiarla)</small></h3>
            <div class="grid-campos">
              <div class="grupo">
                <label for="pass_actual">Contraseña actual</label>
                <div class="campo-pass">
                  <input type="password" id="pass_actual" name="pass_actual" class="campo"
                    autocomplete="current-password" placeholder="Ingresá tu contraseña actual...">
                  <button type="button" class="ver-pass" data-target="pass_actual"
                    aria-label="Mostrar contraseña">👁</button>
                </div>
              </div>
              <div class="grupo">
                <label for="pass_nueva">Nueva contraseña</label>
                <div class="campo-pass">
                  <input type="password" id="pass_nueva" name="pass_nueva" class="campo" minlength="8"
                    autocomplete="new-password" placeholder="Mínimo 8 caracteres...">
                  <button type="button" class="ver-pass" data-target="pass_nueva"
                    aria-label="Mostrar contraseña">👁</button>
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-guardar">Guardar cambios</button>
          </form>
        </div>

        <!-- Entradas compradas -->
        <div class="panel" id="panel-entradas">
          <h2 class="panel-titulo">Entradas compradas</h2>

          <?php if ($errEntradas): ?>
            <div class="vacio">
              <div class="vacio-icono">⚠️</div>
              <p>No se pudieron cargar tus entradas. Verificá que ejecutaste <strong>migracion_compras.sql</strong> en la
                base de datos.</p>
            </div>
          <?php elseif (empty($entradas)): ?>
            <div class="vacio">
              <div class="vacio-icono">🎟️</div>
              <p>Todavía no compraste entradas.</p>
              <a href="inicio.php" class="btn btn-chico">Ver cartelera</a>
            </div>
          <?php else: ?>
            <ul class="lista-entradas">
              <?php foreach ($entradas as $en): ?>
                <li class="entrada">
                  <div class="entrada-fecha">
                    <span class="dia"><?= e($en['horario'] !== '' ? $en['horario'] : '—') ?></span>
                    <span class="mes"><?= e($en['dia'] !== '' ? $en['dia'] : 'Función') ?></span>
                  </div>
                  <div class="entrada-info">
                    <strong><?= e($en['pelicula']) ?></strong>
                    <?php if ($en['cine'] !== ''): ?><span><?= e($en['cine']) ?></span><?php endif; ?>
                    <?php [$formato, $idioma] = separar_detalle((string) $en['detalle'], (string) ($en['idioma'] ?? '')); ?>
                    <span>
                      <?= e($en['cantidad']) ?>     <?= (int) $en['cantidad'] === 1 ? 'entrada' : 'entradas' ?>
                      <?php if ($formato !== ''): ?> · <?= e($formato) ?><?php endif; ?>
                      <?php if (!empty($en['numero_sala'])): ?> · Sala <?= e($en['numero_sala']) ?><?php endif; ?>
                      <?php if (!empty($en['asiento'])): ?> · Asiento <?= e($en['asiento']) ?><?php endif; ?>
                    </span>
                    <?php if ($idioma !== ''): ?><span>Idioma: <?= e($idioma) ?></span><?php endif; ?>
                    <span class="entrada-compra">Comprada el
                      <?= e(date('d/m/Y H:i', strtotime($en['fecha_compra'] ?? 'now'))) ?></span>
                  </div>
                  <div class="entrada-lado">
                    <span class="entrada-precio">$ <?= e(number_format((int) $en['precio'], 0, ',', '.')) ?></span>
                    <?php if ((int) $en['vista'] === 0): ?>
                      <span class="etiqueta etiqueta-nueva">Nueva</span>
                    <?php endif; ?>
                    <?php if (!empty($en['codigo'])): ?>
                      <span class="etiqueta etiqueta-codigo" title="Código de reserva"><?= e($en['codigo']) ?></span>
                    <?php endif; ?>
                    <form method="POST" action="perfil.php" class="form-cancelar"
                      onsubmit="return confirm('¿Seguro que querés cancelar esta entrada? Esta acción no se puede deshacer.');">
                      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="accion" value="cancelar_entrada">
                      <input type="hidden" name="id_entrada" value="<?= (int) $en['id_entrada'] ?>">
                      <button type="submit" class="btn-cancelar">Cancelar entrada</button>
                    </form>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>

      </section>
    </div>
  </main>

  <footer class="footer">
    <div class="footer-bottom">
      <div class="footer-info">
        <p>&copy; 2024 CineMegapolis</p>
      </div>
    </div>
  </footer>

  <script src="../assets/js/perfil.js"></script>
</body>

</html>