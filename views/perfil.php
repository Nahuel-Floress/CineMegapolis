<?php
require_once '../config/conexion.php';
requerir_login();

$id = usuario_id();

// IMPORTANTE: este bloque tiene que ejecutarse ANTES de imprimir cualquier HTML
// (incluido el navbar), porque responde con los bytes de la imagen.
if (isset($_GET['foto'])) {
  session_write_close();

  try {
    $q = $pdo->prepare("SELECT foto, foto_tipo FROM clientes WHERE id_cliente = ?");
    $q->execute([$id]);
    $fila = $q->fetch(PDO::FETCH_ASSOC);
  } catch (PDOException $ex) {
    $fila = false;
  }

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
  header('Cache-Control: private, no-cache');
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

const CARPETA_IMG = __DIR__ . '/../assets/img';
const EXT_IMG = '/\.(png|jpe?g|webp|gif|avif)$/i';


const POSTERS_PELICULAS = [
  'Lilo y Stitch' => 'ds.jpg',
  'Destino final lazos de sangre' => 'ds2.jpg',
  'Ballerina' => 'DS3.jpg',
  'Mision imposible la sentencia final' => 'ds4.jpg',
  'El esquema fenicio' => 'ds5.jpg',
  'Dan da dan evil eve' => 'ds6.jpg',
  'Vermigilio' => 'ds7.jpg',
  'Todas las fuerzas' => 'ds8.jpg',
  'Algo viejo algo nuevo' => 'ds9.jpg',
  'Como entrenar a tu dragon' => 'ds10.jpg',
  'F1 la pelicula' => 'ds11.jpg',
  'Miley cyrus something' => 'ds12.jpg',
  'Gatillero' => 'ds13.jpg',
  'Roger waters this is not a drill' => 'ds14.jpg',
];

const IMG_COMBOS = [
  'Combo Dúo Grande' => 'combo1.png',
  'Combo Dúo Mediano' => 'combo2.png',
  'Combo Dúo Pequeño' => 'combo3.png',
  'Palomitas Gigantes' => 'pochoclos.png',
  'Palomitas Dulces Medianas' => 'pochoclos2.png',
  'Gaseosa Pequeña' => 'Coca-Cola.png',
  'Gaseosa Mediana' => 'Coca-Cola2.png',
  'Gaseosa Grande' => 'Coca-Cola3.png',
  'Bonobon' => 'Bonobon.png',
  'Gomitas Ácidas' => 'mogul.png',
];

function poster_normalizar(string $s): string
{
  $s = mb_strtolower($s, 'UTF-8');
  $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
  return preg_replace('/[^a-z0-9]+/', '', $s) ?? '';
}

// Valida una referencia. Devuelve la URL o la ruta relativa a assets/img, o '' si no sirve.
function poster_resolver(string $ref): string
{
  $ref = trim($ref);
  if ($ref === '') {
    return '';
  }
  if (preg_match('#^https?://#i', $ref)) {
    return (filter_var($ref, FILTER_VALIDATE_URL) && strlen($ref) <= 500) ? $ref : '';
  }
  $r = str_replace('\\', '/', $ref);
  $r = preg_replace('#^(\.{1,2}/)+#', '', $r) ?? '';
  $r = ltrim($r, '/');
  $r = preg_replace('#^assets/img/#i', '', $r) ?? '';
  if ($r === '' || strpos($r, '..') !== false || !preg_match(EXT_IMG, $r)) {
    return '';
  }
  return is_file(CARPETA_IMG . '/' . $r) ? $r : '';
}

// Convierte una referencia válida en el src del <img>.
function poster_src(string $ref): string
{
  if (preg_match('#^https?://#i', $ref)) {
    return $ref;
  }
  return '../assets/img/' . implode('/', array_map('rawurlencode', explode('/', $ref)));
}

// Lista todas las imágenes de assets/img, incluidas las subcarpetas: [ruta relativa => nombre normalizado]
function archivos_img(): array
{
  static $archivos = null;
  if ($archivos === null) {
    $archivos = [];
    $base = realpath(CARPETA_IMG);
    if ($base !== false) {
      try {
        $it = new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $archivo) {
          if (!$archivo->isFile() || !preg_match(EXT_IMG, $archivo->getFilename())) {
            continue;
          }
          $rel = str_replace('\\', '/', substr($archivo->getPathname(), strlen($base) + 1));
          $archivos[$rel] = poster_normalizar(pathinfo($archivo->getFilename(), PATHINFO_FILENAME));
        }
      } catch (Exception $ex) {
        // si no se puede leer la carpeta, queda vacío
      }
    }
  }
  return $archivos;
}

