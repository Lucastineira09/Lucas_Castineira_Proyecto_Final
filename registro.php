<?php
require_once __DIR__ . '/conexion.php';

// Evita que un dato se interprete como código HTML
function escapar(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$errores  = [];
$exito    = false;
$nombre = $apellido = $dni = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Recibir y limpiar los datos
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellido  = trim($_POST['apellido'] ?? '');
    $dni       = trim($_POST['dni'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    // 2) Validar en el servidor
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

    // 3) Evitar duplicados de DNI y correo
    if (empty($errores)) {
        $stmt = $conexion->prepare(
            'SELECT COUNT(*) FROM usuarios WHERE dni = :dni OR email = :email'
        );
        $stmt->execute(['dni' => $dni, 'email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = 'Ya existe una cuenta con ese DNI o ese correo.';
        }
    }

    // 4) Guardar en la base con la contraseña hasheada
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
                'rol'      => 'usuario'   // siempre socio común
            ]);
            $exito = true;
            $nombre = $apellido = $dni = $email = '';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errores[] = 'No se pudo crear la cuenta. Probá de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro - Gimnasio Huracán</title>
        <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <?php require __DIR__ . '/menu.php'; ?>
<main>
    <div class="cabecera">
        <img src="img/escudo.png" alt="Escudo del Club Atlético Huracán" class="escudo">
        <h1>Gimnasio Huracán</h1>
    </div>    <h2>Crear cuenta</h2>

    <?php if ($exito): ?>
        <p>¡Cuenta creada con éxito! Ya podés iniciar sesión.</p>
    <?php endif; ?>

    <?php foreach ($errores as $error): ?>
        <p><strong><?= escapar($error) ?></strong></p>
    <?php endforeach; ?>

    <form action="registro.php" method="POST">
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

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>

        <label for="confirmar">Repetir contraseña</label>
        <input type="password" id="confirmar" name="confirmar" required>

        <button type="submit">Crear cuenta</button>
    </form>
</main>
</body>
</html>