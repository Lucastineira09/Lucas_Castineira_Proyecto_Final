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

// El id viene por GET al abrir el formulario y por POST al guardarlo
$esPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$id = filter_input($esPost ? INPUT_POST : INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('Identificador inválido.');
}

// Buscar al socio (solo activos)
$stmt = $conexion->prepare(
    'SELECT id, nombre, apellido, dni, email
     FROM usuarios
     WHERE id = :id AND archivado_en IS NULL
     LIMIT 1'
);
$stmt->execute(['id' => $id]);
$usuario = $stmt->fetch();

if ($usuario === false) {
    http_response_code(404);
    exit('Usuario no encontrado o archivado.');
}

$errores = [];
$exito   = false;

if ($esPost) {
    // 1) Verificar el token CSRF
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Solicitud no válida.');
    }

    // 2) Recibir y limpiar datos (el rol NO se puede cambiar desde acá)
    $usuario['nombre']   = trim($_POST['nombre'] ?? '');
    $usuario['apellido'] = trim($_POST['apellido'] ?? '');
    $usuario['dni']      = trim($_POST['dni'] ?? '');
    $usuario['email']    = trim($_POST['email'] ?? '');

    // 3) Validar
    if ($usuario['nombre'] === '' || $usuario['apellido'] === ''
        || $usuario['dni'] === '' || $usuario['email'] === '') {
        $errores[] = 'Completá todos los campos.';
    }
    if ($usuario['dni'] !== '' && !preg_match('/^[0-9]{7,8}$/', $usuario['dni'])) {
        $errores[] = 'El DNI debe tener 7 u 8 números, sin puntos.';
    }
    if ($usuario['email'] !== '' && !filter_var($usuario['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo no es válido.';
    }

    // 4) Duplicados (ignorando al propio usuario)
    if (empty($errores)) {
        $stmt = $conexion->prepare(
            'SELECT COUNT(*) FROM usuarios
             WHERE (dni = :dni OR email = :email) AND id <> :id'
        );
        $stmt->execute([
            'dni'   => $usuario['dni'],
            'email' => $usuario['email'],
            'id'    => $id,
        ]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = 'Ya existe otro socio con ese DNI o ese correo.';
        }
    }

    // 5) Guardar
    if (empty($errores)) {
        try {
            $stmt = $conexion->prepare(
                'UPDATE usuarios
                 SET nombre = :nombre, apellido = :apellido,
                     dni = :dni, email = :email
                 WHERE id = :id AND archivado_en IS NULL'
            );
            $stmt->execute([
                'nombre'   => $usuario['nombre'],
                'apellido' => $usuario['apellido'],
                'dni'      => $usuario['dni'],
                'email'    => $usuario['email'],
                'id'       => $id,
            ]);
            $exito = true;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errores[] = 'No se pudo guardar. Probá de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar socio - Gimnasio Huracán</title>
        <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<main>
    <h1>Editar socio</h1>
    <p><a href="admin_usuarios.php">&larr; Volver al listado</a></p>

    <?php if ($exito): ?>
        <p>Datos actualizados correctamente.</p>
    <?php endif; ?>

    <?php foreach ($errores as $error): ?>
        <p><strong><?= escapar($error) ?></strong></p>
    <?php endforeach; ?>

    <form action="editar_usuario.php" method="POST">
        <input type="hidden" name="id" value="<?= (int)$usuario['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCsrf()) ?>">

        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" maxlength="80"
               value="<?= escapar($usuario['nombre']) ?>" required>

        <label for="apellido">Apellido</label>
        <input type="text" id="apellido" name="apellido" maxlength="80"
               value="<?= escapar($usuario['apellido']) ?>" required>

        <label for="dni">DNI</label>
        <input type="text" id="dni" name="dni" maxlength="8"
               value="<?= escapar($usuario['dni']) ?>" required>

        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" maxlength="254"
               value="<?= escapar($usuario['email']) ?>" required>

        <button type="submit">Guardar cambios</button>
    </form>
</main>
</body>
</html>