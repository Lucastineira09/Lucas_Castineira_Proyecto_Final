<?php
session_start();
require_once __DIR__ . '/conexion.php';

function escapar(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Completá el correo y la contraseña.';
    } else {
        // Buscar al usuario activo (no archivado) por correo
        $stmt = $conexion->prepare(
            'SELECT id, nombre, password_hash, rol
             FROM usuarios
             WHERE email = :email AND archivado_en IS NULL
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        // Comparar la contraseña escrita con el hash guardado
        if ($usuario !== false && password_verify($password, $usuario['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id']  = $usuario['id'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
                        if ($usuario['rol'] === 'admin') {
                header('Location: admin_usuarios.php');
            } else {
                header('Location: panel.php');
            }
            exit;
        }
        // Mismo mensaje si falla el correo o la contraseña
        $error = 'Correo o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar - Gimnasio Huracán</title>
        <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<main>
    <div class="cabecera">
        <img src="img/escudo.png" alt="Escudo del Club Atlético Huracán" class="escudo">
        <h1>Gimnasio Huracán</h1>
    </div>    <h2>Iniciar sesión</h2>

    <?php if ($error !== ''): ?>
        <p><strong><?= escapar($error) ?></strong></p>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" value="<?= escapar($email) ?>" required>

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Ingresar</button>
    </form>

    <p>¿No tenés cuenta? <a href="registro.php">Registrate</a></p>
</main>
</body>
</html>