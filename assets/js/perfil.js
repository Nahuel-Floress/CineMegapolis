document.addEventListener('DOMContentLoaded', () => {
  const wrap = document.querySelector('.perfil-wrap');
  if (!wrap) return;
  const CSRF = wrap.dataset.csrf;

  const alerta = document.getElementById('alerta');
  if (alerta) {
    const cerrar = () => alerta.remove();
    alerta.querySelector('.alerta-cerrar')?.addEventListener('click', cerrar);
    setTimeout(cerrar, 7000);
  }

  const items = document.querySelectorAll('.menu-item');
  const paneles = document.querySelectorAll('.panel');
  let vistasMarcadas = false;

  function marcarVistas() {
    const badge = document.getElementById('badgeEntradas');
    if (vistasMarcadas || !badge) return;
    vistasMarcadas = true;

    const body = new URLSearchParams({ accion: 'marcar_vistas', csrf: CSRF });
    fetch('perfil.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      credentials: 'same-origin',
      body: body.toString()
    })
      .then(r => r.json())
      .then(d => { if (d.ok) badge.remove(); else vistasMarcadas = false; })
      .catch(() => { vistasMarcadas = false; });
  }

  function mostrarPanel(nombre, guardarHash = true) {
    if (!document.getElementById('panel-' + nombre)) nombre = 'config';
    items.forEach(b => b.classList.toggle('activo', b.dataset.panel === nombre));
    paneles.forEach(p => p.classList.toggle('activo', p.id === 'panel-' + nombre));
    if (guardarHash) history.replaceState(null, '', '#' + nombre);
    if (nombre === 'entradas') marcarVistas();
  }

  items.forEach(b => b.addEventListener('click', () => mostrarPanel(b.dataset.panel)));
  mostrarPanel(location.hash.replace('#', '') || 'config', false);

  const bio = document.getElementById('biografia');
  const contBio = document.getElementById('contBio');
  if (bio && contBio) {
    const contar = () => (contBio.textContent = bio.value.length);
    bio.addEventListener('input', contar);
    contar();
  }

  document.querySelectorAll('.ver-pass').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.target);
      if (!input) return;
      const oculto = input.type === 'password';
      input.type = oculto ? 'text' : 'password';
      btn.setAttribute('aria-label', oculto ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
  });

  const inputFoto = document.getElementById('inputFoto');
  const formFoto = document.getElementById('formFoto');
  const btnCambiar = document.getElementById('btnCambiarFoto');
  const avatarImg = document.getElementById('avatarImg');
  const avatarIniciales = document.getElementById('avatarIniciales');
  const formRestaurar = document.getElementById('formRestaurar');

  function error(msg) {
    alert(msg);
    inputFoto.value = '';
  }

  btnCambiar?.addEventListener('click', () => inputFoto.click());

  inputFoto?.addEventListener('change', () => {
    const archivo = inputFoto.files[0];
    if (!archivo) return;

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(archivo.type)) {
      return error('Formato no válido. Usá JPG, PNG o WEBP.');
    }
    if (archivo.size > 2 * 1024 * 1024) {
      return error('La imagen pesa demasiado (máximo 2 MB).');
    }

    avatarImg.src = URL.createObjectURL(archivo);
    avatarImg.hidden = false;
    if (avatarIniciales) avatarIniciales.hidden = true;
    btnCambiar.disabled = true;
    btnCambiar.textContent = 'Subiendo...';
    formFoto.submit();
  });

  formRestaurar?.addEventListener('submit', (ev) => {
    if (!confirm('¿Querés volver a las iniciales en lugar de tu foto?')) ev.preventDefault();
  });
});