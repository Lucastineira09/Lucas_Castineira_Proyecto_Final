<?php
// Barra de navegación que se repite en todas las páginas.
// Solo lee la sesión si la página ya la inició con session_start()
$logueado = session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['usuario_id']);
$esAdmin  = $logueado && ($_SESSION['usuario_rol'] ?? '') === 'admin';
?>
<nav class="menu">
    <span class="menu-titulo">Club Atlético Huracán</span>
    <div>
        <a href="index.php">Inicio</a>
        <?php if ($logueado): ?>
            <a href="panel.php">Mi panel</a>
            <?php if ($esAdmin): ?>
                <a href="admin_usuarios.php">Administración</a>
            <?php endif; ?>
            <a href="cerrar_sesion.php">Cerrar sesión</a>
        <?php else: ?>
            <a href="login.php">Ingresar</a>
            <a href="registro.php">Registrarme</a>
        <?php endif; ?>
    </div>
</nav>