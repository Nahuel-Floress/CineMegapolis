<?php
require_once '../config/conexion.php';

const PRECIO_UNITARIO = 5500;

/* =====================================================================
 *  Pósters (sin poster.php)
 *  Una "referencia" puede ser una URL (https://...) o un archivo dentro
 *  de assets/img (se permiten subcarpetas).
 * ===================================================================== */
const CARPETA_IMG = __DIR__ . '/../assets/img';
const EXT_IMG = '/\.(png|jpe?g|webp|gif|avif)$/i';

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

// Busca en assets/img un archivo que se llame como la película.
function poster_por_titulo(string $titulo): string
{
    $clave = poster_normalizar($titulo);
    if ($clave === '') {
        return '';
    }
    $parcial = '';
    foreach (@scandir(CARPETA_IMG) ?: [] as $f) {
        if (!preg_match(EXT_IMG, $f)) {
            continue;
        }
        $norm = poster_normalizar(pathinfo($f, PATHINFO_FILENAME));
        if ($norm === $clave) {
            return $f;
        }
        if ($parcial === '' && strlen($clave) >= 4 && strpos($norm, $clave) !== false) {
            $parcial = $f;
        }
    }
    return $parcial;
}

// Busca el póster en la tabla peliculas (prueba varios nombres de columna).
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
        }
    } catch (PDOException $ex) {
        // la tabla o columnas no existen: se sigue con la carpeta
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $responder = function (int $http, array $datos): void {
        http_response_code($http);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit();
    };

    if (!usuario_logueado()) {
        $responder(401, ['ok' => false, 'error' => 'Tenés que iniciar sesión para comprar.']);
    }

    $nonce = $_POST['nonce'] ?? '';
    if (empty($_SESSION['compra_nonce']) || !is_string($nonce) || !hash_equals($_SESSION['compra_nonce'], $nonce)) {
        $responder(400, ['ok' => false, 'error' => 'La compra ya se procesó o la página expiró. Volvé a elegir la función.']);
    }

    $limpiar = function (string $campo, int $max): string {
        $texto = trim((string)($_POST[$campo] ?? ''));
        $texto = preg_replace('/[\x00-\x1F\x7F]/u', '', $texto) ?? '';
        return mb_substr($texto, 0, $max);
    };

    $pelicula = $limpiar('pelicula', 150);
    $cine     = $limpiar('cine', 120);
    $dia      = $limpiar('dia', 40);
    $horario  = $limpiar('horario', 30);
    $detalle  = $limpiar('detalle', 100);
    $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT);

    if ($pelicula === '') {
        $responder(422, ['ok' => false, 'error' => 'Falta el nombre de la película.']);
    }
    if ($cantidad === false || $cantidad < 1 || $cantidad > 10) {
        $responder(422, ['ok' => false, 'error' => 'La cantidad de entradas debe ser entre 1 y 10.']);
    }

    // Póster: 1) lo que mande la página de la película (URL o archivo),
    //         2) tabla peliculas, 3) archivo de assets/img con el nombre de la película.
    $imagen = poster_resolver((string)($_POST['imagen'] ?? ''));
    if ($imagen === '') {
        $imagen = poster_de_tabla($pdo, $pelicula);
    }
    if ($imagen === '') {
        $imagen = poster_por_titulo($pelicula);
    }

    $idioma = '';
    if (mb_strpos($detalle, '·') !== false) {
        $partes = explode('·', $detalle);
        $idioma = mb_substr(trim((string)end($partes)), 0, 40);
    }

    $total = $cantidad * PRECIO_UNITARIO;

    $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $codigo = null;
    $idEntrada = null;

    for ($intento = 0; $intento < 6 && $codigo === null; $intento++) {
        $candidato = 'CIN-';
        for ($i = 0; $i < 6; $i++) {
            $candidato .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        try {
            $q = $pdo->prepare("INSERT INTO entradas
                (precio, cantidad, asiento, id_cliente, id_funcion, codigo, pelicula, imagen, cine, dia, horario, detalle, idioma)
                VALUES (?, ?, NULL, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)");
            $q->execute([$total, $cantidad, usuario_id(), $candidato, $pelicula, ($imagen ?: null), $cine, $dia, $horario, $detalle, $idioma]);
            $codigo = $candidato;
            $idEntrada = (int) $pdo->lastInsertId();
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000' && strpos($ex->getMessage(), 'codigo') !== false) {
                continue;
            }
            // La URL del póster no entra en la columna: reintentamos sin imagen (el perfil la buscará después)
            if ($imagen !== '' && stripos($ex->getMessage(), 'Data too long') !== false) {
                $imagen = '';
                continue;
            }
            error_log('compra.php: ' . $ex->getMessage());
            $responder(500, ['ok' => false, 'error' => 'No se pudo guardar la compra. ¿Ejecutaste el SQL que agrega la columna imagen?']);
        }
    }

    if ($codigo === null || !$idEntrada) {
        $responder(500, ['ok' => false, 'error' => 'No se pudo generar el código de reserva. Probá de nuevo.']);
    }

    unset($_SESSION['compra_nonce'], $_SESSION['ultima_compra']);

    // La entrada queda guardada y se la pasamos al Candy para armar el combo.
    $_SESSION['candy_entrada'] = $idEntrada;

    $responder(200, [
        'ok'       => true,
        'codigo'   => $codigo,
        'total'    => $total,
        'redirect' => 'candy.php',
    ]);
}

requerir_login(true);

$nonce = bin2hex(random_bytes(16));
$_SESSION['compra_nonce'] = $nonce;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Comprar entradas</title>
  <link rel="stylesheet" href="../assets/css/peliculas.css">
  <link rel="icon" href="../assets/img/Logo.png">
</head>

