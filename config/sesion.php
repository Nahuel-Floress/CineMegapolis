<?php

if (session_status() === PHP_SESSION_NONE) {
    $duracion = 60 * 60 * 24 * 30; // 30 días

    ini_set('session.gc_maxlifetime', (string) $duracion);
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => $duracion,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']),
    ]);

    session_start();
}


function usuario_logueado(): bool
{
    return !empty($_SESSION['usuario_id']);
}

function usuario_id(): ?int
{
    return usuario_logueado() ? (int) $_SESSION['usuario_id'] : null;
}

function requerir_login(): void
{
    if (!usuario_logueado()) {
        $destino = basename($_SERVER['SCRIPT_NAME'] ?? 'perfil.php');
        header('Location: login.php?redirect=' . urlencode($destino));
        exit();
    }
}

function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}
