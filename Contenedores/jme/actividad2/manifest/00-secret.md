# Descripción de `00-secret.yaml`

Este manifiesto define un recurso **Secret** de Kubernetes para almacenar de forma centralizada credenciales sensibles: la contraseña de la base de datos MySQL y las claves/salts criptográficas de sesión de WordPress.

---

## Código del manifiesto

```yaml
apiVersion: v1
kind: Secret
metadata:
  name: mysql-pass
type: Opaque
stringData:
  password: "6y%ytTBJT&54#"
  auth-key: "ylB0<{ekLvJ{@lEdu.%c-nBQR2##f*$da|O{}Z;(DcUW}++T5pr0W*!/woRWdX>Z"
  secure-auth-key: "I_OZELfQiLg5;8%|PMqm8?9Y3-a^|@Ynj|dCHT7Sr,|[(ekwpBj:Ln!sCg-jsk%1"
  logged-in-key: "E_V{LMqT|lBGp`<Or?0Ab;e%Q-f#!1l}vj`oQk>@A[Z &:W!U/<Rbeo*|#p^t!l;"
  nonce-key: ".3&n6N+r%6|$L&q.7xHPhVlL>M,M*fcPGQ[V:faECKz>twN]BBBYC+|n#%]in67$"
  auth-salt: "+6JRa6[eaL/29|*#@u0N=hB-$x/E/&s|b?!4f{n=prrzTW#DKCJ-I,nB +$:e+;J"
  secure-auth-salt: "2I,)a,tvygi+Zper+%Dod+#lu-B-h+0?$!x,kdpYv*knX6@Grhfd.w$VFu,.-1EX"
  logged-in-salt: "vA;xQ?pFfIML9H}1Xi6nNy^VU0V]DPMYByEJ;iN2f@|?=rj;5s(p4j<ns,!d%Oq "
  nonce-salt: "Dwjs=UBhA,=)$Z)ge8?w;E^vHOgP9|;7fo!0^=p-{0q|.KFe+dAo/r/x%]?@[6Y|"
```

---

## Descripción de los elementos utilizados

- **`apiVersion: v1`**
  Sirve para indicar la versión de la API de Kubernetes que se utiliza para crear el objeto. `v1` corresponde al grupo principal (*core*) de Kubernetes donde viven recursos base como `Secret`, `Pod`, `Service` y `Namespace`.

- **`kind: Secret`**
  Sirve para especificar el tipo de recurso de Kubernetes. Un `Secret` es un objeto diseñado para almacenar datos confidenciales (contraseñas, tokens, claves) y evitar escribirlos directamente en el código de los `Deployments` o imágenes de contenedor.

- **`metadata:`**
  Bloque que contiene los datos que identifican de forma única al objeto dentro del clúster.
  - **`name: mysql-pass`**: Define el nombre identificador del secreto (`mysql-pass`). Con este nombre otros manifiestos (como los Deployments de MySQL y WordPress) hacen referencia a él mediante `secretKeyRef`.

- **`type: Opaque`**
  Sirve para definir el tipo de secreto. `Opaque` es el valor por defecto en Kubernetes e indica que el contenido son pares clave-valor arbitrarios definidos por el usuario.

- **`stringData:`**
  Sirve para ingresar los datos del secreto en **texto plano** dentro del YAML (a diferencia de `data:`, que exige codificar manualmente cada valor en Base64). Kubernetes se encarga automáticamente de codificarlos en Base64 al guardar el secreto en el clúster.
  - **`password`**: Contraseña compartida tanto por el contenedor de MySQL (para el usuario `root` y el usuario `wordpress`) como por WordPress para conectarse a la base de datos.
  - **`auth-key`, `secure-auth-key`, `logged-in-key`, `nonce-key`**: Claves criptográficas de WordPress utilizadas para firmar las cookies de autenticación y proteger las sesiones de los usuarios.
  - **`auth-salt`, `secure-auth-salt`, `logged-in-salt`, `nonce-salt`**: Valores *salt* criptográficos que refuerzan el cifrado de las cookies de sesión. Al inyectar los mismos valores desde este `Secret` a todas las réplicas de WordPress, se garantiza que el usuario no pierda su sesión cuando el balanceador redirige el tráfico entre distintos Pods.
