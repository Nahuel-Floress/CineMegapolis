<?php
require_once '../config/conexion.php'; // sesión + conexión a la base de datos

const PRECIO_UNITARIO = 5500; // el precio lo define el servidor, no el navegador

/* =====================================================================
   GUARDAR COMPRA
   El JavaScript de esta misma página hace un POST acá y recibe JSON.
   ===================================================================== */
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

    // Código de un solo uso generado al abrir esta página (evita compras duplicadas)
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

    // El idioma viene al final del detalle: "XD • 2D · Castellano" -> "Castellano"
    $idioma = '';
    if (mb_strpos($detalle, '·') !== false) {
        $partes = explode('·', $detalle);
        $idioma = mb_substr(trim((string)end($partes)), 0, 40);
    }

    $total = $cantidad * PRECIO_UNITARIO;

    // Insertar con un código de reserva único (reintenta si justo se repite)
    $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sin 0/O/1/I para evitar confusiones
    $codigo = null;

    for ($intento = 0; $intento < 5 && $codigo === null; $intento++) {
        $candidato = 'CIN-';
        for ($i = 0; $i < 6; $i++) {
            $candidato .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        try {
            $q = $pdo->prepare("INSERT INTO entradas
                (precio, cantidad, asiento, id_cliente, id_funcion, codigo, pelicula, cine, dia, horario, detalle, idioma)
                VALUES (?, ?, NULL, ?, NULL, ?, ?, ?, ?, ?, ?, ?)");
            $q->execute([$total, $cantidad, usuario_id(), $candidato, $pelicula, $cine, $dia, $horario, $detalle, $idioma]);
            $codigo = $candidato;
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000' && strpos($ex->getMessage(), 'codigo') !== false) {
                continue; // código repetido: probar con otro
            }
            error_log('compra.php: ' . $ex->getMessage());
            $responder(500, ['ok' => false, 'error' => 'No se pudo guardar la compra. ¿Ejecutaste migracion_completa.sql?']);
        }
    }

    if ($codigo === null) {
        $responder(500, ['ok' => false, 'error' => 'No se pudo generar el código de reserva. Probá de nuevo.']);
    }

    unset($_SESSION['compra_nonce']); // se consume: refrescar no duplica la compra
    $responder(200, ['ok' => true, 'codigo' => $codigo, 'total' => $total]);
}

/* =====================================================================
   MOSTRAR LA PÁGINA
   ===================================================================== */
requerir_login(true); // para comprar hay que iniciar sesión (después vuelve acá con los datos de la función)

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
   <header>
        <nav class="navbar">
            <!-- Sección Izquierda: Logo + Enlaces -->
            <div class="uno">
                <img src="../assets/img/Logo.png" alt="logo de la pagina" class="logo">
                <a href="inicio.php" class="nav-link">PELÍCULAS</a>
                <a href="candy.php" class="nav-link active">CANDY</a>
                <a href="Cinefan.php" class="nav-link">CINE FAN</a>
                <a href="regalacine.php" class="nav-link">REGALÁ CINE</a>
                <a href="#" class="nav-link">STORE</a>
            </div>

            <!-- Sección Derecha: Selector de Cine + Usuario -->
            <div class="dos">
                <div class="cinema-selector">
                    <span class="pin-icon">📍</span>
                    <select id="selectCine">
                        <option value="" disabled selected>ELEGÍ CINE</option>
                        <option value="abasto">Cine Abasto</option>
                        <option value="palermo">Cine Palermo</option>
                        <option value="unicenter">Cine Unicenter</option>
                    </select>
                </div>
                <?php include __DIR__ . '/../includes/navbar_usuario.php'; ?>
            </div>
        </nav> 
        <div class="navbar-underline"></div>
    </header>

  <main class="compra-container">

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

    <div class="confirmacion" id="confirmacion">
      <h3>¡Compra confirmada!</h3>
      <p>Tu código de reserva es:</p>
      <div class="codigo" id="codigoReserva">------</div>
      <p style="color:#bfbfbf;font-size:13px">Presentá este código en la boletería del cine.</p>
    </div>

  </main>

  <footer class="footer">
    <div class="footer-bottom">
      <div class="footer-info">
        <p>&copy; 2024 CineMegapolis</p>
      </div>
    </div>
  </footer>

  <script>
    const PRECIO_UNITARIO = <?= PRECIO_UNITARIO ?>;

    // Leer los parámetros que llegan desde pelicula2.html
    const params = new URLSearchParams(window.location.search);

    document.getElementById('rPelicula').textContent = params.get('pelicula') || 'Película';
    document.getElementById('rCine').textContent = params.get('cine') || 'Cine';
    document.getElementById('rDia').textContent = params.get('dia') || 'HOY';
    document.getElementById('rHorario').textContent = params.get('horario') || '-';
    document.getElementById('rDetalle').textContent = params.get('detalle') || '-';
    document.getElementById('rButacas').textContent = params.get('butacas') || '-';

    // Selector de cantidad
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

    // Confirmar compra: se guarda en la base de datos (POST a esta misma página)
    const NONCE = <?= json_encode($nonce) ?>;
    const btnConfirmar = document.getElementById('btnConfirmar');
    const errorEl = document.getElementById('errorCompra');

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

        document.getElementById('codigoReserva').textContent = data.codigo;
        document.getElementById('confirmacion').style.display = 'block';
        btnConfirmar.textContent = 'Compra realizada';
        document.getElementById('mas').disabled = true;
        document.getElementById('menos').disabled = true;
        document.getElementById('confirmacion').scrollIntoView({ behavior: 'smooth' });
      } catch (err) {
        mostrarError(err.message || 'Error de conexión. Probá de nuevo.');
        btnConfirmar.disabled = false;
        btnConfirmar.textContent = 'Confirmar compra';
      }
    });

    actualizarTotal();
  </script>

</body>
</html>