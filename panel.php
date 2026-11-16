<?php
session_start();

// Si no inició sesión, lo mandamos al login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/conexion.php';

function escapar(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

// Traer los datos del usuario logueado (sin la contraseña)
$stmt = $conexion->prepare(
    'SELECT nombre, apellido, dni, email, rol, creado_en
     FROM usuarios
     WHERE id = :id AND archivado_en IS NULL
     LIMIT 1'
);
$stmt->execute(['id' => $_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

// Si la cuenta fue archivada o ya no existe, cerramos la sesión
if ($usuario === false) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi panel - Gimnasio Huracán</title>
        <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<main>
    <h1>Bienvenido, <?= escapar($usuario['nombre']) ?></h1>

    <h2>Mis datos</h2>
    <ul>
        <li><strong>Nombre:</strong> <?= escapar($usuario['nombre']) ?></li>
        <li><strong>Apellido:</strong> <?= escapar($usuario['apellido']) ?></li>
        <li><strong>DNI:</strong> <?= escapar($usuario['dni']) ?></li>
        <li><strong>Correo:</strong> <?= escapar($usuario['email']) ?></li>
        <li><strong>Rol:</strong> <?= escapar($usuario['rol']) ?></li>
        <li><strong>Socio desde:</strong> <?= escapar($usuario['creado_en']) ?></li>
    </ul>

    <p><a href="cerrar_sesion.php">Cerrar sesión</a></p>
</main>
</body>
</html>