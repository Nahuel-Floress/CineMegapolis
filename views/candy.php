<?php
require_once __DIR__ . '/../config/conexion.php';

const MAX_POR_PRODUCTO = 10;

/*
 * Poné en false cuando todo funcione.
 * En true, si falla el guardado del pedido, el mensaje de error muestra el motivo real de la base de datos.
 */
const CANDY_DEBUG = true;

/*
 * Catálogo del Candy (precios en pesos, igual que las entradas).
 * El servidor usa SIEMPRE estos precios: lo que manda el navegador no se confía.
 */
$CATALOGO = [
    'combo_duo_grande'  => ['nombre' => 'Combo Dúo Grande',        'desc' => '1 Popcorn Gigante + 2 Bebidas grandes',      'precio' => 12900, 'cat' => 'combos',  'img' => 'combo1.png'],
    'combo_duo_mediano' => ['nombre' => 'Combo Dúo Mediano',       'desc' => '1 Popcorn Mediano + 2 Bebidas medianas',     'precio' => 9900,  'cat' => 'combos',  'img' => 'combo2.png'],
    'combo_duo_chico'   => ['nombre' => 'Combo Dúo Pequeño',       'desc' => '1 Popcorn Pequeño + 2 Bebidas pequeñas',     'precio' => 7500,  'cat' => 'combos',  'img' => 'combo3.png'],
    'pop_gigante'       => ['nombre' => 'Palomitas Gigantes',      'desc' => 'Manteca, sal o queso extra',                 'precio' => 6900,  'cat' => 'popcorn', 'img' => 'pochoclos.png'],
    'pop_dulce'         => ['nombre' => 'Palomitas Dulces Medianas', 'desc' => 'Con un toque crocante de caramelo',         'precio' => 5900,  'cat' => 'popcorn', 'img' => 'pochoclos2.png'],
    'gaseosa_pequeña'    => ['nombre' => 'Gaseosa Pequeña',    'desc' => '2 Bebidas grandes a elección',               'precio' => 7900,  'cat' => 'drinks',  'img' => 'Coca-Cola.png'],
    'gaseosa_mediana'   => ['nombre' => 'Gaseosa Mediana', 'desc' => 'Línea Coca-Cola, Sprite o Fanta de 750ml',   'precio' => 4500,  'cat' => 'drinks',  'img' => 'Coca-Cola2.png'],
    'gaseosa_grande'   => ['nombre' => 'Gaseosa Grande',            'desc' => 'Gaseosa 1LT', 'precio' => 3000,  'cat' => 'drinks',  'img' => 'Coca-Cola3.png'],
    'bonobon'        => ['nombre' => 'Bonobon',              'desc' => 'Chocolate ideal para disfrutar',          'precio' => 4500,  'cat' => 'sweets',  'img' => 'Bonobon.png'],
    'gomitas'           => ['nombre' => 'Gomitas Ácidas',          'desc' => 'Paquete familiar de gomitas frutales',       'precio' => 3500,  'cat' => 'sweets',  'img' => 'mogul.png'],
];

$METODOS = [
    'tarjeta_credito' => 'Tarjeta de crédito',
    'tarjeta_debito'  => 'Tarjeta de débito',
    'mercadopago'     => 'Mercado Pago',
];

function pesos(int $n): string
{
    return '$ ' . number_format($n, 0, ',', '.');
}

// Agrega ?v=<fecha de modificación> para que el navegador nunca use una versión vieja del CSS/JS
function ver(string $ruta): string
{
    $f = __DIR__ . '/' . $ruta;
    return $ruta . '?v=' . (is_file($f) ? filemtime($f) : 1);
}

