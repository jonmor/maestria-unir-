# Notas iniciales



URL de Pagina en la que se baso el cluster de wordpress: 

https://kubernetes.io/docs/tutorials/stateful-application/mysql-wordpress-persistent-volume/


URL de generacion de salt para wordpress:

https://api.wordpress.org/secret-key/1.1/salt/

Revisar archivo **"00-secret.yaml"**, ahi viven los valores de salt generados que se pasan como parametros al Pod

## Notas para macOS

Para el caso particular de MacOS se utilizo Colima, siempre recordar encender el servicio:

```bash
colima start
```

### Comandos de ayuda


Verificar que exista un perfil de minikube
```bash
minikube profile list
```

Si no existe perfil de minikube crear uno, puede crear el cluster con 1 o dos nodos
```bash
minikube start -p practica2-minikube --driver=docker --cpus=4 --memory=8g
minikube start -p practica2-minikube --driver=docker --nodes=2 --cpus=2 --memory=5g
```


Definir un perfil por default
```bash
kubectl config use-context practica2-minikube
kubectl config set-context --current --namespace=practica2-minikube
```


Eliminar un perfil de minikube el default se puede tambien eliminar "minikube"
```bash
minikube delete -p practica2-minikube
minikube delete -p minikube
```


Detener todos los cluster de minikube
```bash
minikube stop --all
```

Inicializar cluster cuando ya existe "minikube start" para el caso personalizado use.
```bash
minikube start -p practica2-minikube
kubectl get nodes                     # practica2-minikube y -m02
kubectl get storageclass              # debe estar csi-hostpath-sc, caso contrario crearlo con los comandos de addons
```


Puede hacer una abstracción de los comandos por ejemplo ns es igual a namespace, si no quiere escribirlo todo, svc
```bash
kubectl get ns
kubectl get namespace
```


Este apartado es importante ya que se requiere de volumen csi, para tener un comportamiento de disco atachado con iSCSI
```bash
minikube addons enable volumesnapshots -p practica2-minikube
minikube addons enable csi-hostpath-driver -p practica2-minikube
```

Hacerlo el StorageClass por defecto (Este solo es informativo, logre definirlo en el manifest de yaml si no mal recuerdo)
```bash
kubectl patch storageclass standard -p '{"metadata":{"annotations":{"storageclass.kubernetes.io/is-default-class":"false"}}}'
kubectl patch storageclass csi-hostpath-sc -p '{"metadata":{"annotations":{"storageclass.kubernetes.io/is-default-class":"true"}}}'
```


### Inicializar el cluster despues de ya tener un cluster activo

Revisar el archivo kustomization.yaml, ahi esta definido como se crea el nameSpace para el proyecto "ns-act2-group"
```bash
kubectl apply -k ./
```

### Eliminar el cluster, con todas las conf definidas
```bash
kubectl delete -k ./
```

### Comandos utiles ya con el cluster creado

Se puede obtener los secretos, pvc= disco, pods, escalar pods etc.
```bash
kubectl get secret -n ns-act2-group
kubectl get pvc -n ns-act2-group
kubectl get pods -n ns-act2-group
kubectl scale deploy wordpress --replicas=2 -n ns-act2-group
kubectl get nodes -o wide -n ns-act2-group
kubectl get all,pvc,secret -n ns-act2-group -o wide
kubectl get services wordpress -n ns-act2-group
```

### Comando que permite hacer una prueba rapida de conexion al cluster, te regresa una url para pruebas rapidas
```bash
minikube service wordpress -n ns-act2-group -p practica2-minikube --url
```

### Verificacion de session salt

Comando que utilice para verificar que el salt de sesión sea el mismo, identifique que cuando es un cluster de 2 nodos el salt es diferente y al balancear la carga pierde la sesión
```bash
for p in $(kubectl get pods -n ns-act2-group -l tier=frontend -o name); do
  kubectl exec -n ns-act2-group $p -- printenv WORDPRESS_AUTH_KEY
done
```



```bash
minikube stop -p practica2-minikube
```



---

# Instalacion Ubuntu Linux 26.04


### Setup docker apt

```bash
sudo apt remove $(dpkg --get-selections docker.io docker-compose docker-compose-v2 docker-doc docker-buildx podman-docker containerd runc | cut -f1)
# Add Docker's official GPG key:
sudo apt update
sudo apt install ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

# Add the repository to Apt sources:
sudo tee /etc/apt/sources.list.d/docker.sources <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF

sudo apt update
```

### install docker

```bash
sudo apt install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo systemctl status docker
sudo systemctl start docker
```

### Test docker

```bash
sudo docker run hello-world
```

### Install minikube

Leer el siguiente link para detalles:

https://minikube.sigs.k8s.io/docs/start/?arch=%2Flinux%2Farm64%2Fstable%2Fbinary+download

En mi caso uso arquitectura ARM64, por favor cambiar a X86-64 en caso de usarlo

```bash
curl -LO https://github.com/kubernetes/minikube/releases/latest/download/minikube-linux-arm64
sudo install minikube-linux-arm64 /usr/local/bin/minikube && rm minikube-linux-arm64
```

Comandos x86-64
```bash
curl -LO https://github.com/kubernetes/minikube/releases/latest/download/minikube-linux-amd64
sudo install minikube-linux-amd64 /usr/local/bin/minikube && rm minikube-linux-amd64
```

### Verificar minikube

```bash
minikube start
```

### Comandos de instalacion para cluster de kubernetes

```bash
ARCH=$(dpkg --print-architecture)   # amd64 o arm64
VER=$(curl -L -s https://dl.k8s.io/release/stable.txt)
curl -LO "https://dl.k8s.io/release/${VER}/bin/linux/${ARCH}/kubectl"
curl -LO "https://dl.k8s.io/release/${VER}/bin/linux/${ARCH}/kubectl.sha256"
echo "$(cat kubectl.sha256)  kubectl" | sha256sum --check   # debe decir: kubectl: OK
sudo install -o root -g root -m 0755 kubectl /usr/local/bin/kubectl
rm kubectl kubectl.sha256
kubectl version --client
```

---

### Comandos para crear lo necesario al cluster "kubectl"

```bash
kubectl config use-context practica2-minikube
kubectl config set-context --current --namespace=practica2-minikube
minikube start -p practica2-minikube --driver=docker --nodes=2 --cpus=2 --memory=5g
kubectl get nodes 
kubectl get storageclass 
minikube addons enable volumesnapshots -p practica2-minikube
minikube addons enable csi-hostpath-driver -p practica2-minikube
```

---

### Bajar datos del repositorio

```bash
git clone https://github.com/jonmor/maestria-unir-.git
cd maestria-unir-/
cd Contenedores/jme/actividad2/
kubectl apply -k ./
kubectl get all,pvc,secret -n ns-act2-group -o wide
kubectl get svc -A
nohup kubectl port-forward --address 0.0.0.0 svc/wordpress 8080:80 -n ns-act2-group > /tmp/pf.log 2>&1 &
tail -200f /tmp/pf.log 
```

---

### Wordpress instalation password que utilice
```txt
user:admin
password: WtOKsRzmrX8vozg%cg
```


Comando utilizados en pruebas, **no requeridos en el proceso**
```bash 
minikube stop -p practica2-minikube
minikube stop --all                  # detiene todos los perfiles
minikube profile list                # verifica que diga Stopped

minikube start -p practica2-minikube
kubectl config use-context practica2-minikube
kubectl get pods -n ns-act2-group -o wide
```
