# Descripción de `02-mysql-deployment.yaml`

Este manifiesto define tres recursos separados por `---` necesarios para desplegar la capa de base de datos MySQL:
1. Un **Service** (*Headless*) para la comunicación interna por DNS.
2. Un **PersistentVolumeClaim (PVC)** para el almacenamiento persistente de los datos.
3. Un **Deployment** que ejecuta el contenedor de `mysql:8.0`.

---

## 1. Recurso `Service` (`wordpress-mysql`)

```yaml
apiVersion: v1
kind: Service
metadata:
  name: wordpress-mysql
  labels:
    app: wordpress
spec:
  ports:
    - port: 3306
  selector:
    app: wordpress
    tier: mysql
  clusterIP: None
```

### Elementos utilizados:
- **`apiVersion: v1`**: Indica el uso de la API principal (*core* `v1`) de Kubernetes.
- **`kind: Service`**: Sirve para exponer una aplicación que corre en uno o más Pods como un servicio de red estable dentro del clúster.
- **`metadata:`**:
  - **`name: wordpress-mysql`**: Nombre del servicio; Kubernetes registra este nombre en el DNS interno del clúster, permitiendo que WordPress se conecte a la base de datos usando simplemente el host `wordpress-mysql`.
  - **`labels: app: wordpress`**: Etiqueta para asociar este servicio con la aplicación general `wordpress`.
- **`spec:`**: Define la configuración deseada del servicio:
  - **`ports: - port: 3306`**: Abre y expone el puerto TCP `3306` (puerto estándar de MySQL).
  - **`selector:` (`app: wordpress`, `tier: mysql`)**: Sirve para indicar a qué Pods debe dirigir el tráfico este Service (aquellos que tengan ambas etiquetas).
  - **`clusterIP: None`**: Convierte el servicio en un **Headless Service** (servicio sin IP virtual de balanceo). Sirve para que el DNS de Kubernetes devuelva directamente la IP del Pod de MySQL en lugar de pasar por un proxy de balanceo de carga, ideal para bases de datos de instancia única.

---

## 2. Recurso `PersistentVolumeClaim` (`mysql-pv-claim`)

```yaml
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: mysql-pv-claim
  labels:
    app: wordpress
spec:
  storageClassName: csi-hostpath-sc
  accessModes:
    - ReadWriteOnce
  resources:
    requests:
      storage: 20Gi
```

### Elementos utilizados:
- **`kind: PersistentVolumeClaim`**: Sirve para solicitar almacenamiento persistente al clúster. Garantiza que, aunque el Pod de MySQL se reinicie o se elimine, las tablas y registros de la base de datos no se pierdan.
- **`metadata.name: mysql-pv-claim`**: Nombre identificador de la solicitud de volumen con el que el `Deployment` lo montará más abajo.
- **`spec:`**:
  - **`storageClassName: csi-hostpath-sc`**: Especifica la clase de almacenamiento dinámico a utilizar (en este caso, el driver CSI HostPath habilitado en Minikube).
  - **`accessModes: - ReadWriteOnce`**: Define el modo de acceso al disco. `ReadWriteOnce` (RWO) indica que el volumen puede ser montado en modo lectura/escritura por un único nodo a la vez.
  - **`resources.requests.storage: 20Gi`**: Solicita una capacidad de almacenamiento de 20 Gibibytes para la base de datos.

---

## 3. Recurso `Deployment` (`wordpress-mysql`)

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: wordpress-mysql
  labels:
    app: wordpress
spec:
  selector:
    matchLabels:
      app: wordpress
      tier: mysql
  strategy:
    type: Recreate
  template:
    metadata:
      labels:
        app: wordpress
        tier: mysql
    spec:
      containers:
      - image: mysql:8.0
        name: mysql
        env:
        - name: MYSQL_ROOT_PASSWORD
          valueFrom:
            secretKeyRef:
              name: mysql-pass
              key: password
        - name: MYSQL_DATABASE
          value: wordpress
        - name: MYSQL_USER
          value: wordpress
        - name: MYSQL_PASSWORD
          valueFrom:
            secretKeyRef:
              name: mysql-pass
              key: password
        ports:
        - containerPort: 3306
          name: mysql
        volumeMounts:
        - name: mysql-persistent-storage
          mountPath: /var/lib/mysql
      volumes:
      - name: mysql-persistent-storage
        persistentVolumeClaim:
          claimName: mysql-pv-claim
```

### Elementos utilizados:
- **`apiVersion: apps/v1`**: Grupo de API de Kubernetes donde residen los controladores de cargas de trabajo como `Deployment`, `StatefulSet` y `DaemonSet`.
- **`kind: Deployment`**: Sirve para gestionar el ciclo de vida de los Pods, asegurando que el número deseado de instancias esté siempre en ejecución y reemplazándolas automáticamente si fallan.
- **`spec.selector.matchLabels`**: Define cómo el `Deployment` identifica los Pods que están bajo su control (`app: wordpress` y `tier: mysql`).
- **`spec.strategy.type: Recreate`**: Define la estrategia de actualización. `Recreate` sirve para terminar por completo el Pod existente antes de crear uno nuevo. Es fundamental en MySQL porque evita que dos Pods intenten escribir simultáneamente en el mismo volumen de datos (`/var/lib/mysql`).
- **`spec.template`**: Plantilla que define cómo se creará el Pod:
  - **`metadata.labels`**: Etiquetas asignadas al Pod (`app: wordpress`, `tier: mysql`), que deben coincidir con el `selector` del `Deployment` y del `Service`.
  - **`spec.containers`**: Lista de contenedores dentro del Pod:
    - **`image: mysql:8.0`**: Imagen oficial de Docker para MySQL versión 8.0.
    - **`name: mysql`**: Nombre interno del contenedor dentro del Pod.
    - **`env:`**: Variables de entorno inyectadas al contenedor al iniciar:
      - **`MYSQL_ROOT_PASSWORD`** y **`MYSQL_PASSWORD`**: Usan `valueFrom.secretKeyRef` para leer de forma segura la clave `password` almacenada en el Secret `mysql-pass`.
      - **`MYSQL_DATABASE: wordpress`**: Indica a la imagen de MySQL que cree automáticamente una base de datos llamada `wordpress` al arrancar por primera vez.
      - **`MYSQL_USER: wordpress`**: Crea automáticamente el usuario `wordpress` con permisos sobre dicha base de datos.
    - **`ports: - containerPort: 3306`**: Declara que el contenedor escucha en el puerto `3306`.
    - **`volumeMounts:`**: Conecta el volumen declarado en el Pod dentro del sistema de archivos del contenedor:
      - **`name: mysql-persistent-storage`**: Nombre de referencia del volumen interno.
      - **`mountPath: /var/lib/mysql`**: Ruta dentro del contenedor donde MySQL guarda físicamente sus archivos de base de datos.
  - **`spec.volumes:`**: Declara los volúmenes disponibles para el Pod:
    - **`persistentVolumeClaim.claimName: mysql-pv-claim`**: Vincula el volumen del Pod con el PVC `mysql-pv-claim` creado en este mismo archivo.
