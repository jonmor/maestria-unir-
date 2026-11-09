# Definicion de la imagen base de node a utilizar
FROM node:22-alpine
# Instalar dependencias necesarias para la aplicación
WORKDIR /app
COPY package.json .
# Instalar dependencias de producción
RUN npm install --omit=dev
# Definicion de directorio de trabajo y copia de archivos de la aplicacion
COPY server.js .
USER node
EXPOSE 8080
# Comando para ejecutar la aplicación con Node.js
CMD ["node", "server.js"]
