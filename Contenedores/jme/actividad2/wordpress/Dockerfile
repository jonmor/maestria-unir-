#En construccion, todavia no ahce nada, la idea es modificar la imagen con un tema etc.
FROM wordpress:6.2.1-apache

# Plugins (los .zip se descargan de wordpress.org)
ADD https://downloads.wordpress.org/plugin/classic-editor.latest-stable.zip /tmp/
RUN apt-get update && apt-get install -y --no-install-recommends unzip \
 && unzip -q /tmp/classic-editor.latest-stable.zip -d /usr/src/wordpress/wp-content/plugins/ \
 && rm -f /tmp/*.zip && apt-get purge -y unzip && rm -rf /var/lib/apt/lists/*

# Tema propio u otro tema descargado
# COPY mi-tema/ /usr/src/wordpress/wp-content/themes/mi-tema/