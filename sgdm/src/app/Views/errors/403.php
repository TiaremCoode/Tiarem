<?php
/**
 * errors/403.php
 * Datos propios de esta página de error; el HTML compartido vive en
 * partials/error-page.php (ver ahí el porqué).
 */
$tituloPestana = 'Sin permiso';
$titulo = 'No tenés permiso para ver esto';
$texto = 'Esta sección es solo para administradores.';
require __DIR__ . '/../partials/error-page.php';
