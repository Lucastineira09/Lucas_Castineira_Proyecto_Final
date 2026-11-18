<?php
// Configuración de los planes. Se usa en index.php y pagar.php
// para tener los precios en un solo lugar

// Cada plan: nombre, meses de acceso y precio para quien paga solo el gimnasio
$planes = [
    ['nombre' => 'Mensual',    'meses' => 1,  'precio' => 25000,  'destacado' => false],
    ['nombre' => 'Trimestral', 'meses' => 3,  'precio' => 65000,  'destacado' => true],
    ['nombre' => 'Semestral',  'meses' => 6,  'precio' => 120000, 'destacado' => false],
    ['nombre' => 'Anual',      'meses' => 12, 'precio' => 210000, 'destacado' => false],
];

// Porcentaje de descuento extra para los socios que pagan la cuota social del club
$descuentoSocio = 15;

// Da formato a un precio: 25000 -> $25.000
function formatearPrecio(float $valor): string {
    return '$' . number_format($valor, 0, ',', '.');
}

// Calcula el precio con el descuento aplicado
function conDescuento(int $precio, int $porcentaje): float {
    return $precio - ($precio * $porcentaje / 100);
}