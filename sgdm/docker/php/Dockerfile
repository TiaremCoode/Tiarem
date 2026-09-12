# ============================================================================
# SGDM — Contenedor de aplicación: Apache + PHP 8.2 (mod_php)
# Implementa el requerimiento técnico "Implementación con Apache" +
# "PHP para el backend", ambos dentro del mismo contenedor porque mod_php
# corre embebido en el proceso de Apache (no hace falta un contenedor
# separado de PHP-FPM para cumplir el requisito).
# ============================================================================
FROM php:8.2-apache

# Extensiones de PHP necesarias:
#   pdo, pdo_mysql -> acceso a MySQL vía PDO (usado por app/Config/Database.php)
#   mysqli         -> disponible como alternativa / herramientas de diagnóstico
#   mbstring       -> el código usa mb_strlen/mb_strtoupper/mb_substr para
#                     validar contraseñas y nombres, y para las iniciales
#                     del avatar (Usuario::iniciales). La imagen base
#                     php:8.2-apache NO trae esta extensión compilada por
#                     defecto (a diferencia de pdo, hay que pedirla
#                     explícitamente); sin esto, el registro, el alta de
#                     usuario por el admin y la creación de torneos
#                     tiraban error fatal apenas se los probaba dentro del
#                     contenedor. libonig-dev es la librería de expresiones
#                     regulares que mbstring necesita para compilar.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libonig-dev \
        git \
    && docker-php-ext-install pdo pdo_mysql mysqli mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Virtual host: DocumentRoot apuntando a /public (donde vive el front
# controller) y AllowOverride All para que el .htaccess pueda reescribir
# las rutas hacia index.php.
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# php.ini de desarrollo: errores visibles en pantalla, útil mientras se
# cursa el proyecto. Para producción conviene cambiar a php.ini-production.
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && sed -i "s/^;date.timezone =.*/date.timezone = America\/Montevideo/" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html
