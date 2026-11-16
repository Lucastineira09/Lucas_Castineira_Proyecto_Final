<?php
session_start();

// Solo administradores
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado.');
}

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

function escapar(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

// Mensaje de otras páginas (se muestra una sola vez)
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

try {
    // Solo usuarios archivados, los más recientes primero
    $stmt = $conexion->query(
        'SELECT id, nombre, apellido, dni, email, archivado_en
         FROM usuarios
         WHERE archivado_en IS NOT NULL
         ORDER BY archivado_en DESC'
    );
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudieron consultar los usuarios archivados.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Socios archivados - Gimnasio Huracán</title>
        <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<main>
    <h1>Gimnasio Huracán - Socios archivados</h1>
    <p>
        <a href="admin_usuarios.php">&larr; Volver a socios activos</a> |
        <a href="cerrar_sesion.php">Cerrar sesión</a>
    </p>

    <?php if ($mensaje !== ''): ?>
        <p><strong><?= escapar($mensaje) ?></strong></p>
    <?php endif; ?>

    <p><strong>Total:</strong> <?= count($usuarios) ?></p>

    <?php if (empty($usuarios)): ?>
        <p>No hay socios archivados.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <thead>
                <tr>
                    <th>ID</th><th>Nombre</th><th>Apellido</th>
                    <th>DNI</th><th>Correo</th><th>Archivado el</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= escapar((string)$u['id']) ?></td>
                    <td><?= escapar($u['nombre']) ?></td>
                    <td><?= escapar($u['apellido']) ?></td>
                    <td><?= escapar($u['dni']) ?></td>
                    <td><?= escapar($u['email']) ?></td>
                    <td><?= escapar($u['archivado_en']) ?></td>
                    <td>
                        <!-- Reactivar también va por POST con token CSRF -->
                        <form action="reactivar_usuario.php" method="POST"
                              onsubmit="return confirm('¿Reactivar a este socio?');">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCsrf()) ?>">
                            <button type="submit">Reactivar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
</body>
</html>