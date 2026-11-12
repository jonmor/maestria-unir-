#Definicion de la imagen base de php a utilizar
FROM php:8.4-cli-alpine 

# Instalar dependencias necesarias para la extensión PDO_PGSQL
RUN apk add --no-cache postgresql-libs \
    && apk add --no-cache --virtual .build postgresql-dev \
    && docker-php-ext-install pdo_pgsql \
    && apk del .build
# Definicionde directorio de trabajo y copia de archivos de la aplicacion
WORKDIR /app
COPY --chown=www-data:www-data app/ /app/
# Cambiar el usuario a www-data para ejecutar la aplicación
USER www-data
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "/app/index.php"]