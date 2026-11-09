# Definicion de la imagen base de python a utilizar
FROM python:3.13-alpine
# Instalar dependencias necesarias para la aplicación
WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
# Definicion de directorio de trabajo y copia de archivos de la aplicacion
COPY app.py .
RUN adduser -D -u 10001 app
USER app
EXPOSE 8080
# Comando para ejecutar la aplicación con Gunicorn
CMD ["gunicorn", "-b", "0.0.0.0:8080", "-w", "2", "app:app"]