/* ------------------------------------------------------------------ */
/*  POST                                                              */
/* ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    /* ---- Continuar sin comprar combos ---------------------------- */
    if ($accion === 'sin_combos') {
        if (!usuario_logueado()) {
            header('Location: login.php?redirect=' . urlencode('candy.php'));
            exit();
        }
        if (!csrf_valido() || empty($_SESSION['candy_entrada'])) {
            header('Location: inicio.php');
            exit();
        }
        $_SESSION['ultima_compra'] = ['entrada' => (int) $_SESSION['candy_entrada'], 'pedido' => null];
        unset($_SESSION['candy_entrada']);
        header('Location: compra_exitosa.php');
        exit();
    }

    /* ---- Pagar combos (responde JSON) ---------------------------- */
    header('Content-Type: application/json; charset=utf-8');

    $responder = function (int $http, array $datos): void {
        http_response_code($http);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit();
    };

    if ($accion !== 'pagar') {
        $responder(400, ['ok' => false, 'error' => 'Acción no válida.']);
    }
    if (!usuario_logueado()) {
        $responder(401, ['ok' => false, 'error' => 'Tenés que iniciar sesión para pagar.']);
    }
    if (!csrf_valido()) {
        $responder(400, ['ok' => false, 'error' => 'La página expiró. Recargala e intentá de nuevo.']);
    }

    // Items del carrito: [{id, cantidad}, ...]
    $crudo = json_decode((string) ($_POST['items'] ?? ''), true);
    if (!is_array($crudo) || count($crudo) === 0 || count($crudo) > 30) {
        $responder(422, ['ok' => false, 'error' => 'Tu carrito está vacío.']);
    }

    $items = [];
    $total = 0;
    foreach ($crudo as $fila) {
        $pid = is_array($fila) ? ($fila['id'] ?? null) : null;
        $cant = is_array($fila) ? filter_var($fila['cantidad'] ?? null, FILTER_VALIDATE_INT) : false;

        if (!is_string($pid) || !isset($CATALOGO[$pid])) {
            $responder(422, ['ok' => false, 'error' => 'Hay un producto que ya no está disponible.']);
        }
        if ($cant === false || $cant < 1 || $cant > MAX_POR_PRODUCTO) {
            $responder(422, ['ok' => false, 'error' => 'La cantidad de cada producto debe ser entre 1 y ' . MAX_POR_PRODUCTO . '.']);
        }
        if (isset($items[$pid])) {
            $responder(422, ['ok' => false, 'error' => 'El carrito tiene productos repetidos.']);
        }
        $items[$pid] = [
            'nombre'   => $CATALOGO[$pid]['nombre'],
            'precio'   => $CATALOGO[$pid]['precio'],
            'cantidad' => $cant,
        ];
        $total += $CATALOGO[$pid]['precio'] * $cant;
    }

    $metodo = (string) ($_POST['metodo'] ?? '');
    if (!isset($METODOS[$metodo])) {
        $responder(422, ['ok' => false, 'error' => 'Elegí un medio de pago.']);
    }
    $retiro = ($_POST['retiro'] ?? '') === 'express' ? 'express' : 'mostrador';

    // Entrada asociada (solo si es del usuario logueado)
    $idEntrada = null;
    if (!empty($_SESSION['candy_entrada'])) {
        try {
            $q = $pdo->prepare('SELECT id_entrada FROM entradas WHERE id_entrada = ? AND id_cliente = ?');
            $q->execute([(int) $_SESSION['candy_entrada'], usuario_id()]);
            $idEntrada = $q->fetchColumn() ?: null;
        } catch (PDOException $ex) {
            error_log('candy.php (entrada): ' . $ex->getMessage());
            $idEntrada = null;
        }
    }

    $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $codigo = null;
    $idPedido = null;

    for ($intento = 0; $intento < 5 && $codigo === null; $intento++) {
        $candidato = 'CAN-';
        for ($i = 0; $i < 6; $i++) {
            $candidato .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        try {
            $pdo->beginTransaction();

            $q = $pdo->prepare('INSERT INTO pedidos_candy (codigo, id_cliente, id_entrada, total, metodo_pago, retiro)
                                VALUES (?, ?, ?, ?, ?, ?)');
            $q->execute([$candidato, usuario_id(), $idEntrada, $total, $metodo, $retiro]);
            $idPedido = (int) $pdo->lastInsertId();

            $qi = $pdo->prepare('INSERT INTO pedidos_candy_items (id_pedido, producto, precio_unitario, cantidad)
                                 VALUES (?, ?, ?, ?)');
            foreach ($items as $it) {
                $qi->execute([$idPedido, $it['nombre'], $it['precio'], $it['cantidad']]);
            }

            $pdo->commit();
            $codigo = $candidato;
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($ex->getCode() === '23000' && strpos($ex->getMessage(), 'codigo') !== false) {
                continue; // código repetido: probamos con otro
            }
            error_log('candy.php: ' . $ex->getMessage());
            $msg = 'No se pudo guardar el pedido. ¿Ejecutaste migracion_entradas_candy.sql?';
            if (CANDY_DEBUG) {
                $msg .= ' [Detalle: ' . $ex->getMessage() . ']';
            }
            $responder(500, ['ok' => false, 'error' => $msg]);
        }
    }

    if ($codigo === null) {
        $responder(500, ['ok' => false, 'error' => 'No se pudo generar el código del pedido. Probá de nuevo.']);
    }

    $_SESSION['ultima_compra'] = ['entrada' => $idEntrada ? (int) $idEntrada : null, 'pedido' => $idPedido];
    unset($_SESSION['candy_entrada']);

    $responder(200, ['ok' => true, 'codigo' => $codigo, 'total' => $total, 'redirect' => 'compra_exitosa.php']);
}

/* ------------------------------------------------------------------ */
/*  GET: contexto de la entrada recién comprada (si hay)              */
/*  Solo se usa para el cuadro "Recogida" y el botón "sin combos".    */
/* ------------------------------------------------------------------ */
$entradaCtx = null;
if (usuario_logueado() && !empty($_SESSION['candy_entrada'])) {
    try {
        $q = $pdo->prepare('SELECT id_entrada, codigo, pelicula, cine, dia, horario, cantidad, precio
                            FROM entradas WHERE id_entrada = ? AND id_cliente = ?');
        $q->execute([(int) $_SESSION['candy_entrada'], usuario_id()]);
        $entradaCtx = $q->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $ex) {
        $entradaCtx = null;
    }
    if ($entradaCtx === null) {
        unset($_SESSION['candy_entrada']);
    }
}

// Precio de la entrada ya reservada (0 si el usuario entró al Candy sin entrada)
$precioEntrada = $entradaCtx ? (int) $entradaCtx['precio'] : 0;
$cantEntrada   = $entradaCtx ? (int) $entradaCtx['cantidad'] : 0;

$categorias = [
    'combos'  => 'Combos Dúo',
    'popcorn' => 'Pochoclos',
    'drinks'  => 'Bebidas',
    'sweets'  => 'Dulces y Chocolates',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candy</title>
    <link rel="stylesheet" href="<?= ver('../assets/css/candy.css') ?>">
    <link rel="stylesheet" href="<?= ver('../assets/css/candy_extra.css') ?>">
    <link rel="icon" href="../assets/img/Logo.png">
</head>
<body>


<?php include __DIR__ . '/../includes/navbar_usuario.php'; ?>
    <main class="main-container">

        <aside class="sidebar-categories" id="sidebar-categorias">
            <h3>Armá tu Combo</h3>
            <ul>
                <?php $primera = true; foreach ($categorias as $clave => $titulo): ?>
                <li class="category-item<?= $primera ? ' active' : '' ?>" data-category="<?= e($clave) ?>"><?= e($titulo) ?></li>
                <?php $primera = false; endforeach; ?>
            </ul>
        </aside>

        <section class="products-grid" id="productos">
            <?php $n = 0; foreach ($CATALOGO as $pid => $p): $n++; ?>
            <div class="product-card" data-category="<?= e($p['cat']) ?>">
                <div class="badge"><?= $n ?></div>
                <div class="product-container-img">
                    <img src="../assets/img/<?= e($p['img']) ?>" alt="<?= e($p['nombre']) ?>" class="product-img-file">
                </div>
                <div class="product-info">
                    <h4><?= e($p['nombre']) ?></h4>
                    <p><?= e($p['desc']) ?></p>
                    <span class="price"><?= e(pesos($p['precio'])) ?></span>
                    <button type="button" class="btn-add"
                            data-id="<?= e($pid) ?>"
                            data-nombre="<?= e($p['nombre']) ?>"
                            data-precio="<?= (int) $p['precio'] ?>">Añadir al Carrito</button>
                </div>
            </div>
            <?php endforeach; ?>
        </section>

        <!-- Paso de pago: reemplaza el centro de la pantalla.
             Solo se eligen medio de pago y retiro; los botones están en el panel derecho. -->
        <section class="paso-pago" id="paso-pago" hidden>

            <!-- El script del carrito completa este resumen; se mantiene oculto -->
            <div class="modal-resumen" id="pago-resumen" hidden></div>

            <fieldset class="modal-grupo">
                <legend>Medio de pago</legend>
                <?php $i = 0; foreach ($METODOS as $clave => $titulo): ?>
                <label class="opcion">
                    <input type="radio" name="metodo" value="<?= e($clave) ?>"<?= $i === 0 ? ' checked' : '' ?>>
                    <span><?= e($titulo) ?></span>
                </label>
                <?php $i++; endforeach; ?>
            </fieldset>

            <fieldset class="modal-grupo">
                <legend>Retiro</legend>
                <label class="opcion">
                    <input type="radio" name="retiro" value="mostrador" checked>
                    <span>En el mostrador</span>
                </label>
                <label class="opcion">
                    <input type="radio" name="retiro" value="express">
                    <span>Fila Express</span>
                </label>
            </fieldset>
        </section>

        <aside class="checkout-panel">
            <div class="pickup-info">
                Recogida:
                <?php if ($entradaCtx && $entradaCtx['horario'] !== ''): ?>
                    <span><?= e($entradaCtx['horario']) ?><?= $entradaCtx['cine'] !== '' ? ' - ' . e($entradaCtx['cine']) : '' ?></span>
                <?php else: ?>
                    <span>En el mostrador del Candy</span>
                <?php endif; ?>
            </div>

            <div class="checkout-header">
                <h3>Tu Resumen</h3>
                <button id="btn-clear-all" class="btn-clear" type="button">Vaciar todo</button>
            </div>

            <div id="cart-items" class="cart-items">
                <p class="empty-cart">El carrito está vacío</p>
            </div>

            <div id="candy-error" class="candy-error" hidden></div>

            <!-- Totales: primero la entrada, luego los combos y después el total.
                 Quedan siempre visibles, también durante el paso de pago. -->
            <div class="total-section">
                <?php if ($precioEntrada > 0): ?>
                <div class="total-row" style="font-size:15px;font-weight:normal;margin-bottom:8px;">
                    <span><?= $cantEntrada > 1 ? 'Entradas (' . $cantEntrada . ')' : 'Entrada' ?></span>
                    <span><?= e(pesos($precioEntrada)) ?></span>
                </div>
                <div class="total-row" style="font-size:15px;font-weight:normal;margin-bottom:8px;">
                    <span>Combos</span>
                    <span id="cart-total">$ 0</span>
                </div>
                <div class="total-row" style="border-top:1px solid #2b2933;padding-top:12px;">
                    <span>Total:</span>
                    <span id="total-general" style="color:#e2b13c;"><?= e(pesos($precioEntrada)) ?></span>
                </div>
                <?php else: ?>
                <div class="total-row">
                    <span>Total:</span>
                    <span id="cart-total">$ 0</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Botones del carrito -->
            <div id="acciones-carrito">
                <button type="button" class="btn-pay" id="btn-pay">Proseguir al Pago</button>
                <button type="button" class="btn-express" id="btn-express">Recoge en Fila Express</button>

                <?php if ($entradaCtx): ?>
                <form method="POST" action="candy.php" class="form-sin-combos-panel">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="accion" value="sin_combos">
                    <button type="submit" class="btn-sin-combos-link">Continuar sin combos</button>
                </form>
                <?php endif; ?>
            </div>

            <!-- Botones del paso de pago (reemplazan a los del carrito mientras se elige el pago) -->
            <div id="acciones-pago" class="acciones-pago" hidden>
                <div class="candy-error" id="pago-error" hidden></div>
                <button type="button" class="btn-modal-pagar" id="pago-pagar">Pagar</button>
                <button type="button" class="btn-modal-cancelar" id="pago-volver">← Volver a los combos</button>
            </div>
        </aside>

    </main>

    <script>
        window.CANDY = {
            csrf: <?= json_encode(csrf_token()) ?>,
            maxPorProducto: <?= MAX_POR_PRODUCTO ?>,
            precioEntrada: <?= (int) $precioEntrada ?>,
            logueado: <?= usuario_logueado() ? 'true' : 'false' ?>,
            loginUrl: 'login.php?redirect=' + encodeURIComponent('candy.php')
        };
    </script>
    <script src="<?= ver('../assets/js/candy.js') ?>"></script>

      <?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>