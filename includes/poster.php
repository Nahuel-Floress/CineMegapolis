<?php
/*
 * includes/poster.php
 * Encuentra el archivo del póster de una película (dentro de assets/img/).
 *
 * Orden de búsqueda:
 *   1) El nombre que mande la página de la película (?imagen=archivo.jpg), si el archivo existe.
 *   2) La tabla "peliculas" (columnas imagen / poster / portada / img / foto ...), buscando por título.
 *   3) Una imagen en assets/img cuyo nombre coincida con el título de la película.
 *
 * Devuelve solo el nombre del archivo ('' si no encuentra ninguno).
 */

const POSTER_DIR = __DIR__ . '/../assets/img';

function poster_normalizar(string $s): string
{
    $s = mb_strtolower($s, 'UTF-8');
    $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    return preg_replace('/[^a-z0-9]+/', '', $s) ?? '';
}

function poster_existe(string $ruta): string
{
    $nombre = basename(str_replace('\\', '/', trim($ruta)));
    if ($nombre === '' || !preg_match('/^[\w\-. ()]+\.(png|jpe?g|webp|gif)$/iu', $nombre)) {
        return '';
    }
    return is_file(POSTER_DIR . '/' . $nombre) ? $nombre : '';
}

function buscar_poster(PDO $pdo, string $titulo, string $sugerida = ''): string
{
    // 1) La que mandó la páginacs
    if ($sugerida !== '') {
        $ok = poster_existe($sugerida);
        if ($ok !== '') {
            return $ok;
        }
    }

    $titulo = trim($titulo);
    if ($titulo === '') {
        return '';
    }

    // 2) Tabla peliculas
    try {
        $q = $pdo->prepare('SELECT * FROM peliculas WHERE titulo = ? LIMIT 1');
        $q->execute([$titulo]);
        $fila = $q->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            foreach (['imagen', 'poster', 'portada', 'img', 'foto', 'imagen_url', 'url_imagen', 'cartel'] as $col) {
                if (!empty($fila[$col])) {
                    $ok = poster_existe((string) $fila[$col]);
                    if ($ok !== '') {
                        return $ok;
                    }
                }
            }
        }
    } catch (PDOException $ex) {
        // la tabla o la columna no existen: seguimos con el plan C
    }

    // 3) Buscar en la carpeta por nombre
    static $archivos = null;
    if ($archivos === null) {
        $archivos = [];
        foreach (@scandir(POSTER_DIR) ?: [] as $f) {
            if (preg_match('/\.(png|jpe?g|webp|gif)$/i', $f)) {
                $archivos[$f] = poster_normalizar(pathinfo($f, PATHINFO_FILENAME));
            }
        }
    }

    $clave = poster_normalizar($titulo);
    if ($clave === '') {
        return '';
    }
    foreach ($archivos as $archivo => $norm) {   // coincidencia exacta
        if ($norm === $clave) {
            return $archivo;
        }
    }
    if (strlen($clave) >= 4) {
        foreach ($archivos as $archivo => $norm) {   // el nombre del archivo contiene el título
            if (strpos($norm, $clave) !== false) {
                return $archivo;
            }
        }
    }
    return '';
}