# Descripción de `03-wordpress-deployment.yaml`

Este manifiesto define tres recursos separados por `---` para desplegar el frontend de la aplicación WordPress en alta disponibilidad:
1. Un **PersistentVolumeClaim (PVC)** para persistir los archivos web (`/var/www/html`).
2. Un **Service** tipo `NodePort` para exponer WordPress hacia el exterior del clúster.
3. Un **Deployment** con 2 réplicas de la imagen personalizada de WordPress y reglas de antiafinidad.

---

## 1. Recurso `PersistentVolumeClaim` (`wordpress-pvc`)

```yaml
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: wordpress-pvc
  labels:
    app: wordpress
spec:
  accessModes:
    - ReadWriteOnce
  resources:
    requests:
      storage: 2Gi
```

### Elementos utilizados:
- **`apiVersion: v1`**: Versión principal de la API de Kubernetes.
- **`kind: PersistentVolumeClaim`**: Sirve para solicitar un volumen de almacenamiento persistente donde se guardarán los archivos de instalación de WordPress, plugins, temas y archivos multimedia subidos (`uploads`).
- **`metadata.name: wordpress-pvc`**: Nombre con el que el `Deployment` de WordPress montará este volumen.
- **`spec.accessModes: - ReadWriteOnce`**: Permite montar el volumen en lectura y escritura por un nodo.
- **`spec.resources.requests.storage: 2Gi`**: Solicita 2 Gibibytes de espacio en disco para el contenido estático y código PHP de WordPress.

---

## 2. Recurso `Service` (`wordpress`)

```yaml
apiVersion: v1
kind: Service
metadata:
  name: wordpress
  labels:
    app: wordpress
spec:
  type: NodePort
  ports:
    - port: 80
      targetPort: 80
      nodePort: 30080
  selector:
    app: wordpress
    tier: frontend
```

### Elementos utilizados:
- **`kind: Service`**: Sirve para exponer los Pods de WordPress y balancear el tráfico HTTP entre todas las réplicas activas.
- **`spec.type: NodePort`**: Expone el servicio fuera del clúster abriendo un puerto estático en la dirección IP de cada nodo (por ejemplo, en la VM o contenedor de Minikube).
- **`spec.ports:`**:
  - **`port: 80`**: Puerto interno del servicio dentro del clúster.
  - **`targetPort: 80`**: Puerto en el que escuchan los contenedores de WordPress (Apache).
  - **`nodePort: 30080`**: Puerto externo fijo (dentro del rango permitido por Kubernetes `30000-32767`) mediante el cual se puede acceder desde fuera del clúster.
- **`spec.selector:` (`app: wordpress`, `tier: frontend`)**: Indica que este servicio balanceará las peticiones únicamente entre los Pods del frontend de WordPress.

---

## 3. Recurso `Deployment` (`wordpress`)

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: wordpress
  labels:
    app: wordpress
spec:
  replicas: 2
  strategy:
    type: RollingUpdate
  selector:
    matchLabels:
      app: wordpress
      tier: frontend
  template:
    metadata:
      labels:
        app: wordpress
        tier: frontend
    spec:
      affinity:
        podAntiAffinity:
          preferredDuringSchedulingIgnoredDuringExecution:
          - weight: 100
            podAffinityTerm:
              labelSelector:
                matchLabels:
                  app: wordpress
                  tier: mysql
              topologyKey: kubernetes.io/hostname
      containers:
      - image: wordpress-unir-a2:latest
        imagePullPolicy: IfNotPresent
        name: wordpress
        env:
        - name: WORDPRESS_DB_HOST
          value: wordpress-mysql
        - name: WORDPRESS_DB_PASSWORD
          valueFrom:
            secretKeyRef:
              name: mysql-pass
              key: password
        - name: WORDPRESS_DB_USER
          value: wordpress
        - name: WORDPRESS_AUTH_KEY
          valueFrom: { secretKeyRef: { name: mysql-pass, key: auth-key } }
        # ... (resto de claves y salts desde mysql-pass)
        - name: WORDPRESS_CONFIG_EXTRA
          value: |
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false) {
                $_SERVER['HTTPS'] = 'on';
            }
            // define('WP_HOME', 'https://wordpressunir.tsolbe.com');
            // define('WP_SITEURL', 'https://wordpressunir.tsolbe.com');
        ports:
        - containerPort: 80
          name: wordpress
        volumeMounts:
        - name: wordpress-persistent-storage
          mountPath: /var/www/html
      volumes:
      - name: wordpress-persistent-storage
        persistentVolumeClaim:
          claimName: wordpress-pvc
