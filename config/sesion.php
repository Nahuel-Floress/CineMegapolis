<?php

if (session_status() === PHP_SESSION_NONE) {
    $duracion = 60 * 60 * 24 * 30;

    ini_set('session.gc_maxlifetime', (string) $duracion);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $cookie = [
        'lifetime' => $duracion,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']),
    ];
    session_set_cookie_params($cookie);
    session_start();

    if (!empty($_SESSION['usuario_id']) && !headers_sent()) {
        $cookie['expires'] = time() + $duracion;
        unset($cookie['lifetime']);
        setcookie(session_name(), session_id(), $cookie);
    }
}

if (!function_exists('usuario_logueado')) {
    function usuario_logueado(): bool
    {
        return !empty($_SESSION['usuario_id']);
    }
}

if (!function_exists('usuario_id')) {
    function usuario_id(): ?int
    {
        return usuario_logueado() ? (int) $_SESSION['usuario_id'] : null;
    }
}

if (!function_exists('requerir_login')) 
    {

    function requerir_login(bool $conservar_datos = false): void
    {
        if (!usuario_logueado()) {
            $destino = basename($_SERVER['SCRIPT_NAME'] ?? 'perfil.php');
            if ($conservar_datos && !empty($_SERVER['QUERY_STRING'])) {
                $destino .= '?' . $_SERVER['QUERY_STRING'];
            }
            header('Location: login.php?redirect=' . urlencode($destino));
            exit();
        }
    }
}

if (!function_exists('e')) {
    function e($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }
}

if (!function_exists('csrf_valido')) {
    function csrf_valido(): bool
    {
        return isset($_POST['csrf'], $_SESSION['csrf'])
            && is_string($_POST['csrf'])
            && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    }
}