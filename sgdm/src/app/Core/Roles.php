<?php
/**
 * Roles.php
 * Códigos de rol tal como están cargados en la tabla `roles` por
 * db/03_seed.sql. Se usan por código (no por ID numérico) en todo el
 * código PHP para que el orden en que se insertaron los roles en la base
 * no afecte al comportamiento del sistema.
 */
final class Roles
{
    public const ADMIN_GENERAL = 'admin_general';
    public const ORGANIZADOR   = 'organizador';
    public const PARTICIPANTE  = 'participante';
}