<body>


  <?php include __DIR__ . '/../includes/navbar_usuario.php'; ?>
  <main class="compra-container">

    <!-- Todo lo de la compra va dentro de este contenedor: al confirmar se oculta -->
    <div id="pasoCompra">

      <a href="javascript:history.back()" class="btn-volver">&larr; Volver a la película</a>

      <h2 class="compra-titulo">Comprar entradas</h2>

      <div class="resumen-card" id="resumenCard">
        <div class="resumen-row"><span>Película</span><span id="rPelicula">-</span></div>
        <div class="resumen-row"><span>Cine</span><span id="rCine">-</span></div>
        <div class="resumen-row"><span>Día</span><span id="rDia">-</span></div>
        <div class="resumen-row"><span>Horario</span><span id="rHorario">-</span></div>
        <div class="resumen-row"><span>Formato / Idioma</span><span id="rDetalle">-</span></div>
        <div class="resumen-row"><span>Disponibilidad de butacas</span><span id="rButacas">-</span></div>
      </div>

      <div class="qty-card">
        <div class="qty-title">Cantidad de entradas</div>
        <div class="qty-control">
          <button class="qty-btn" id="menos">−</button>
          <span class="qty-num" id="cantidad">1</span>
          <button class="qty-btn" id="mas">+</button>
        </div>

        <div class="precio-total">
          <span>Total a pagar</span>
          <span id="total">$ 5.500</span>
        </div>
      </div>

      <div id="errorCompra" style="display:none;max-width:420px;background:rgba(255,0,0,0.1);color:#ff6b6b;padding:10px;border-radius:6px;margin:12px 0;font-size:13px;"></div>

      <button class="btn-confirmar" id="btnConfirmar">Confirmar compra</button>

    </div>

    <!-- Aparece en lugar de la compra cuando se confirma -->
    <div class="confirmacion" id="confirmacion" style="display:none;">
      <h3>¡Entrada reservada!</h3>
      <p>Tu código de reserva es:</p>
      <div class="codigo" id="codigoReserva">------</div>
      <p style="color:#bfbfbf;font-size:13px"></p>
      <p style="font-size:13px"><a id="irCandy" href="candy.php" style="color:#e2b13c"></a></p>
    </div>

  </main>

  <script>
    const PRECIO_UNITARIO = <?= PRECIO_UNITARIO ?>;

    const params = new URLSearchParams(window.location.search);

    // El póster puede venir con distintos nombres según la página de la película
    const IMAGEN = params.get('imagen') || params.get('poster') || params.get('img') ||
                   params.get('portada') || params.get('cartel') || '';

    document.getElementById('rPelicula').textContent = params.get('pelicula') || 'Película';
    document.getElementById('rCine').textContent = params.get('cine') || 'Cine';
    document.getElementById('rDia').textContent = params.get('dia') || 'HOY';
    document.getElementById('rHorario').textContent = params.get('horario') || '-';
    document.getElementById('rDetalle').textContent = params.get('detalle') || '-';
    document.getElementById('rButacas').textContent = params.get('butacas') || '-';

    let cantidad = 1;
    const cantidadEl = document.getElementById('cantidad');
    const totalEl = document.getElementById('total');

    function actualizarTotal(){
      cantidadEl.textContent = cantidad;
      const total = cantidad * PRECIO_UNITARIO;
      totalEl.textContent = '$ ' + total.toLocaleString('es-AR');
    }

    document.getElementById('mas').addEventListener('click', () => {
      if (cantidad < 10) { cantidad++; actualizarTotal(); }
    });

    document.getElementById('menos').addEventListener('click', () => {
      if (cantidad > 1) { cantidad--; actualizarTotal(); }
    });

    const NONCE = <?= json_encode($nonce) ?>;
    const btnConfirmar = document.getElementById('btnConfirmar');
    const errorEl = document.getElementById('errorCompra');
    const pasoCompra = document.getElementById('pasoCompra');
    const confirmacion = document.getElementById('confirmacion');

    function mostrarError(msg) {
      errorEl.textContent = msg;
      errorEl.style.display = 'block';
    }

    btnConfirmar.addEventListener('click', async () => {
      errorEl.style.display = 'none';

      if (!params.get('pelicula')) {
        mostrarError('Elegí una función desde la página de la película para poder comprar.');
        return;
      }

      btnConfirmar.disabled = true;
      btnConfirmar.textContent = 'Procesando...';

      try {
        const body = new URLSearchParams({
          nonce: NONCE,
          pelicula: params.get('pelicula') || '',
          imagen: IMAGEN,
          cine: params.get('cine') || '',
          dia: params.get('dia') || '',
          horario: params.get('horario') || '',
          detalle: params.get('detalle') || '',
          cantidad: String(cantidad)
        });

        const res = await fetch('compra.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          credentials: 'same-origin',
          body: body.toString()
        });

        if (res.status === 401) {
          window.location.href = 'login.php?redirect=' + encodeURIComponent('compra.php' + window.location.search);
          return;
        }

        const data = await res.json();
        if (!data.ok) { throw new Error(data.error || 'No se pudo completar la compra.'); }

        // Compra OK: se oculta todo el paso de compra y se muestra la reserva en su lugar
        document.getElementById('codigoReserva').textContent = data.codigo;
        pasoCompra.style.display = 'none';
        confirmacion.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Después de comprar la entrada, vamos directo al Candy.
        const destino = data.redirect || 'candy.php';
        document.getElementById('irCandy').setAttribute('href', destino);
        setTimeout(() => { window.location.href = destino; }, 2500);
      } catch (err) {
        mostrarError(err.message || 'Error de conexión. Probá de nuevo.');
        btnConfirmar.disabled = false;
        btnConfirmar.textContent = 'Confirmar compra';
      }
    });

    actualizarTotal();
  </script>
  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>