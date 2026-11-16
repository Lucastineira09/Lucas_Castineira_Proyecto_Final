<?php
// Crea (o reutiliza) un token secreto guardado en la sesión
function generarTokenCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Comprueba que el token recibido en el formulario sea el de la sesión
function validarTokenCsrf(?string $token): bool {
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}