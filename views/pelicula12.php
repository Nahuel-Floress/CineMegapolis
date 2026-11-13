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
      <h2 class="movie-title">MILEY CYRUS SOMETHING BEAUTIFUL</h2>

      <div class="poster-card">
        <div class="poster">
          
          <a href="https://www.youtube.com/watch?v=y2nu8zpVBmY">
          <img src="../assets/img/ds12.jpg" alt="Poster Mision imposible">
          </a> 
          <div class="badge-time">55m</div>
          <div class="badge-age">APT</div>
          <div class="play-overlay" title="Ver trailer"></div>
        </div>

        <div class="poster-meta">
          <div>
            <span class="tag">Aventura</span>
          </div>

          <p class="synopsis">
          Miley Cyrus: Something BeautifulUna ambiciosa ópera pop y experiencia cinematográfica conceptual ideada por Miley Cyrus. A través de trece canciones originales, la ganadora del Grammy nos sumerge en una odisea visual de fantasía que explora el existencialismo, la sanación de traumas y la búsqueda de la belleza en la oscuridad.
          </p>

          <div class="info-grid">
            <div class="info-row"><strong>Formatos disponibles</strong><br>2D</div>
            <div class="info-row"><strong>Duración</strong><br>55m</div>
            <div class="info-row"><strong>Fecha de estreno</strong><br> 6 de junio de 2025  </div>
            <div class="info-row"><strong>Distribuidor</strong><br>Disney</div>
          </div>
        </div>
      </div>

      
      <div style="margin-top:18px;color:#bfbfbf;font-size:13px;line-height:1.6">
        <p><strong>Sinopsis completa:</strong></p>Más que un concierto fílmico, Something Beautiful es un largometraje musical de vanguardia que acompaña al noveno álbum de estudio de Miley Cyrus. Inspirada en estructuras épicas clásicas como The Wall de Pink Floyd pero con un enfoque pop glamuroso, la cinta entrelaza relatos de surrealismo y alta costura. Acompañada por figuras icónicas del arte y el entretenimiento, Cyrus utiliza la música y una hipnótica propuesta fímica como una terapia colectiva frente a las crisis de la sociedad contemporánea.
    </section>


    <aside class="right-col">
      <h3 class="section-title">HORARIOS</h3>

      <div class="day-tabs">
        <div class="tab active">HOY</div>
        <div class="tab">MAR 30/JUN</div>
        <div class="tab">MIÉ 31/JUN</div>
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
