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

$errores = [];
$exito   = false;
$nombre = $apellido = $dni = $email = '';
$rol = 'usuario';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Verificar el token CSRF
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Solicitud no válida.');
    }

    // 2) Recibir y limpiar datos
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellido  = trim($_POST['apellido'] ?? '');
    $dni       = trim($_POST['dni'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';
    $rol       = $_POST['rol'] ?? 'usuario';

    // 3) Validar. El rol solo puede ser uno de los dos valores permitidos
    if (!in_array($rol, ['usuario', 'admin'], true)) {
        $rol = 'usuario';
    }
    if ($nombre === '' || $apellido === '' || $dni === '' || $email === '' || $password === '') {
        $errores[] = 'Completá todos los campos.';
    }
    if ($dni !== '' && !preg_match('/^[0-9]{7,8}$/', $dni)) {
        $errores[] = 'El DNI debe tener 7 u 8 números, sin puntos.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo no es válido.';
    }
    if ($password !== '' && strlen($password) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if ($password !== $confirmar) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    // 4) Evitar duplicados de DNI y correo
    if (empty($errores)) {
        $stmt = $conexion->prepare(
            'SELECT COUNT(*) FROM usuarios WHERE dni = :dni OR email = :email'
        );
        $stmt->execute(['dni' => $dni, 'email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = 'Ya existe un usuario con ese DNI o ese correo.';
        }
    }

    // 5) Guardar con la contraseña hasheada
    if (empty($errores)) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare(
                'INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol)
                 VALUES (:nombre, :apellido, :dni, :email, :hash, :rol)'
            );
            $stmt->execute([
                'nombre'   => $nombre,
                'apellido' => $apellido,
                'dni'      => $dni,
                'email'    => $email,
                'hash'     => $hash,
                'rol'      => $rol,
            ]);
            $exito = true;
            $nombre = $apellido = $dni = $email = '';
            $rol = 'usuario';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errores[] = 'No se pudo crear el usuario. Probá de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo socio - Gimnasio Huracán</title>
        <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <?php require __DIR__ . '/menu.php'; ?>
<main>
    <h1>Nuevo socio</h1>
    <p><a href="admin_usuarios.php">&larr; Volver al listado</a></p>

    <?php if ($exito): ?>
        <p>Usuario creado correctamente.</p>
    <?php endif; ?>

    <?php foreach ($errores as $error): ?>
        <p><strong><?= escapar($error) ?></strong></p>
    <?php endforeach; ?>

    <form action="crear_usuario.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCsrf()) ?>">

        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" maxlength="80"
               value="<?= escapar($nombre) ?>" required>

        <label for="apellido">Apellido</label>
        <input type="text" id="apellido" name="apellido" maxlength="80"
               value="<?= escapar($apellido) ?>" required>

        <label for="dni">DNI</label>
        <input type="text" id="dni" name="dni" maxlength="8"
               value="<?= escapar($dni) ?>" required>

        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" maxlength="254"
               value="<?= escapar($email) ?>" required>

        <label for="rol">Rol</label>
        <select id="rol" name="rol">
            <option value="usuario" <?= $rol === 'usuario' ? 'selected' : '' ?>>Socio</option>
            <option value="admin" <?= $rol === 'admin' ? 'selected' : '' ?>>Administrador</option>
        </select>

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>

        <label for="confirmar">Repetir contraseña</label>
        <input type="password" id="confirmar" name="confirmar" required>

        <button type="submit">Crear usuario</button>
    </form>
</main>
</body>
</html>