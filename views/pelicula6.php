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
      <h2 class="movie-title">DAN DA DAN EVIL EVE</h2>

      <div class="poster-card">
        <div class="poster">
          
          <a href="https://www.youtube.com/watch?v=Dk9tRbXkGzE">
          <img src="../assets/img/ds6.jpg" alt="Poster Mision imposible">
          </a> 
          <div class="badge-time">1h 33m</div>
          <div class="badge-age">S13</div>
          <div class="play-overlay" title="Ver trailer"></div>
        </div>

        <div class="poster-meta">
          <div>
            <span class="tag">Anime</span>
          </div>

          <p class="synopsis">
          Momo, Okarun y su grupo viajan a un pueblo de aguas termales para resolver un misterio familiar, sin imaginar que despertarán al "Mal de Ojo", una de las maldiciones más peligrosas del mundo espiritual. El aclamado estudio Science SARU regresa a la pantalla grande con su inconfundible cóctel de acción frenética, aliens, fantasmas y romance adolescente.
          </p>

          <div class="info-grid">
            <div class="info-row"><strong>Formatos disponibles</strong><br>2D</div>
            <div class="info-row"><strong>Duración</strong><br>1h 33m</div>
            <div class="info-row"><strong>Fecha de estreno</strong><br> 30 de mayo de 2025 </div>
            <div class="info-row"><strong>Distribuidor</strong><br>Crunchyroll / GKIDS / Sugoi Co</div>
          </div>
        </div>
      </div>

      
      <div style="margin-top:18px;color:#bfbfbf;font-size:13px;line-height:1.6">
        <p><strong>Sinopsis completa:</strong></p>En Dan Da Dan: Evil Eye, Momo Ayase y Ken "Okarun" Takakura se embarcan en una nueva y paranormal aventura. El dúo viaja a una casa alquilada por Jiji, el amigo de la infancia y antiguo amor de Momo, con el objetivo de limpiar una aparente maldición sobre su familia. Sin embargo, la investigación se descarrila rápidamente cuando descubren que el lugar es el hogar de sacrificios del temible "Mal de Ojo" (Evil Eye), un Yokai despiadado obsesionado con destruir a la humanidad. Una explosiva carrera de supervivencia que mezcla la comedia romántica con el horror sobrenatural de vanguardia
      </div>
    </section>


    <aside class="right-col">
      <h3 class="section-title">HORARIOS</h3>

      <div class="day-tabs">
        <div class="tab active">HOY</div>
        <div class="tab">MAR 30/mayo</div>
        <div class="tab">MIÉ 31/MAY</div>
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
        <div class="cinema-name">HORARIOS EN <span style="color:var(--white);">CINEMARK ALTO AVELLANEDA</span></div>
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
    const dayTabs = document.querySelectorAll('.day-tabs .tab');
    dayTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        dayTabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
      });
    });

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
