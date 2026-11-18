<?php
session_start();

// Solo pasa un administrador con sesión iniciada
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado.');
}

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

function escapar(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

// Mensaje que dejan otras páginas (archivar, reactivar). Se muestra una sola vez
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

// Texto del buscador (viene por GET)
$buscar = trim($_GET['buscar'] ?? '');

// Solo usuarios activos (no archivados)
$sql = 'SELECT id, nombre, apellido, dni, email, rol, creado_en
        FROM usuarios
        WHERE archivado_en IS NULL';
$parametros = [];

if ($buscar !== '') {
    $sql .= ' AND (nombre LIKE :nombre OR apellido LIKE :apellido
                   OR dni LIKE :dni OR email LIKE :email)';
    $termino = '%' . $buscar . '%';
    $parametros = [
        'nombre'   => $termino,
        'apellido' => $termino,
        'dni'      => $termino,
        'email'    => $termino,
    ];
}

$sql .= ' ORDER BY id DESC';

try {
    $stmt = $conexion->prepare($sql);
    $stmt->execute($parametros);
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudieron consultar los usuarios.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administración - Gimnasio Huracán</title>
        <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <?php require __DIR__ . '/menu.php'; ?>
<main>
    <h1>Gimnasio Huracán - Administración</h1>
    <p>
        <a href="panel.php">Mi panel</a> |
                <a href="crear_usuario.php">Nuevo socio</a> |
        <a href="usuarios_archivados.php">Socios archivados</a> |
        <a href="cerrar_sesion.php">Cerrar sesión</a>
    </p>

    <h2>Socios activos</h2>

    <?php if ($mensaje !== ''): ?>
        <p><strong><?= escapar($mensaje) ?></strong></p>
    <?php endif; ?>

    <form method="GET" action="admin_usuarios.php">
        <input type="search" name="buscar" maxlength="100"
               placeholder="Nombre, apellido, DNI o correo"
               value="<?= escapar($buscar) ?>">
        <button type="submit">Buscar</button>
    </form>

    <p><strong>Total:</strong> <?= count($usuarios) ?></p>

    <?php if (empty($usuarios)): ?>
        <p>No se encontraron usuarios.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <thead>
                <tr>
                    <th>ID</th><th>Nombre</th><th>Apellido</th>
                    <th>DNI</th><th>Correo</th><th>Rol</th><th>Alta</th><th>Acciones</th>
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
                    <td><?= escapar($u['rol']) ?></td>
                    <td><?= escapar($u['creado_en']) ?></td>
                    <td>
                        <a href="editar_usuario.php?id=<?= (int)$u['id'] ?>">Editar</a>

                        <!-- Archivar va por POST con token CSRF, nunca por un link -->
                        <form action="archivar_usuario.php" method="POST" style="display:inline"
                              onsubmit="return confirm('¿Archivar a este socio?');">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCsrf()) ?>">
                            <button type="submit">Archivar</button>
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