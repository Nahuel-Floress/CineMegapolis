<?php require_once __DIR__ . '/../config/sesion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lilo y stitch - Detalle</title>
  <link rel="stylesheet" href="../assets/css/peliculas.css">
  <link rel="icon" href="../assets/img/Logo.png">
</head>

<body>

  <?php include __DIR__ . '/../includes/navbar_usuario.php'; ?>
  <main class="container">

    
    <section class="left-col">
      <h2 class="movie-title">Destino final lazos de sangre</h2>

      <div class="poster-card">
        <div class="poster">
          
          <a href="https://www.youtube.com/watch?v=NkcT00FMTu8">
          <img src="../assets/img/ds2.jpg" alt="Poster Telefono Negro 2">
          </a> 
          <div class="badge-time">1h 50m</div>
          <div class="badge-age">S16</div>
          <div class="play-overlay" title="Ver trailer"></div>
        </div>

        <div class="poster-meta">
          <div>
            <span class="tag">Comedia</span>
          </div>

          <p class="synopsis">
            "Destino Final: Lazos de Sangre" sigue a Stefani, una universitaria atormentada por una pesadilla recurrente sobre un accidente. Ella regresa a casa para investigar el sueño y descubre que está relacionado con su abuela, quien logró eludir a la muerte años atrás.
          </p>

          <div class="info-grid">
            <div class="info-row"><strong>Formatos disponibles</strong><br>2D · XD DIGITAL · COMFORT · PREMIUM CLASS</div>
            <div class="info-row"><strong>Duración</strong><br>1h 50m</div>
            <div class="info-row"><strong>Fecha de estreno</strong><br>16 Octubre, 2025</div>
            <div class="info-row"><strong>Distribuidor</strong><br>United International Pictures S.R.L.</div>
          </div>
        </div>
      </div>

      
      <div style="margin-top:18px;color:#bfbfbf;font-size:13px;line-height:1.6">
        <p><strong>Sinopsis completa:</strong></p>Acosada por una violenta pesadilla recurrente, la estudiante universitaria Stefanie se dirige a casa para localizar a la única persona que podría ser capaz de romper el ciclo y salvar a su familia de la espeluznante muerte que inevitablemente les espera a todos.
      </div>
    </section>


    <aside class="right-col">
      <h3 class="section-title">HORARIOS</h3>

      <div class="day-tabs">
        <div class="tab active">HOY</div>
        <div class="tab">MAR 4/NOV</div>
        <div class="tab">MIÉ 5/NOV</div>
      </div>

      <div class="filters">
        <select class="select"><option>Formatos ▾</option><option>2D</option><option>XD</option></select>
        <select class="select"><option>Idioma ▾</option><option>Castellano</option><option>Subtitulada</option></select>
      </div>

      <div class="availability">
        <span style="font-weight:700;margin-right:6px">Disponibilidad de butacas</span>
        <div style="display:flex;gap:8px;align-items:center">
          <span><span class="dot high"></span>Alta</span>
          <span><span class="dot medium"></span>Media</span>
          <span><span class="dot low"></span>Baja</span>
          <span><span class="dot full"></span>Completa</span>
        </div>
      </div>

      <div class="cinema-block">
        <div class="cinema-name">HORARIOS EN <span style="color:var(--white);">CINE</span></div>
        <div style="font-size:13px;color:#bfbfbf;margin-top:6px">Dirección: Av. Güemes 897, Shopping Alto Avellaneda</div>

        <div style="margin-top:12px" class="sessions">
          <div class="session">
            <div>
              <div class="time">22:20 hs</div>
              <small>XD • 2D · Castellano</small>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end">
              <button style="background:var(--accent);color:#fff;border:none;padding:8px 12px;border-radius:8px;cursor:pointer;font-weight:800">Comprar</button>
              <small style="color:#bfbfbf">Butacas: Media</small>
            </div>
          </div>

          <div class="session">
            <div>
              <div class="time">19:40 hs</div>
              <small>2D · Castellano</small>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end">
              <button style="background:var(--accent);color:#fff;border:none;padding:8px 12px;border-radius:8px;cursor:pointer;font-weight:800">Comprar</button>
              <small style="color:#bfbfbf">Butacas: Alta</small>
            </div>
          </div>
        </div>

        <div class="near-cinemas" style="margin-top:14px">
          <strong style="color:var(--white)">CINES MÁS CERCANOS</strong>
          <div style="margin-top:8px;color:#bfbfbf;font-size:13px">Ver los cines que se encuentran cerca del cine seleccionado con horarios disponibles para esta película.</div>
        </div>
      </div>
    </aside>

  </main>

  
  <script>
    // ---------- TABS DE DÍA (HOY / MAR / MIÉ) ----------
    const dayTabs = document.querySelectorAll('.day-tabs .tab');
    dayTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        dayTabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        // Acá se podría más adelante recargar los horarios según el día elegido
      });
    });

    // ---------- BOTONES "COMPRAR" ----------
    const peliculaTitulo = document.querySelector('.movie-title').textContent.trim();
    const cineNombre = document.querySelector('.cinema-name').textContent.replace('HORARIOS EN', '').trim();

    document.querySelectorAll('.session').forEach(session => {
      const btn = session.querySelector('button');
      if (!btn) return;

      btn.addEventListener('click', () => {
        const diaActivo = document.querySelector('.day-tabs .tab.active');
        const dia = diaActivo ? diaActivo.textContent.trim() : 'HOY';
        const horario = session.querySelector('.time').textContent.trim();
        const detalle = session.querySelector('small').textContent.trim();
        const butacas = session.querySelector('small[style]')
          ? session.querySelector('small[style]').textContent.replace('Butacas:', '').trim()
          : '';

        const params = new URLSearchParams({
          pelicula: peliculaTitulo,
          cine: cineNombre,
          dia: dia,
          horario: horario,
          detalle: detalle,
          butacas: butacas
        });

        window.location.href = 'compra.php?' + params.toString();
      });
    });
  </script>
  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
