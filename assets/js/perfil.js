/* Perfil de usuario - CineMegapolis */
(function () {
  'use strict';

  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

  /* ---------- Aviso de entradas nuevas ---------- */
  var badge = $('#badgeEntradas');
  var wrap = $('.perfil-wrap');

  // Al entrar a "Entradas compradas" el número desaparece y se avisa al servidor que ya las vio
  function marcarEntradasVistas() {
    if (!badge) { return; }
    badge.remove();
    badge = null;

    var datos = new URLSearchParams();
    datos.append('csrf', wrap ? wrap.getAttribute('data-csrf') : '');
    datos.append('accion', 'marcar_vistas');

    fetch('perfil.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      credentials: 'same-origin',
      body: datos.toString()
    }).catch(function () { /* si falla, el número vuelve a aparecer la próxima vez */ });
  }

  /* ---------- Pestañas (Configuración / Entradas compradas) ---------- */
  var items = $$('.menu-item');
  var paneles = $$('.panel');

  function mostrarPanel(nombre, actualizarHash) {
    var existe = paneles.some(function (p) { return p.id === 'panel-' + nombre; });
    if (!existe) { nombre = 'config'; }

    paneles.forEach(function (p) { p.classList.toggle('activo', p.id === 'panel-' + nombre); });
    items.forEach(function (b) { b.classList.toggle('activo', b.getAttribute('data-panel') === nombre); });

    if (nombre === 'entradas') { marcarEntradasVistas(); }

    if (actualizarHash) {
      if (history.replaceState) { history.replaceState(null, '', '#' + nombre); }
      else { location.hash = nombre; }
    }
  }

  items.forEach(function (btn) {
    btn.addEventListener('click', function () {
      mostrarPanel(btn.getAttribute('data-panel'), true);
    });
  });

  window.addEventListener('hashchange', function () {
    mostrarPanel(location.hash.replace('#', ''), false);
  });

  // Panel inicial según el hash (#config / #entradas)
  mostrarPanel(location.hash.replace('#', '') || 'config', false);

  /* ---------- Foto de perfil ---------- */
  var inputFoto = $('#inputFoto');
  var btnCambiar = $('#btnCambiarFoto');
  var formFoto = $('#formFoto');
  var img = $('#avatarImg');
  var iniciales = $('#avatarIniciales');
  var MAX_BYTES = 2 * 1024 * 1024;
  var TIPOS = ['image/jpeg', 'image/png', 'image/webp'];

  function mostrarAlerta(texto) {
    var alerta = $('#alerta');
    if (!alerta) {
      alerta = document.createElement('div');
      alerta.id = 'alerta';
      alerta.className = 'alerta alerta-error';
      alerta.setAttribute('role', 'alert');
      alerta.innerHTML = '<span></span><button type="button" class="alerta-cerrar" aria-label="Cerrar">&times;</button>';
      $('.perfil-card').insertBefore(alerta, $('.perfil-card').firstChild);
      activarCierre(alerta);
    }
    alerta.className = 'alerta alerta-error';
    $('span', alerta).textContent = texto;
  }

  if (btnCambiar && inputFoto) {
    btnCambiar.addEventListener('click', function () { inputFoto.click(); });

    inputFoto.addEventListener('change', function () {
      var archivo = inputFoto.files && inputFoto.files[0];
      if (!archivo) { return; }

      if (TIPOS.indexOf(archivo.type) === -1) {
        mostrarAlerta('Formato no válido. Usá JPG, PNG o WEBP.');
        inputFoto.value = '';
        return;
      }
      if (archivo.size > MAX_BYTES) {
        mostrarAlerta('La imagen pesa demasiado (máximo 2 MB).');
        inputFoto.value = '';
        return;
      }

      // Vista previa inmediata y envío automático
      var lector = new FileReader();
      lector.onload = function (e) {
        img.src = e.target.result;
        img.hidden = false;
        if (iniciales) { iniciales.style.display = 'none'; }
        btnCambiar.disabled = true;
        btnCambiar.textContent = 'Subiendo...';
        formFoto.submit();
      };
      lector.readAsDataURL(archivo);
    });
  }

  var formRestaurar = $('#formRestaurar');
  if (formRestaurar) {
    formRestaurar.addEventListener('submit', function (e) {
      if (!window.confirm('¿Querés volver a la foto por defecto?')) { e.preventDefault(); }
    });
  }

  /* ---------- Contador de la biografía ---------- */
  var bio = $('#biografia');
  var cont = $('#contBio');
  function actualizarContador() { if (bio && cont) { cont.textContent = bio.value.length; } }
  if (bio) {
    bio.addEventListener('input', actualizarContador);
    actualizarContador();
  }

  /* ---------- Mostrar / ocultar contraseña ---------- */
  $$('.ver-pass').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var campo = document.getElementById(btn.getAttribute('data-target'));
      if (!campo) { return; }
      campo.type = campo.type === 'password' ? 'text' : 'password';
    });
  });

  /* ---------- Validación: contraseña nueva requiere la actual ---------- */
  var formConfig = $('#formConfig');
  if (formConfig) {
    formConfig.addEventListener('submit', function (e) {
      var actual = $('#pass_actual').value;
      var nueva = $('#pass_nueva').value;
      if (nueva !== '' && actual === '') {
        e.preventDefault();
        mostrarPanel('config', true);
        mostrarAlerta('Para cambiar la contraseña ingresá la actual.');
        $('#pass_actual').focus();
      }
    });
  }

  /* ---------- Cerrar alertas (y que se oculten solas) ---------- */
  function activarCierre(alerta) {
    var cerrar = $('.alerta-cerrar', alerta);
    if (cerrar) { cerrar.addEventListener('click', function () { alerta.remove(); }); }
  }
  var alertaInicial = $('#alerta');
  if (alertaInicial) { activarCierre(alertaInicial); }
})();