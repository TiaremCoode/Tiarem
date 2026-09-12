<?php
/**
 * errors/404.php
 * Datos propios de esta página de error; el HTML compartido vive en
 * partials/error-page.php (ver ahí el porqué).
 */
$tituloPestana = 'No encontrado';
$titulo = 'No encontramos esta página';
$texto = 'Revisá el enlace o volvé al inicio.';
require __DIR__ . '/../partials/error-page.php';
