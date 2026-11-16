<?php
session_start();
$_SESSION = [];      // vaciar los datos de la sesión
session_destroy();   // destruir la sesión
header('Location: login.php');
exit;