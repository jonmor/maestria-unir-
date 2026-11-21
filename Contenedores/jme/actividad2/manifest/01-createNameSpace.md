# Descripción de `01-createNameSpace.yaml`

Este manifiesto crea un **Namespace** (espacio de nombres) dedicado para aislar lógicamente todos los recursos de la Actividad 2 dentro del clúster de Kubernetes.

---

## Código del manifiesto

```yaml
apiVersion: v1
kind: Namespace
metadata:
  name: ns-act2-group
  labels:
    name: ns-act2-group
    environment: dev
```

---

## Descripción de los elementos utilizados

- **`apiVersion: v1`**
  Sirve para indicar que el recurso pertenece a la versión `v1` de la API principal (*core*) de Kubernetes.

- **`kind: Namespace`**
  Sirve para crear un clúster virtual o partición lógica dentro del clúster físico de Kubernetes. Permite agrupar y aislar los recursos de este proyecto (`Secrets`, `Deployments`, `Services`, `PVCs`) del resto del clúster (como el namespace `default` o `kube-system`).

- **`metadata:`**
  Contiene la información de identificación y organización del recurso.
  - **`name: ns-act2-group`**: Define el nombre exacto del Namespace (`ns-act2-group`). Es el nombre que se pasa con la bandera `-n ns-act2-group` en los comandos de `kubectl` y el que se asigna en `kustomization.yaml`.
  - **`labels:`**: Sirve para asignar etiquetas clave-valor al Namespace con fines de organización, filtrado o aplicación de políticas de red/cuotas:
    - **`name: ns-act2-group`**: Etiqueta identificadora con el nombre del espacio de nombres.
    - **`environment: dev`**: Etiqueta que clasifica este entorno como ambiente de desarrollo (`dev`).