// Busca en assets/img (y subcarpetas) un archivo que se llame como el título.
function poster_por_titulo(string $titulo): string
{
  $clave = poster_normalizar($titulo);
  if ($clave === '') {
    return '';
  }
  $parcial = '';
  foreach (archivos_img() as $archivo => $norm) {
    if ($norm === $clave) {
      return $archivo;
    }
    if ($parcial === '' && strlen($clave) >= 4 && strpos($norm, $clave) !== false) {
      $parcial = $archivo;
    }
  }
  return $parcial;
}

// Busca el título en el mapa POSTERS_PELICULAS (ds1.jpg ... ds14.jpg).
function poster_mapeado(string $titulo): string
{
  $clave = poster_normalizar($titulo);
  if ($clave === '') {
    return '';
  }
  $parcial = '';
  foreach (POSTERS_PELICULAS as $nombre => $archivo) {
    $norm = poster_normalizar((string) $nombre);
    if ($norm === '') {
      continue;
    }
    if ($norm === $clave) {
      $ref = poster_resolver((string) $archivo);
      if ($ref !== '') {
        return $ref;
      }
    }
    // coincidencia parcial (ej: "Lilo y Stitch (2025)" contiene "Lilo y Stitch")
    if ($parcial === '' && strlen($norm) >= 4 && (strpos($clave, $norm) !== false || strpos($norm, $clave) !== false)) {
      $parcial = (string) $archivo;
    }
  }
  return $parcial !== '' ? poster_resolver($parcial) : '';
}

// Busca el póster en la tabla peliculas: columnas de imagen, y si no, ds{id}.jpg
function poster_de_tabla(PDO $pdo, string $titulo): string
{
  if (trim($titulo) === '') {
    return '';
  }
  try {
    $q = $pdo->prepare('SELECT * FROM peliculas WHERE titulo = ? LIMIT 1');
    $q->execute([$titulo]);
    $fila = $q->fetch(PDO::FETCH_ASSOC);
    if ($fila) {
      foreach (['imagen', 'poster', 'portada', 'img', 'foto', 'imagen_url', 'url_imagen', 'cartel'] as $col) {
        if (!empty($fila[$col])) {
          $ok = poster_resolver((string) $fila[$col]);
          if ($ok !== '') {
            return $ok;
          }
        }
      }
      // Si los archivos son ds1.jpg, ds2.jpg... y coinciden con el id de la película
      foreach (['id_pelicula', 'id'] as $colId) {
        if (!empty($fila[$colId])) {
          foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $ok = poster_resolver('ds' . (int) $fila[$colId] . '.' . $ext);
            if ($ok !== '') {
              return $ok;
            }
          }
        }
      }
    }
  } catch (PDOException $ex) {
    // la tabla o columnas no existen: se sigue con la carpeta
  }
  return '';
}

// Imagen de un producto del Candy: columna imagen (si existe) -> mapa del catálogo -> búsqueda por nombre.
function imagen_combo(array $it): string
{
  $ref = poster_resolver((string) ($it['imagen'] ?? ''));
  if ($ref !== '') {
    return $ref;
  }
  $producto = (string) ($it['producto'] ?? '');
  $clave = poster_normalizar($producto);
  foreach (IMG_COMBOS as $nombre => $archivo) {
    if (poster_normalizar($nombre) === $clave) {
      $ref = poster_resolver($archivo);
      if ($ref !== '') {
        return $ref;
      }
    }
  }
  return poster_por_titulo($producto);
}

