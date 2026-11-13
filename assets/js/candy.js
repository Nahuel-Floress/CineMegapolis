/*
 * Carrito del Candy.
 * Todo vive dentro de una función para no chocar con otros scripts de la página
 * (si otro script declara las mismas variables, el archivo viejo se rompía entero).
 */
(function () {
    'use strict';

    var CFG = window.CANDY || { csrf: '', maxPorProducto: 10, precioEntrada: 0, logueado: false, loginUrl: 'login.php' };
    var CLAVE_STORAGE = 'cinemegapolis_carrito_candy';

    var carrito = [];   // [{ id, nombre, precio, cantidad }]
    var total = 0;      // total de los combos
    var enPaso = false;

    function el(id) { return document.getElementById(id); }

    function pesos(n) {
        return '$ ' + Math.round(n).toLocaleString('es-AR');
    }

    function esc(texto) {
        var d = document.createElement('div');
        d.textContent = texto;
        return d.innerHTML;
    }

    // Entrada (si hay) + combos
    function totalGeneral() {
        return (CFG.precioEntrada || 0) + total;
    }

    /* ---------- persistencia ---------- */
    function guardarCarrito() {
        try { sessionStorage.setItem(CLAVE_STORAGE, JSON.stringify(carrito)); } catch (e) { /* sin storage */ }
    }

    function cargarCarrito() {
        try {
            var guardado = JSON.parse(sessionStorage.getItem(CLAVE_STORAGE) || '[]');
            if (Array.isArray(guardado)) {
                carrito = guardado.filter(function (i) {
                    return i && typeof i.id === 'string' && typeof i.nombre === 'string' &&
                        Number.isInteger(i.precio) && Number.isInteger(i.cantidad) && i.cantidad > 0;
                });
            }
        } catch (e) { carrito = []; }
    }

    function mostrarError(msg, id) {
        var e = el(id || 'candy-error');
        if (!e) return;
        e.textContent = msg || '';
        e.hidden = !msg;
    }

    /* ---------- operaciones del carrito ---------- */
    function calcularTotal() {
        total = carrito.reduce(function (sum, it) { return sum + it.precio * it.cantidad; }, 0);
    }

    function agregarAlCarrito(id, nombre, precio) {
        mostrarError('');
        var existente = carrito.find(function (i) { return i.id === id; });
        if (existente) {
            if (existente.cantidad >= CFG.maxPorProducto) {
                mostrarError('Máximo ' + CFG.maxPorProducto + ' unidades por producto.');
                return;
            }
            existente.cantidad += 1;
        } else {
            carrito.push({ id: id, nombre: nombre, precio: precio, cantidad: 1 });
        }
        renderizarCarrito();
    }

    function cambiarCantidad(id, delta) {
        var item = carrito.find(function (i) { return i.id === id; });
        if (!item) return;
        var nueva = item.cantidad + delta;
        if (nueva < 1) { eliminarDelCarrito(id); return; }
        if (nueva > CFG.maxPorProducto) {
            mostrarError('Máximo ' + CFG.maxPorProducto + ' unidades por producto.');
            return;
        }
        mostrarError('');
        item.cantidad = nueva;
        renderizarCarrito();
    }

    function eliminarDelCarrito(id) {
        carrito = carrito.filter(function (i) { return i.id !== id; });
        renderizarCarrito();
    }

    function vaciarCarrito() {
        carrito = [];
        mostrarError('');
        renderizarCarrito();
    }

    function renderizarCarrito() {
        calcularTotal();
        guardarCarrito();

        var contenedorItems = el('cart-items');
        var contenedorTotal = el('cart-total');
        var totalGen = el('total-general');
        var botonVaciar = el('btn-clear-all');
        if (!contenedorItems || !contenedorTotal) return;

        if (enPaso && carrito.length === 0) volverAProductos();
        if (enPaso) pintarResumenPago();

        // Total general (entrada + combos): solo existe si el usuario tiene una entrada
        if (totalGen) totalGen.textContent = pesos(totalGeneral());

        if (carrito.length === 0) {
            contenedorItems.innerHTML = '<p class="empty-cart">El carrito está vacío</p>';
            contenedorTotal.textContent = pesos(0);
            if (botonVaciar) botonVaciar.style.display = 'none';
            return;
        }

        if (botonVaciar) botonVaciar.style.display = 'block';
        contenedorItems.innerHTML = '';

        carrito.forEach(function (item) {
            var fila = document.createElement('div');
            fila.className = 'cart-item-row';
            fila.innerHTML =
                '<div class="cart-item-details">' +
                '<span class="cart-item-name">' + esc(item.nombre) + '</span>' +
                '<span class="cart-item-qty">' + pesos(item.precio) + ' c/u</span>' +
                '<div class="cart-qty-control">' +
                '<button type="button" class="qty-mini" data-accion="menos" aria-label="Quitar uno">−</button>' +
                '<span>' + item.cantidad + '</span>' +
                '<button type="button" class="qty-mini" data-accion="mas" aria-label="Agregar uno">+</button>' +
                '</div></div>' +
                '<div class="cart-item-right">' +
                '<strong>' + pesos(item.precio * item.cantidad) + '</strong>' +
                '<button type="button" class="btn-remove-item" data-accion="eliminar" title="Eliminar artículo">Eliminar</button>' +
                '</div>';
            fila.querySelector('[data-accion="menos"]').addEventListener('click', function () { cambiarCantidad(item.id, -1); });
            fila.querySelector('[data-accion="mas"]').addEventListener('click', function () { cambiarCantidad(item.id, 1); });
            fila.querySelector('[data-accion="eliminar"]').addEventListener('click', function () { eliminarDelCarrito(item.id); });
            contenedorItems.appendChild(fila);
        });

        contenedorTotal.textContent = pesos(total);
    }

    /* ---------- paso de pago ---------- */
    var OCULTAR_EN_PAGO = ['sidebar-categorias', 'productos', 'btn-pay', 'btn-express', 'acciones-carrito'];

    function pintarResumenPago() {
        var resumen = el('pago-resumen');
        var btnPagar = el('pago-pagar');
        if (!btnPagar) return;
        if (resumen) {
            resumen.innerHTML = carrito.map(function (i) {
                return '<div class="modal-linea"><span>' + i.cantidad + ' × ' + esc(i.nombre) + '</span><span>' +
                    pesos(i.precio * i.cantidad) + '</span></div>';
            }).join('') + '<div class="modal-linea modal-total"><span>Total</span><span>' + pesos(totalGeneral()) + '</span></div>';
        }
        // El precio aparece una sola vez, en el botón
        btnPagar.textContent = 'Pagar ' + pesos(totalGeneral());
    }

    function mostrarPaso(retiro) {
        mostrarError('');
        if (carrito.length === 0) {
            mostrarError('Agregá al menos un producto antes de pagar.');
            return;
        }
        enPaso = true;

        var radio = document.querySelector('#paso-pago input[name="retiro"][value="' + retiro + '"]');
        if (radio) radio.checked = true;
        mostrarError('', 'pago-error');
        pintarResumenPago();

        OCULTAR_EN_PAGO.forEach(function (id) { var e = el(id); if (e) e.hidden = true; });
        document.querySelectorAll('.form-sin-combos-panel').forEach(function (e) { e.hidden = true; });
        var acciones = el('acciones-pago');
        if (acciones) acciones.hidden = false;
        el('paso-pago').hidden = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function volverAProductos() {
        enPaso = false;
        var paso = el('paso-pago');
        if (paso) paso.hidden = true;
        var acciones = el('acciones-pago');
        if (acciones) acciones.hidden = true;
        OCULTAR_EN_PAGO.forEach(function (id) { var e = el(id); if (e) e.hidden = false; });
        document.querySelectorAll('.form-sin-combos-panel').forEach(function (e) { e.hidden = false; });
    }

    async function pagar() {
        var btn = el('pago-pagar');
        mostrarError('', 'pago-error');

        if (!CFG.logueado) {
            window.location.href = CFG.loginUrl;
            return;
        }

        var metodoEl = document.querySelector('#paso-pago input[name="metodo"]:checked');
        var retiroEl = document.querySelector('#paso-pago input[name="retiro"]:checked');
        var metodo = metodoEl ? metodoEl.value : '';
        var retiro = retiroEl ? retiroEl.value : 'mostrador';
        var textoOriginal = btn.textContent;

        btn.disabled = true;
        btn.textContent = 'Procesando pago...';

        try {
            var body = new URLSearchParams({
                accion: 'pagar',
                csrf: CFG.csrf,
                metodo: metodo,
                retiro: retiro,
                items: JSON.stringify(carrito.map(function (i) { return { id: i.id, cantidad: i.cantidad }; }))
            });

            var res = await fetch('candy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: body.toString()
            });

            if (res.status === 401) {
                window.location.href = CFG.loginUrl;
                return;
            }

            var texto = await res.text();
            var data;
            try {
                data = JSON.parse(texto);
            } catch (e) {
                throw new Error('El servidor devolvió una respuesta inválida: ' + texto.replace(/<[^>]*>/g, ' ').trim().slice(0, 250));
            }

            if (!data.ok) throw new Error(data.error || 'No se pudo completar el pago.');

            try { sessionStorage.removeItem(CLAVE_STORAGE); } catch (e) { /* nada */ }
            window.location.href = data.redirect || 'compra_exitosa.php';
        } catch (err) {
            mostrarError(err.message || 'Error de conexión. Probá de nuevo.', 'pago-error');
            btn.disabled = false;
            btn.textContent = textoOriginal;
        }
    }

    function filtrarProductos(categoria) {
        document.querySelectorAll('.product-card').forEach(function (card) {
            card.style.display = card.getAttribute('data-category') === categoria ? 'flex' : 'none';
        });
    }

    function iniciar() {
        try {
            var categorias = document.querySelectorAll('.category-item');
            filtrarProductos('combos');

            categorias.forEach(function (item) {
                item.addEventListener('click', function () {
                    categorias.forEach(function (i) { i.classList.remove('active'); });
                    item.classList.add('active');
                    filtrarProductos(item.getAttribute('data-category'));
                });
            });

            // Delegación: funciona aunque los botones se re-rendericen
            document.addEventListener('click', function (ev) {
                var btn = ev.target.closest ? ev.target.closest('.btn-add') : null;
                if (!btn) return;
                agregarAlCarrito(btn.dataset.id, btn.dataset.nombre, parseInt(btn.dataset.precio, 10));
            });

            var vaciar = el('btn-clear-all');
            if (vaciar) vaciar.addEventListener('click', vaciarCarrito);
            var pay = el('btn-pay');
            if (pay) pay.addEventListener('click', function () { mostrarPaso('mostrador'); });
            var express = el('btn-express');
            if (express) express.addEventListener('click', function () { mostrarPaso('express'); });
            var volver = el('pago-volver');
            if (volver) volver.addEventListener('click', volverAProductos);
            var pagarBtn = el('pago-pagar');
            if (pagarBtn) pagarBtn.addEventListener('click', pagar);

            cargarCarrito();
            renderizarCarrito();
        } catch (err) {
            console.error('candy.js:', err);
            mostrarError('Error al iniciar el carrito: ' + err.message);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();