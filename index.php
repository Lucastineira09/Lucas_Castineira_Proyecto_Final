<?php
session_start();

// Los planes y los precios están en planes.php
require_once __DIR__ . '/planes.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gimnasio Huracán</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<?php require __DIR__ . '/menu.php'; ?>

<main>
    <div class="cabecera">
        <img src="img/escudo.png" alt="Escudo del Club Atlético Huracán" class="escudo">
        <h1>Gimnasio del Club Atlético Huracán</h1>
    </div>

    <!-- Foto con texto encima -->
    <div class="portada">
        <img src="img/gimnasio.jpg" alt="Sala de musculación">
        <div class="portada-texto">
            <h2>Entrená en el club</h2>
            <p>Elegí tu plan, creá tu cuenta y manejá tus datos online.</p>
            <a href="registro.php" class="boton">Quiero ser socio</a>
            <a href="login.php" class="boton">Ya soy socio</a>
        </div>
    </div>

    <!-- Beneficios (editá los textos como quieras) -->
    <h2>¿Por qué entrenar con nosotros?</h2>
    <div class="beneficios">
        <div class="beneficio">
            <h3>Sala de musculación</h3>
            <p>Máquinas y pesas para todos los niveles.</p>
        </div>
        <div class="beneficio">
            <h3>Clases grupales</h3>
            <p>Funcional, spinning y más, en distintos horarios.</p>
        </div>
        <div class="beneficio">
            <h3>Profesores a cargo</h3>
            <p>Te ayudamos a armar tu rutina.</p>
        </div>
    </div>

    <!-- Planes -->
    <h2 id="planes">Nuestros planes</h2>
    <div class="aviso">
        <strong>¿Sos socio del club?</strong> Si tenés la cuota social al día, tenés
        un <?= (int)$descuentoSocio ?>% de descuento extra en cualquier plan.
        Si solo pagás el gimnasio, se mantiene el precio de lista.
    </div>

    <div class="planes">
        <?php foreach ($planes as $plan): ?>
            <div class="plan">
                <?php if ($plan['destacado']): ?>
                    <span class="etiqueta">Más elegido</span>
                <?php endif; ?>

                <h3><?= htmlspecialchars($plan['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="chico"><?= (int)$plan['meses'] ?> <?= $plan['meses'] === 1 ? 'mes' : 'meses' ?> de acceso</p>

                <p class="chico">Solo gimnasio</p>
                <p class="precio-gym"><?= formatearPrecio($plan['precio']) ?></p>

                <p class="chico">Con cuota social</p>
                <p class="precio-socio"><?= formatearPrecio(conDescuento($plan['precio'], $descuentoSocio)) ?></p>

                <!-- Lleva a la página de pago con la cantidad de meses del plan -->
                <a href="pagar.php?plan=<?= (int)$plan['meses'] ?>" class="boton">Elegir plan</a>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<footer class="pie">
    Gimnasio del Club Atlético Huracán - Trabajo práctico final de Informática - Lucas Castiñeira
</footer>

</body>
</html>