// Un producto es "combo" si su nombre empieza con "Combo" (Combo Dúo Grande, etc.)
function es_combo(array $it): bool
{
  return strpos(poster_normalizar((string) ($it['producto'] ?? '')), 'combo') === 0;
}

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

function procesar_imagen(string $ruta, int $tipo): ?array
{
  if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
    return null;
  }
  $img = @imagecreatefromstring((string) file_get_contents($ruta));
  if (!$img) {
    return null;
  }

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
  imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
  imagecopyresampled($destino, $img, 0, 0, $x, $y, 400, 400, $lado, $lado);

  ob_start();
  imagejpeg($destino, null, 85);
  $binario = (string) ob_get_clean();

  imagedestroy($img);
  imagedestroy($destino);

  return $binario !== '' ? [$binario, 'image/jpeg'] : null;
}

function volver(?string $error = null, string $panel = 'config'): void
{
  if ($error !== null) {
    $_SESSION['flash'] = ['tipo' => 'error', 'texto' => $error];
  }
  header('Location: perfil.php#' . $panel);
  exit();
}

$errConfig = "";
$post = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (($_POST['accion'] ?? '') === 'marcar_vistas') {
    header('Content-Type: application/json; charset=utf-8');
    if (!csrf_valido()) {
      http_response_code(400);
      echo json_encode(['ok' => false]);
      exit();
    }
    try {
      // Entradas y combos se ven juntos en el mismo panel
      $pdo->prepare("UPDATE entradas SET vista = 1 WHERE id_cliente = ? AND vista = 0")->execute([$id]);
      $pdo->prepare("UPDATE pedidos_candy SET vista = 1 WHERE id_cliente = ? AND vista = 0")->execute([$id]);
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

  if ($accion === 'cancelar_entrada') {
    $idEntrada = filter_var($_POST['id_entrada'] ?? null, FILTER_VALIDATE_INT);
    if ($idEntrada === false || $idEntrada < 1) {
      volver('No se pudo identificar la entrada a cancelar.', 'entradas');
    }

    // Si la entrada tiene combos asociados, se cancelan junto con ella.
    $borradas = 0;
    try {
      $pdo->beginTransaction();
      $pdo->prepare("DELETE i FROM pedidos_candy_items i
                     JOIN pedidos_candy pc ON pc.id_pedido = i.id_pedido
                     WHERE pc.id_entrada = ? AND pc.id_cliente = ?")->execute([$idEntrada, $id]);
      $pdo->prepare("DELETE FROM pedidos_candy WHERE id_entrada = ? AND id_cliente = ?")->execute([$idEntrada, $id]);
      $q = $pdo->prepare("DELETE FROM entradas WHERE id_entrada = ? AND id_cliente = ?");
      $q->execute([$idEntrada, $id]);
      $borradas = $q->rowCount();
      $pdo->commit();
    } catch (PDOException $ex) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      error_log('perfil.php (cancelar): ' . $ex->getMessage());
      volver('No se pudo cancelar la entrada. Probá de nuevo.', 'entradas');
    }

    if ($borradas === 0) {
      volver('La entrada no existe o ya fue cancelada.', 'entradas');
    }
    volver(null, 'entradas');
  }

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

    volver(null, 'config');
  }

  if ($accion === 'restaurar_foto') {
    try {
      $q = $pdo->prepare("UPDATE clientes SET foto = NULL, foto_tipo = NULL WHERE id_cliente = ?");
      $q->execute([$id]);
    } catch (PDOException $ex) {
      error_log('perfil.php (restaurar): ' . $ex->getMessage());
      volver('No se pudo restaurar la foto. ¿Ejecutaste migracion_completa.sql?');
    }
    volver(null, 'config');
  }

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
    } elseif (!filter_var($post['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($post['email']) > 100) {
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
        $post['biografia'] === '' ? null : $post['biografia']
      ];
      if ($nuevoHash) {
        $params[] = $nuevoHash;
      }
      $params[] = $id;

      try {
        $pdo->prepare($sql)->execute($params);
      } catch (PDOException $ex) {
        error_log('perfil.php (config): ' . $ex->getMessage());
        $errConfig = "No se pudieron guardar los cambios. ¿Ejecutaste migracion_completa.sql?";
      }

      if ($errConfig === "") {
        $_SESSION['usuario_nombre'] = $post['nombre'];
        $_SESSION['usuario_email'] = $post['email'];
        if ($nuevoHash) {
          session_regenerate_id(true);
        }
        volver(null, 'config');
      }
    }
  }
}

