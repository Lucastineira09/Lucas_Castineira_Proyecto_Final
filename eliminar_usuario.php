<?php
session_start();

// Solo administradores
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado.');
}

// Solo se acepta POST: eliminar nunca se hace con un link
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

// Verificar el token CSRF
if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Solicitud no válida.');
}

// Validar que el id sea un entero positivo
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('Identificador inválido.');
}

// El admin no puede eliminar su propia cuenta
if ($id === (int)$_SESSION['usuario_id']) {
    $_SESSION['mensaje'] = 'No podés eliminar tu propia cuenta.';
    header('Location: usuarios_archivados.php');
    exit;
}

try {
    // Solo se pueden eliminar socios que ya están archivados
    $stmt = $conexion->prepare(
        'DELETE FROM usuarios
         WHERE id = :id AND archivado_en IS NOT NULL'
    );
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 1) {
        $_SESSION['mensaje'] = 'Socio eliminado definitivamente.';
    } else {
        $_SESSION['mensaje'] = 'No se encontró el socio o no está archivado.';
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    // El código 23000 indica que otra tabla depende de este usuario (por ejemplo, pagos)
    if ($e->getCode() === '23000') {
        $_SESSION['mensaje'] = 'No se puede eliminar: el socio tiene registros asociados (por ejemplo, pagos).';
    } else {
        $_SESSION['mensaje'] = 'No se pudo eliminar. Probá de nuevo.';
    }
}

header('Location: usuarios_archivados.php');
exit;