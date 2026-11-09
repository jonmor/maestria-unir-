#Notas iniciales

Para el caso particular de MacOS se utilizo Colima, siembre recordar encender els ervicio:


Base: https://kubernetes.io/docs/tutorials/stateful-application/mysql-wordpress-persistent-volume/

```bash
colima start
minikube profile list //Para ver mi cluster
//Si no existe
minikube profile list
minikube start -p practica2-minikube --driver=docker --cpus=4 --memory=8g
minikube start -p practica2-minikube --driver=docker --nodes=2 --cpus=2 --memory=5g
kubectl config use-context practica2-minikube
kubectl config set-context --current --namespace=practica2-minikube
minikube delete -p practica2-minikube
minikube stop --all
//Si existe
minikube start
kubectl get ns




minikube addons enable volumesnapshots -p practica2-minikube
minikube addons enable csi-hostpath-driver -p practica2-minikube

# hacerlo el StorageClass por defecto
kubectl patch storageclass standard -p '{"metadata":{"annotations":{"storageclass.kubernetes.io/is-default-class":"false"}}}'
kubectl patch storageclass csi-hostpath-sc -p '{"metadata":{"annotations":{"storageclass.kubernetes.io/is-default-class":"true"}}}'

```


---

KbxXEDRT^!u$AXjalE

```bash
kubectl apply -k ./
kubectl delete -k ./
kubectl get secret -n ns-act2-group
kubectl get pvc -n ns-act2-group
kubectl get pods -n ns-act2-group
//escalar
kubectl scale deploy wordpress --replicas=2 -n ns-act2-group

kubectl get nodes -o wide -n ns-act2-group
kubectl get all,pvc,secret -n ns-act2-group -o wide


kubectl get services wordpress -n ns-act2-group
minikube service wordpress -n ns-act2-group -p practica2-minikube --url

```


````bash
for p in $(kubectl get pods -n ns-act2-group -l tier=frontend -o name); do
  kubectl exec -n ns-act2-group $p -- printenv WORDPRESS_AUTH_KEY
done
```