<?php require_once __DIR__ . '/../config/sesion.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regala Cine</title>
    <link rel="stylesheet" href="../assets/css/regalacine.css">
    <link rel="icon" href="../assets/img/Logo.png">
</head>
<body>

  <?php include __DIR__ . '/../includes/navbar_usuario.php'; ?>  
    <div class="banner-container">
        <img src="../assets/img/banner.jpg" alt="Banner" class="banner-img">
    </div>

     <div class="sub-contenedor">
  <h2 class="sub">LAS EXPERIENCIAS</h2>
</div>
<div class="tres">
  <h4 class="cuatro">Para regalar o para vos</h4>
</div>

<section class="cards-grid">
  <article class="card">
    <div class="card-media" style="background-image:url('../assets/img/pag2.jpg')"></div>
    <div class="card-body">
      <h3 class="card-title">EXPERIENCIA CLÁSICA</h3>
      <p class="card-desc">2 entradas 2D/3D • 1 combo mega (1 balde de pochoclos + 2 gaseosas) • Disponible en salas seleccionadas.</p>
      <div class="card-foot">
        <span class="card-price">$ 36.900</span>
        <button class="card-btn">VER DETALLES</button>
      </div>
    </div>
  </article>

  <article class="card">
    <div class="card-media" style="background-image:url('../assets/img/pag2-2.jpg')"></div>
    <div class="card-body">
      <h3 class="card-title">EXPERIENCIA GIGANTE</h3>
      <p class="card-desc">2 entradas 2D/3D • 1 combo mega (1 balde de pochoclos + 2 gaseosas) • Disponible en salas seleccionadas.</p>
      <div class="card-foot">
        <span class="card-price">$ 37.900</span>
        <button class="card-btn">VER DETALLES</button>
      </div>
    </div>
  </article>

  <article class="card">
    <div class="card-media" style="background-image:url('../assets/img/pag2-3.jpg')"></div>
    <div class="card-body">
      <h3 class="card-title">EXPERIENCIA MÁXIMO COMFORT</h3>
      <p class="card-desc">2 entradas Comfort • 1 combo mega (1 balde de pochoclos + 2 gaseosas) • Disponible en salas Comfort.</p>
      <div class="card-foot">
        <span class="card-price">$ 36.700</span>
        <button class="card-btn">VER DETALLES</button>
      </div>
    </div>
  </article>
</section>
  
 <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>