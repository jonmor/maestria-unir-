# Actividad1

Para la practica se genero una app basica TODO API en 4 lenguajes, mismo contrato, misma base Postgres.

| Servicio | Imagen runtime                | Puerto host |
|----------|-------------------------------|-------------|
| python   | python:3.13-alpine            | 8081        |
| node     | node:22-alpine                | 8082        |
| java     | eclipse-temurin:21-jre-alpine | 8083        |
| php      | php:8.4-cli-alpine            | 8084        |
| db       | postgres:17-alpine            | 5432        |

## Contrato (igual en los 4)

- `GET /todos` → lista de todos (de **todos** los servicios; la columna `lang` dice quién lo creó)
- `POST /todos` con `{"title": "..."}` → 201 + el todo creado; 400 si falta `title`


## Levantar con docker compose con build para generar las imagenes

En este primer build, se ejecuta el archivo "docker-compose-creacion-imagen.yml" que contiene las configuraciones para hacer el build de las aplicaciones

```bash
docker compose -f docker-compose-creacion-imagen.yml up --build -d
docker compose logs -f
```

## Generar tag de imagenes

```bash
docker tag actividad1-java:latest   jonme25/unir-act1:java-0.1
docker tag actividad1-node:latest   jonme25/unir-act1:node-0.1
docker tag actividad1-php:latest    jonme25/unir-act1:php-0.1
docker tag actividad1-python:latest jonme25/unir-act1:python-0.1
```

## Generar push de imagenes a repositorio jonme25/unir-act1

```bash
docker push jonme25/unir-act1:java-0.1
docker push jonme25/unir-act1:node-0.1
docker push jonme25/unir-act1:php-0.1
docker push jonme25/unir-act1:python-0.1
```

## Levantar con docker compose

En esta opcion toma el archivo "docker-compose.yml" que ya cuenta con la configuracion para utilizar las imagenes generadas en el repo de docker hub

```bash
docker compose up --build -d
docker compose logs -f
```

## Detener con docker compose

```bash
docker compose down
```

## Probar

```bash
for p in 8081 8082 8083 8084; do
  curl -s -X POST localhost:$p/todos -H 'Content-Type: application/json' -d "{\"title\":\"tarea desde $p\"}"
  echo
done
curl -s localhost:8081/todos | jq
curl -s localhost:8082/todos | jq

```

## Notas

- `init.sql` solo se ejecuta cuando el volumen `pgdata` está vacío. para revisar si existe volumen `docker volume ls`
- Credenciales en claro en el compose: todo se usa con valor de "todo" en socker-compose.yml
- Para la practica se creo un repositorio publico: https://hub.docker.com/r/jonme25/unir-act1 
- **IMPORTANTE (Se debe eliminar despues de entrega y calificación de la practica)**
