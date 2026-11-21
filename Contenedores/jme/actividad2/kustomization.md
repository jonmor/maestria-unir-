# Descripción de `kustomization.yaml`

Este archivo configura **Kustomize**, una herramienta nativa integrada en `kubectl` (mediante `kubectl apply -k ./` y `kubectl delete -k ./`) que permite agrupar, personalizar y desplegar todos los manifiestos de la Actividad 2 en una sola operación y bajo un mismo espacio de nombres.

---

## Código del manifiesto

```yaml
apiVersion: kustomize.config.k8s.io/v1beta1
kind: Kustomization

namespace: ns-act2-group

resources:
  - manifest/00-secret.yaml
  - manifest/01-createNameSpace.yaml
  - manifest/02-mysql-deployment.yaml
  - manifest/03-wordpress-deployment.yaml
```

---

## Descripción de los elementos utilizados

- **`apiVersion: kustomize.config.k8s.io/v1beta1`**
  Sirve para especificar el grupo y la versión del esquema de configuración que utiliza Kustomize para interpretar este archivo.

https://kubectl.docs.kubernetes.io/references/kustomize/kustomization/

- **`kind: Kustomization`**
  Sirve para declarar que este archivo no es un recurso directo del clúster, sino un manifiesto de orquestación y empaquetado de **Kustomize**. Le indica a `kubectl -k` cómo debe ensamblar y transformar los archivos YAML listados antes de enviarlos a la API de Kubernetes.

- **`namespace: ns-act2-group`**
  Sirve para inyectar automáticamente el campo `metadata.namespace: ns-act2-group` a **todos los recursos** incluidos en la lista `resources` (el `Secret`, los `Services`, los `PersistentVolumeClaims` y los `Deployments`).
  - Gracias a esta directiva, no fue necesario escribir `namespace: ns-act2-group` manualmente dentro de cada uno de los archivos individuales en la carpeta `manifest/`.

- **`resources:`**
  Sirve para definir la lista ordenada de archivos de manifiesto YAML que Kustomize debe leer, procesar y aplicar en el clúster cuando se ejecuta `kubectl apply -k ./`:
  - **`manifest/00-secret.yaml`**: Crea primero el secreto `mysql-pass` con las contraseñas y claves *salt*.
  - **`manifest/01-createNameSpace.yaml`**: Crea el espacio de nombres `ns-act2-group` donde residirán todos los objetos.
  - **`manifest/02-mysql-deployment.yaml`**: Crea el servicio interno, el volumen persistente (PVC) y el Deployment de la base de datos MySQL 8.0.
  - **`manifest/03-wordpress-deployment.yaml`**: Crea el volumen persistente (PVC), el servicio `NodePort` y el Deployment de WordPress con sus 2 réplicas.