```

### Elementos utilizados:
- **`kind: Deployment`**: Controlador encargado de mantener en ejecución las instancias de WordPress.
- **`spec.replicas: 2`**: Indica a Kubernetes que debe mantener **2 Pods idénticos** de WordPress ejecutándose simultáneamente para distribuir la carga y brindar tolerancia a fallos.
- **`spec.strategy.type: RollingUpdate`**: Estrategia de despliegue continuo (actualización gradual). Al actualizar el Deployment, Kubernetes reemplaza los Pods uno por uno sin dejar el servicio fuera de línea.
- **`spec.template.spec.affinity.podAntiAffinity`**:
  - Sirve para definir reglas de ubicación de Pods entre distintos nodos.
  - **`preferredDuringSchedulingIgnoredDuringExecution`**: Es una regla *suave* (preferencia): el planificador (*scheduler*) intentará cumplirla, pero si no hay otro nodo disponible, programará el Pod de todos modos.
  - **`weight: 100`**: Peso máximo de preferencia (de 1 a 100).
  - **`podAffinityTerm.labelSelector`** (`app: wordpress`, `tier: mysql`) y **`topologyKey: kubernetes.io/hostname`**: Indica que Kubernetes debe preferir colocar los Pods de WordPress en un nodo físico/virtual (`hostname`) **distinto** a aquel donde corre el Pod de MySQL, distribuyendo mejor el consumo de recursos entre nodos.
- **`spec.template.spec.containers`**:
  - **`image: wordpress-unir-a2:latest`**: Imagen personalizada de WordPress creada para la Actividad 2 (que incluye el plugin `gestor-tareas.php`).
  - **`imagePullPolicy: IfNotPresent`**: Sirve para indicar a Kubernetes que use la imagen local del nodo si ya existe y no intente descargarla de Docker Hub, indispensable cuando se usan imágenes construidas localmente.
  - **`env:`**:
    - **`WORDPRESS_DB_HOST: wordpress-mysql`**: Apunta al nombre DNS del `Service` de MySQL creado en `02-mysql-deployment.yaml`.
    - **`WORDPRESS_DB_USER` / `WORDPRESS_DB_PASSWORD`**: Usuario y contraseña (obtenida del Secret `mysql-pass`) para conectarse a MySQL.
    - **`WORDPRESS_AUTH_KEY`, `WORDPRESS_SECURE_AUTH_KEY`, `WORDPRESS_LOGGED_IN_KEY`, `WORDPRESS_NONCE_KEY` y sus respectivos `*_SALT`**: Leen del Secret `mysql-pass` las llaves de cifrado de cookies de sesión para que ambas réplicas compartan exactamente la misma firma de sesión y no cierren la sesión del usuario al balancear tráfico.
    - **`WORDPRESS_CONFIG_EXTRA`**: Inyecta líneas de código PHP adicionales dentro de `wp-config.php`. Aquí se usa para detectar la cabecera `HTTP_X_FORWARDED_PROTO` cuando WordPress está detrás de un proxy inverso con HTTPS, evitando bucles de redirección infinitos.
  - **`ports: - containerPort: 80`**: Expone el puerto 80 del servidor web Apache interno del contenedor.
  - **`volumeMounts` y `volumes`**: Monta el PVC `wordpress-pvc` en la ruta `/var/www/html` del contenedor, donde reside todo el código fuente de WordPress.
