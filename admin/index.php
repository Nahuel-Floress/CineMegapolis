<?php
require_once '../config/conexion.php';

// Eliminar película
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $stmt = $conexion->prepare("DELETE FROM peliculas WHERE id_pelicula = :id");
    $stmt->execute([':id' => $id]);
    header("Location: index.php?msg=eliminado");
    exit;
}

// Consultar todas
$peliculas = $conexion->query("SELECT * FROM peliculas ORDER BY id_pelicula DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración - CineMegapolis</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Panel de Administración (CRUD)</h2>
        <div>
            <a href="agregar_pelicula.php" class="btn btn-success">+ Nueva Película</a>
            <a href="../index.php" class="btn btn-outline-secondary">Ir al Sitio</a>
        </div>
    </div>

    <table class="table table-bordered align-middle">
        <thead class="table-dark">
            <tr>
                    <th>id_pelicula</th> 
                    <th>Título</th>
                    <th>Género</th>
                    <th>Duración</th>
                    <th>Clasificación</th>
                    <th>Estado</th>
                    <th>Formato</th>
                    <th>Idioma</th>
                    <th>Descripcion</th>
                    <th>Fecha_estreno</th>
                    <th>Distribuidor</th>
                    <th>Imagen</th>
                    <th>Trailer</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($peliculas as $p): ?>
                <tr>
                    <td><?= $p['id_pelicula'] ?></td>
                    <td><strong><?= htmlspecialchars($p['titulo']) ?></strong></td>
                    <td><?= htmlspecialchars($p['genero']) ?></td>
                    <td><?= $p['duracion'] ?> min</td>
                    <td><span class="badge bg-secondary"><?= $p['clasificacion'] ?></span></td>
                    <td><span class="badge bg-info text-dark"><?= $p['estado'] ?></span></td>
                    <td>
                        <a href="editar_pelicula.php?id=<?= $p['id_pelicula'] ?>" class="btn btn-sm btn-warning">Editar</a>
                        <a href="index.php?eliminar=<?= $p['id_pelicula'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar película?');">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>