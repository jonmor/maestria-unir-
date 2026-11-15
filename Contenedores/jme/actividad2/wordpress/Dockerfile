FROM wordpress:latest

# Herramientas necesarias para instalar el plugin y WP-CLI.
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl unzip \
    && rm -rf /var/lib/apt/lists/*

# Plugin Classic Editor.
ADD https://downloads.wordpress.org/plugin/classic-editor.latest-stable.zip /tmp/classic-editor.zip
RUN unzip -q /tmp/classic-editor.zip -d /usr/src/wordpress/wp-content/plugins/ \
    && rm -f /tmp/classic-editor.zip

# Aumentar límites de subida, memoria y tiempo de ejecución de PHP.
RUN { \
    echo 'upload_max_filesize = 128M'; \
    echo 'post_max_size = 128M'; \
    echo 'memory_limit = 256M'; \
    echo 'max_execution_time = 300'; \
} > /usr/local/etc/php/conf.d/custom-limits.ini

# Instalar WP-CLI para administración por consola.
RUN curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    -o /usr/local/bin/wp \
    && chmod +x /usr/local/bin/wp

# Instalar el gestor de tareas como plugin de uso obligatorio.
COPY wordpress/gestor-tareas.php /usr/src/wordpress/wp-content/mu-plugins/gestor-tareas.php

# Endpoint de diagnóstico del Pod en JSON.
RUN cat << 'PHP' > /usr/src/wordpress/pod.php
<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status' => 'ok',
    'pod_name' => gethostname(),
    'pod_ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
    'php_version' => phpversion(),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'memory_limit' => ini_get('memory_limit')
], JSON_PRETTY_PRINT);
PHP

# Reglas de Apache para que funcionen las URLs amigables de WordPress.
RUN cat << 'HTACCESS' > /usr/src/wordpress/.htaccess
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
HTACCESS

# Copiar pod.php al volumen persistente en cada inicio.
RUN cat << 'SH' > /usr/local/bin/custom-entrypoint.sh
#!/bin/bash
set -e
if [ -d /var/www/html ]; then
    cp -f /usr/src/wordpress/pod.php /var/www/html/pod.php
    chown www-data:www-data /var/www/html/pod.php
    cp -f /usr/src/wordpress/.htaccess /var/www/html/.htaccess
    chown www-data:www-data /var/www/html/.htaccess
    mkdir -p /var/www/html/wp-content/mu-plugins
    cp -f /usr/src/wordpress/wp-content/mu-plugins/gestor-tareas.php /var/www/html/wp-content/mu-plugins/gestor-tareas.php
    chown www-data:www-data /var/www/html/wp-content/mu-plugins/gestor-tareas.php
fi
exec docker-entrypoint.sh "$@"
SH
RUN chmod +x /usr/local/bin/custom-entrypoint.sh

ENTRYPOINT ["custom-entrypoint.sh"]
CMD ["apache2-foreground"]
