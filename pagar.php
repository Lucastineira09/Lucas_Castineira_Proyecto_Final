<?php
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Para pagar hay que haber iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/planes.php';

function escapar(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$esPost = $_SERVER['REQUEST_METHOD'] === 'POST';

// El plan llega como cantidad de meses (1, 3, 6 o 12)
$meses = filter_input($esPost ? INPUT_POST : INPUT_GET, 'plan', FILTER_VALIDATE_INT);

// Buscar ese plan entre los planes válidos
$planElegido = null;
foreach ($planes as $p) {
    if ($p['meses'] === $meses) {
        $planElegido = $p;
        break;
    }
}
if ($planElegido === null) {
    http_response_code(400);
    exit('Plan inválido.');
}

// Comprobar que la cuenta siga activa
$stmt = $conexion->prepare(
    'SELECT id, nombre FROM usuarios WHERE id = :id AND archivado_en IS NULL LIMIT 1'
);
$stmt->execute(['id' => $_SESSION['usuario_id']]);
$usuario = $stmt->fetch();
if ($usuario === false) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Métodos de pago permitidos (es una simulación, no se cobra nada)
$metodos = [
    'transferencia' => 'Transferencia',
    'efectivo'      => 'Efectivo en el club',
    'tarjeta'       => 'Tarjeta (simulado)',
];

$errores = [];
$metodo  = '';

if ($esPost) {
    // 1) Verificar el token CSRF
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Solicitud no válida.');
    }

    // 2) Validar el método de pago contra la lista permitida
    $metodo = $_POST['metodo'] ?? '';
    if (!array_key_exists($metodo, $metodos)) {
        $errores[] = 'Elegí un método de pago.';
    }
    $cuotaSocial = isset($_POST['cuota_social']) ? 1 : 0;

    if (empty($errores)) {
        // 3) El monto se calcula en el servidor, nunca viene del formulario
        $monto = $cuotaSocial === 1
            ? conDescuento($planElegido['precio'], $descuentoSocio)
            : $planElegido['precio'];

        try {
            // 4) Si ya tiene un plan vigente, el nuevo empieza cuando termina ese
            $stmt = $conexion->prepare('SELECT MAX(vence_el) FROM pagos WHERE usuario_id = :id');
            $stmt->execute(['id' => $usuario['id']]);
            $ultimoVencimiento = $stmt->fetchColumn();

            $vence = new DateTime('today');
            if ($ultimoVencimiento && new DateTime($ultimoVencimiento) > $vence) {
                $vence = new DateTime($ultimoVencimiento);
            }
            $vence->modify('+' . $planElegido['meses'] . ' months');

            // 5) Guardar el pago con consulta preparada
            $stmt = $conexion->prepare(
                'INSERT INTO pagos (usuario_id, plan, meses, cuota_social, monto, metodo, vence_el)
                 VALUES (:usuario_id, :plan, :meses, :cuota_social, :monto, :metodo, :vence_el)'
            );
            $stmt->execute([
                'usuario_id'   => $usuario['id'],
                'plan'         => $planElegido['nombre'],
                'meses'        => $planElegido['meses'],
                'cuota_social' => $cuotaSocial,
                'monto'        => number_format($monto, 2, '.', ''),
                'metodo'       => $metodo,
                'vence_el'     => $vence->format('Y-m-d'),
            ]);

            $_SESSION['mensaje'] = 'Pago registrado: plan ' . $planElegido['nombre']
                . ' por ' . formatearPrecio($monto)
                . '. Tu plan vence el ' . $vence->format('d/m/Y') . '.';
            header('Location: panel.php');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errores[] = 'No se pudo registrar el pago. Probá de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pagar plan - Gimnasio Huracán</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<?php require __DIR__ . '/menu.php'; ?>

<main>
    <div class="cabecera">
        <img src="img/escudo.png" alt="Escudo del Club Atlético Huracán" class="escudo">
        <h1>Pagar plan</h1>
    </div>

    <p><a href="index.php">&larr; Volver a los planes</a></p>

    <div class="aviso">
        Este pago es una <strong>simulación</strong>: no se cobra dinero real ni se piden datos de tarjeta.
    </div>

    <h2>Plan <?= escapar($planElegido['nombre']) ?> (<?= (int)$planElegido['meses'] ?>
        <?= $planElegido['meses'] === 1 ? 'mes' : 'meses' ?>)</h2>
    <ul>
        <li>Solo gimnasio: <strong><?= formatearPrecio($planElegido['precio']) ?></strong></li>
        <li>Con cuota social (<?= (int)$descuentoSocio ?>% de descuento):
            <strong><?= formatearPrecio(conDescuento($planElegido['precio'], $descuentoSocio)) ?></strong></li>
    </ul>

    <?php foreach ($errores as $error): ?>
        <p><strong><?= escapar($error) ?></strong></p>
    <?php endforeach; ?>

    <form action="pagar.php" method="POST">
        <input type="hidden" name="plan" value="<?= (int)$planElegido['meses'] ?>">
        <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCsrf()) ?>">

        <label>
            <input type="checkbox" name="cuota_social" value="1" style="width:auto">
            Soy socio del club y tengo la cuota social al día
        </label>

        <label for="metodo">Método de pago</label>
        <select id="metodo" name="metodo">
            <option value="">Elegí una opción</option>
            <?php foreach ($metodos as $clave => $nombre): ?>
                <option value="<?= escapar($clave) ?>" <?= $metodo === $clave ? 'selected' : '' ?>>
                    <?= escapar($nombre) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Confirmar pago (simulado)</button>
    </form>
</main>
</body>
</html>