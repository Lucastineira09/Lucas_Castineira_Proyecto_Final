<?php
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Si no inició sesión, lo mandamos al login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/conexion.php';

function escapar(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

// Mensaje que deja otra página (por ejemplo, el pago). Se muestra una sola vez
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

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

// Historial de pagos, el más reciente primero
$stmt = $conexion->prepare(
    'SELECT plan, meses, monto, cuota_social, metodo, fecha_pago, vence_el
     FROM pagos
     WHERE usuario_id = :id
     ORDER BY id DESC'
);
$stmt->execute(['id' => $_SESSION['usuario_id']]);
$pagos = $stmt->fetchAll();

// El plan está vigente si la fecha de vencimiento más lejana es hoy o futura
$vence   = $pagos ? max(array_column($pagos, 'vence_el')) : null;
$vigente = $vence !== null && $vence >= date('Y-m-d');
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

<?php require __DIR__ . '/menu.php'; ?>

<main>
    <h1>Bienvenido, <?= escapar($usuario['nombre']) ?></h1>

    <?php if ($mensaje !== ''): ?>
        <p><strong><?= escapar($mensaje) ?></strong></p>
    <?php endif; ?>

    <h2>Mis datos</h2>
    <ul>
        <li><strong>Nombre:</strong> <?= escapar($usuario['nombre']) ?></li>
        <li><strong>Apellido:</strong> <?= escapar($usuario['apellido']) ?></li>
        <li><strong>DNI:</strong> <?= escapar($usuario['dni']) ?></li>
        <li><strong>Correo:</strong> <?= escapar($usuario['email']) ?></li>
        <li><strong>Rol:</strong> <?= escapar($usuario['rol']) ?></li>
        <li><strong>Socio desde:</strong> <?= escapar($usuario['creado_en']) ?></li>
    </ul>

    <h2>Mi plan</h2>
    <?php if ($vigente): ?>
        <p>Plan vigente hasta el <strong><?= escapar(date('d/m/Y', strtotime($vence))) ?></strong>.</p>
    <?php elseif ($vence !== null): ?>
        <p>Tu plan venció el <strong><?= escapar(date('d/m/Y', strtotime($vence))) ?></strong>.</p>
    <?php else: ?>
        <p>Todavía no tenés ningún plan contratado.</p>
    <?php endif; ?>
    <p><a href="index.php#planes" class="boton">Contratar o renovar plan</a></p>
    <?php if (!empty($pagos)): ?>
        <h2>Mis pagos</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th><th>Plan</th><th>Cuota social</th>
                    <th>Monto</th><th>Método</th><th>Vence</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pagos as $p): ?>
                <tr>
                    <td><?= escapar(date('d/m/Y', strtotime($p['fecha_pago']))) ?></td>
                    <td><?= escapar($p['plan']) ?></td>
                    <td><?= $p['cuota_social'] ? 'Sí' : 'No' ?></td>
                    <td>$<?= escapar(number_format((float)$p['monto'], 0, ',', '.')) ?></td>
                    <td><?= escapar($p['metodo']) ?></td>
                    <td><?= escapar(date('d/m/Y', strtotime($p['vence_el']))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
</body>
</html>