try {
  $q = $pdo->prepare("SELECT id_cliente, nombre, apellido, email, fecha_nacimiento, telefono, genero,
                             complejo_preferencia, biografia, (foto IS NOT NULL) AS tiene_foto
                      FROM clientes WHERE id_cliente = ?");
  $q->execute([$id]);
} catch (PDOException $ex) {
  die('Falta preparar la base de datos: ejecutá db/migracion_completa.sql en phpMyAdmin y recargá la página.');
}
$u = $q->fetch(PDO::FETCH_ASSOC);

if (!$u) {
  header('Location: logout.php');
  exit();
}

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

$fotoUrl = !empty($u['tiene_foto']) ? 'perfil.php?foto=1' : null;
$iniciales = mb_strtoupper(mb_substr($u['nombre'], 0, 1) . mb_substr($u['apellido'], 0, 1));

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
if ($errConfig !== "") {
  $flash = ['tipo' => 'error', 'texto' => $errConfig];
}

$entradas = [];
$nuevas = 0;
$errEntradas = false;
$pedidos = [];
$combosPorEntrada = [];
$combosSueltos = [];
$errCombos = false;
$metodosPago = ['tarjeta_credito' => 'Tarjeta de crédito', 'tarjeta_debito' => 'Tarjeta de débito', 'mercadopago' => 'Mercado Pago'];

try {
  $q = $pdo->prepare("
        SELECT e.id_entrada, e.codigo, e.cantidad, e.precio, e.asiento, e.fecha_compra, e.vista, e.imagen,
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

  // Póster: lo guardado -> mapa POSTERS_PELICULAS -> tabla peliculas -> carpeta por título.
  // Si hubo que buscarlo, se guarda en la entrada para la próxima vez.
  foreach ($entradas as &$enFila) {
    $ref = poster_resolver((string) ($enFila['imagen'] ?? ''));
    if ($ref === '') {
      $titulo = (string) $enFila['pelicula'];
      $ref = poster_mapeado($titulo);
      if ($ref === '') {
        $ref = poster_de_tabla($pdo, $titulo);
      }
      if ($ref === '') {
        $ref = poster_por_titulo($titulo);
      }
      if ($ref !== '') {
        try {
          $pdo->prepare("UPDATE entradas SET imagen = ? WHERE id_entrada = ? AND id_cliente = ?")
              ->execute([$ref, $enFila['id_entrada'], $id]);
        } catch (PDOException $ex) {
          // si no se puede guardar, igual se muestra
        }
      }
    }
    $enFila['imagen'] = $ref;
  }
  unset($enFila);
} catch (PDOException $ex) {
  error_log('perfil.php (entradas): ' . $ex->getMessage());
  $entradas = [];
  $errEntradas = true;
}

// Combos del Candy: se muestran dentro de su entrada
try {
  $q = $pdo->prepare("
        SELECT pc.id_pedido, pc.id_entrada, pc.codigo, pc.total, pc.metodo_pago, pc.retiro, pc.fecha_compra, pc.vista
        FROM pedidos_candy pc
        WHERE pc.id_cliente = ?
        ORDER BY pc.id_pedido DESC
        LIMIT 50");
  $q->execute([$id]);
  $pedidos = $q->fetchAll(PDO::FETCH_ASSOC);

  if ($pedidos) {
    $ids = array_map(fn($p) => (int) $p['id_pedido'], $pedidos);
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $q = $pdo->prepare("SELECT * FROM pedidos_candy_items WHERE id_pedido IN ($marcas) ORDER BY id_item");
    $q->execute($ids);
    $itemsPorPedido = [];
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $it) {
      $itemsPorPedido[(int) $it['id_pedido']][] = $it;
    }

    $idsEntradas = array_map(fn($e) => (int) $e['id_entrada'], $entradas);
    foreach ($pedidos as $ped) {
      $ped['items'] = $itemsPorPedido[(int) $ped['id_pedido']] ?? [];
      $idEnt = (int) ($ped['id_entrada'] ?? 0);
      if ($idEnt > 0 && in_array($idEnt, $idsEntradas, true)) {
        $combosPorEntrada[$idEnt][] = $ped;
      } else {
        $combosSueltos[] = $ped; // combo comprado sin entrada
      }
    }
  }
} catch (PDOException $ex) {
  error_log('perfil.php (combos): ' . $ex->getMessage());
  $combosPorEntrada = [];
  $combosSueltos = [];
  $errCombos = true;
}

// Total por entrada = precio de la entrada + combos asociados (si eligió alguno)
$totalesEntrada = [];
foreach ($entradas as $en) {
  $idEnt = (int) $en['id_entrada'];
  $totalEntrada = (int) $en['precio'];
  $totalCombos = 0;
  foreach ($combosPorEntrada[$idEnt] ?? [] as $cb) {
    $totalCombos += (int) $cb['total'];
  }
  $totalesEntrada[$idEnt] = [
    'entradas' => $totalEntrada,
    'combos'   => $totalCombos,
    'total'    => $totalEntrada + $totalCombos,
  ];
}

// Badge: entradas nuevas + combos nuevos
try {
  $q = $pdo->prepare("SELECT COUNT(*) FROM entradas WHERE id_cliente = ? AND vista = 0");
  $q->execute([$id]);
  $nuevas = (int) $q->fetchColumn();
  $q = $pdo->prepare("SELECT COUNT(*) FROM pedidos_candy WHERE id_cliente = ? AND vista = 0");
  $q->execute([$id]);
  $nuevas += (int) $q->fetchColumn();
} catch (PDOException $ex) {
  $nuevas = 0;
}

function pesos_fmt($n): string
{
  return '$ ' . number_format((int) $n, 0, ',', '.');
}

// Bloque del combo: imagen (columna del póster) + datos (columna de la info), método de pago, retiro y total
function pintar_combo(array $cb, array $metodosPago, ?array $totales = null): void
{ ?>
  <div class="entrada-combos">
    <div class="combos-cab">
      <span class="combos-titulo">Combo elegido</span>
      <span class="combos-codigo" title="Código de retiro"><?= e($cb['codigo']) ?></span>
    </div>

    <?php foreach ($cb['items'] as $it):
      $cant = (int) $it['cantidad'];
      $unit = (int) ($it['precio_unitario'] ?? 0);
      $ref = imagen_combo($it);
      // Los combos llenan el recuadro (cover); bebidas, golosinas y pochoclos se muestran enteros (contain)
      $claseImg = es_combo($it) ? 'combo-img' : 'combo-img combo-img-producto';
      ?>
      <div class="combo-item">
        <div class="<?= $claseImg ?>">
          <?php if ($ref !== ''): ?>
            <img src="<?= e(poster_src($ref)) ?>" alt="<?= e($it['producto']) ?>" loading="lazy"
              referrerpolicy="no-referrer" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
            <span class="combo-img-vacio" hidden>🍿</span>
          <?php else: ?>
            <span class="combo-img-vacio">🍿</span>
          <?php endif; ?>
        </div>
        <div class="combo-datos">
          <strong><?= e($it['producto']) ?></strong>
          <span>Cantidad: <?= $cant ?></span>
          <?php if ($unit > 0): ?>
            <span><?= pesos_fmt($unit) ?> c/u · Subtotal <?= pesos_fmt($unit * $cant) ?></span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="combo-pie">
      <span><?= e($metodosPago[$cb['metodo_pago']] ?? $cb['metodo_pago']) ?></span>
      <span><?= $cb['retiro'] === 'express' ? 'Fila Express' : 'Retiro en mostrador' ?></span>
      <?php if ($totales !== null): ?>
        <div class="combo-totales" style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;margin-left:auto;">
          <span>Entradas: <?= e(pesos_fmt($totales['entradas'])) ?></span>
          <span>Combos: <?= e(pesos_fmt($totales['combos'])) ?></span>
          <span class="combo-total">Total: <?= e(pesos_fmt($totales['total'])) ?></span>
        </div>
      <?php else: ?>
        <span class="combo-total">Total: <?= e(pesos_fmt($cb['total'])) ?></span>
      <?php endif; ?>
    </div>
  </div>
<?php }
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Mi perfil</title>
  <link rel="stylesheet" href="../assets/css/peliculas.css">
  <link rel="stylesheet" href="../assets/css/perfil.css">
  <link rel="icon" href="../assets/img/Logo.png">
</head>

<body class="pagina-perfil">

  <?php
  include __DIR__ . '/../includes/navbar_usuario.php';
  ?>

  <main class="perfil-wrap" data-csrf="<?= e(csrf_token()) ?>">
    <div class="perfil-card">

      <?php if ($flash): ?>
        <div class="alerta alerta-error" id="alerta" role="alert">
          <span><?= e($flash['texto']) ?></span>
          <button type="button" class="alerta-cerrar" aria-label="Cerrar">&times;</button>
        </div>
      <?php endif; ?>

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
      </section>

      <section class="col-cabecera">
        <label for="nombre" class="etiqueta-blanca">Nombre de usuario</label>
        <input type="text" id="nombre" name="nombre" form="formConfig" class="campo campo-grande" maxlength="50"
          required value="<?= e($v['nombre']) ?>">

        <p class="ubicacion">Complejo preferido:
          <strong><?= e($complejos[$v['complejo']] ?? ucfirst($v['complejo'])) ?></strong>
        </p>

        <textarea id="biografia" name="biografia" form="formConfig" class="campo campo-bio" maxlength="300"
          placeholder="Escribí algo sobre vos..."><?= e($v['biografia']) ?></textarea>
        <small class="contador"><span id="contBio">0</span>/300 </small>
      </section>

      <aside class="menu">
        <button type="button" class="menu-item activo" data-panel="config">Configuración</button>
        <button type="button" class="menu-item" data-panel="entradas">
          Entradas compradas
          <?php if ($nuevas > 0): ?><span class="menu-badge" id="badgeEntradas"
              title="Novedades"><?= $nuevas ?></span><?php endif; ?>
        </button>
        <a href="logout.php" class="btn btn-ancho">Cerrar sesión</a>
      </aside>

      <section class="paneles">

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

            <h3 class="subtitulo">Cambiar contraseña</h3>
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

        <!-- Entradas compradas (con su combo debajo) -->
        <div class="panel" id="panel-entradas">
          <h2 class="panel-titulo">Entradas compradas</h2>

          <?php if ($errEntradas): ?>
            <div class="vacio">
              <div class="vacio-icono">⚠️</div>
              <p>No se pudieron cargar tus entradas.</p>
            </div>
          <?php elseif (empty($entradas) && empty($combosSueltos)): ?>
            <div class="vacio">
              <div class="vacio-icono">🎟️</div>
              <p>Todavía no compraste entradas.</p>
              <a href="inicio.php" class="btn btn-chico">Ver cartelera</a>
            </div>
          <?php else: ?>
            <ul class="lista-entradas">
              <?php foreach ($entradas as $en): ?>
                <li class="entrada">
                  <!-- Fila 1: póster | datos de la película | precio -->
                  <div class="entrada-poster">
                    <?php if (!empty($en['imagen'])): ?>
                      <img src="<?= e(poster_src($en['imagen'])) ?>" alt="Póster de <?= e($en['pelicula']) ?>"
                        loading="lazy" referrerpolicy="no-referrer"
                        onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                      <span class="poster-vacio" hidden>🎬</span>
                    <?php else: ?>
                      <span class="poster-vacio"></span>
                    <?php endif; ?>
                  </div>

                  <div class="entrada-info">
                    <strong><?= e($en['pelicula']) ?></strong>
                    <?php if ($en['cine'] !== ''): ?><span><?= e($en['cine']) ?></span><?php endif; ?>
                    <?php [$formato, $idioma] = separar_detalle((string) $en['detalle'], (string) ($en['idioma'] ?? '')); ?>
                    <span><?= e($en['dia'] !== '' ? $en['dia'] : 'Función') ?><?= $en['horario'] !== '' ? ' · ' . e($en['horario']) : '' ?></span>
                    <span>
                      <?= (int) $en['cantidad'] ?> <?= (int) $en['cantidad'] === 1 ? 'entrada' : 'entradas' ?>
                      <?php if ($formato !== ''): ?> · Formato: <?= e($formato) ?><?php endif; ?>
                      <?php if (!empty($en['numero_sala'])): ?> · Sala <?= e($en['numero_sala']) ?><?php endif; ?>
                      <?php if (!empty($en['asiento'])): ?> · Asiento <?= e($en['asiento']) ?><?php endif; ?>
                    </span>
                    <?php if ($idioma !== ''): ?><span>Idioma: <?= e($idioma) ?></span><?php endif; ?>
                    <span class="entrada-compra">Comprada el
                      <?= e(date('d/m/Y H:i', strtotime($en['fecha_compra'] ?? 'now'))) ?></span>
                  </div>

                  <div class="entrada-lado">
                    <?php $t = $totalesEntrada[(int) $en['id_entrada']]; ?>
                    <?php if ($t['combos'] === 0): ?>
                      <span class="entrada-precio"><?= e(pesos_fmt($t['total'])) ?></span>
                    <?php endif; ?>
                    <?php if ((int) $en['vista'] === 0): ?>
                      <span class="etiqueta etiqueta-nueva">Nueva</span>
                    <?php endif; ?>
                    <?php if (!empty($en['codigo'])): ?>
                      <span class="etiqueta etiqueta-codigo" title="Código de reserva"><?= e($en['codigo']) ?></span>
                    <?php endif; ?>
                    <form method="POST" action="perfil.php" class="form-cancelar">
                      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="accion" value="cancelar_entrada">
                      <input type="hidden" name="id_entrada" value="<?= (int) $en['id_entrada'] ?>">
                      <button type="submit" class="btn-cancelar">Cancelar entrada</button>
                    </form>
                  </div>

                  <!-- Fila 2: imagen del combo | datos del combo (misma grilla que la fila 1) -->
                  <?php
                  $combosEnt = $combosPorEntrada[(int) $en['id_entrada']] ?? [];
                  foreach ($combosEnt as $i => $cb):
                    $esUltimo = ($i === count($combosEnt) - 1);
                    pintar_combo($cb, $metodosPago, $esUltimo ? $t : null);
                  endforeach;
                  ?>
                </li>
              <?php endforeach; ?>
            </ul>

            <?php if ($combosSueltos): ?>
              <h3 class="subtitulo">Combos sin entrada asociada</h3>
              <ul class="lista-entradas">
                <?php foreach ($combosSueltos as $cb): ?>
                  <li class="entrada solo-combo">
                    <div class="entrada-info">
                      <span class="entrada-compra">Comprado el
                        <?= e(date('d/m/Y H:i', strtotime($cb['fecha_compra'] ?? 'now'))) ?></span>
                    </div>
                    <div class="entrada-lado">
                      <?php if ((int) $cb['vista'] === 0): ?>
                        <span class="etiqueta etiqueta-nueva">Nuevo</span>
                      <?php endif; ?>
                    </div>
                    <?php pintar_combo($cb, $metodosPago); ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          <?php endif; ?>
        </div>

      </section>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <script src="../assets/js/perfil.js"></script>
</body>
</html>