<?php
session_start();

// Solo administradores
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado.');
}

// Solo se acepta POST: archivar nunca se hace con un link
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

// El admin no puede archivar su propia cuenta
if ($id === (int)$_SESSION['usuario_id']) {
    $_SESSION['mensaje'] = 'No podés archivar tu propia cuenta.';
    header('Location: admin_usuarios.php');
    exit;
}

try {
    // Borrado lógico: solo se guarda la fecha, el registro no se elimina
    $stmt = $conexion->prepare(
        'UPDATE usuarios
         SET archivado_en = CURRENT_TIMESTAMP
         WHERE id = :id AND archivado_en IS NULL'
    );
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 1) {
        $_SESSION['mensaje'] = 'Socio archivado correctamente.';
    } else {
        $_SESSION['mensaje'] = 'No se encontró el socio o ya estaba archivado.';
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['mensaje'] = 'No se pudo archivar. Probá de nuevo.';
}

header('Location: admin_usuarios.php');
exit;