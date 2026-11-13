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
      <h2 class="movie-title">EL ESQUEMA FENICIO</h2>

      <div class="poster-card">
        <div class="poster">
          
          <a href="https://www.youtube.com/watch?v=YHyIH-KDuy4">
          <img src="../assets/img/ds5.jpg" alt="Poster Mision imposible">
          </a> 
          <div class="badge-time">1h 42m</div>
          <div class="badge-age">S13</div>
          <div class="play-overlay" title="Ver trailer"></div>
        </div>

        <div class="poster-meta">
          <div>
            <span class="tag">Comedia negra</span>
          </div>

          <p class="synopsis">
           El Esquema FenicioWes Anderson regresa con una ingeniosa comedia negra de espionaje ambientada en los años 50. Un turbio y extravagante magnate europeo (Benicio Del Toro) debe aliarse con su hija distanciada, una joven monja (Mia Threapleton), para salvar su vasto imperio empresarial de un complot internacional. Una joya visual de simetrías perfectas y humor refinado.
          </p>

          <div class="info-grid">
            <div class="info-row"><strong>Formatos disponibles</strong><br>2D</div>
            <div class="info-row"><strong>Duración</strong><br>1h 42m</div>
            <div class="info-row"><strong>Fecha de estreno</strong><br> 18 de mayo de 2025 </div>
            <div class="info-row"><strong>Distribuidor</strong><br>Universal Pictures</div>
          </div>
        </div>
      </div>

      
      <div style="margin-top:18px;color:#bfbfbf;font-size:13px;line-height:1.6">
        <p><strong>Sinopsis completa:</strong></p>En El Esquema Fenicio, nos adentramos en la sofisticada mente de Anatole "Zsa-zsa" Korda, un poderoso multimillonario que sufre constantes e inexplicables intentos de asesinato. Al descubrir una intriga geopolítica que amenaza con destruir su monumental "plan fenicio" —un millonario entramado de infraestructuras globales—, Korda decide cambiar su testamento en favor de su hija Liesl. Con la ayuda de un excéntrico entomólogo noruego, padre e hija intentarán reconstruir su relación rota mientras sortean traiciones familiares, dilemas morales e inversores de dudosa reputación.
      </div>
    </section>


    <aside class="right-col">
      <h3 class="section-title">HORARIOS</h3>

      <div class="day-tabs">
        <div class="tab active">HOY</div>
        <div class="tab">MAR 20/mayo</div>
        <div class="tab">MIÉ 19/MAY</div>
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
