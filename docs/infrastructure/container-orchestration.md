# Container Orchestration

This document provides comprehensive guidance on Brown Bear ALM's container orchestration strategy using Kubernetes, including deployment patterns, scaling strategies, and operational procedures.

## Table of Contents

1. [Kubernetes Architecture](#kubernetes-architecture)
2. [Deployment Strategies](#deployment-strategies)
3. [Service Mesh Integration](#service-mesh-integration)
4. [Auto-Scaling Configuration](#auto-scaling-configuration)
5. [Resource Management](#resource-management)
6. [Networking and Ingress](#networking-and-ingress)
7. [Security Policies](#security-policies)
8. [Monitoring and Observability](#monitoring-and-observability)
9. [Backup and Recovery](#backup-and-recovery)

## Kubernetes Architecture

### Cluster Architecture

```mermaid
graph TB
    subgraph "Control Plane"
        API_SERVER[API Server]
        ETCD[(etcd)]
        SCHEDULER[Scheduler]
        CONTROLLER[Controller Manager]
        CLOUD_CONTROLLER[Cloud Controller]
    end
    
    subgraph "Worker Nodes"
        subgraph "Node 1"
            KUBELET1[kubelet]
            KUBE_PROXY1[kube-proxy]
            CONTAINER_RUNTIME1[Container Runtime]
            PODS1[Application Pods]
        end
        
        subgraph "Node 2"
            KUBELET2[kubelet]
            KUBE_PROXY2[kube-proxy]
            CONTAINER_RUNTIME2[Container Runtime]
            PODS2[Application Pods]
        end
        
        subgraph "Node N"
            KUBELETN[kubelet]
            KUBE_PROXYN[kube-proxy]
            CONTAINER_RUNTIMEN[Container Runtime]
            PODSN[Application Pods]
        end
    end
    
    subgraph "Add-ons"
        DNS[CoreDNS]
        INGRESS[Ingress Controller]
        MONITORING[Monitoring]
        LOGGING[Logging]
    end
    
    API_SERVER <--> ETCD
    API_SERVER <--> SCHEDULER
    API_SERVER <--> CONTROLLER
    API_SERVER <--> CLOUD_CONTROLLER
    
    API_SERVER <--> KUBELET1
    API_SERVER <--> KUBELET2
    API_SERVER <--> KUBELETN
    
    KUBELET1 --> CONTAINER_RUNTIME1
    KUBELET2 --> CONTAINER_RUNTIME2
    KUBELETN --> CONTAINER_RUNTIMEN
    
    CONTAINER_RUNTIME1 --> PODS1
    CONTAINER_RUNTIME2 --> PODS2
    CONTAINER_RUNTIMEN --> PODSN
    
    DNS -.-> PODS1
    DNS -.-> PODS2
    DNS -.-> PODSN
```

### Namespace Organization

```mermaid
graph TB
    subgraph "Production Cluster"
        subgraph "System Namespaces"
            KUBE_SYSTEM[kube-system]
            KUBE_PUBLIC[kube-public]
            KUBE_NODE_LEASE[kube-node-lease]
        end
        
        subgraph "Infrastructure Namespaces"
            INGRESS_NS[ingress-nginx]
            MONITORING_NS[monitoring]
            LOGGING_NS[logging]
            CERT_MANAGER_NS[cert-manager]
        end
        
        subgraph "Application Namespaces"
            BROWNBEAR_PROD[brownbear-production]
            BROWNBEAR_STAGING[brownbear-staging]
            BROWNBEAR_DEV[brownbear-development]
        end
        
        subgraph "Database Namespaces"
            DATABASE_NS[database]
            CACHE_NS[cache]
            BACKUP_NS[backup]
        end
    end
    
    style KUBE_SYSTEM fill:#ffcdd2
    style INGRESS_NS fill:#e8f5e8
    style BROWNBEAR_PROD fill:#e3f2fd
    style DATABASE_NS fill:#fff3e0
```

## Deployment Strategies

### Blue-Green Deployment

```mermaid
sequenceDiagram
    participant LB as Load Balancer
    participant Blue as Blue Environment
    participant Green as Green Environment
    participant Monitor as Monitoring
    
    Note over Blue: Current Production (v1.0)
    Note over Green: Preparing Deployment (v2.0)
    
    Green->>Green: Deploy v2.0
    Green->>Green: Run Health Checks
    Green->>Monitor: Validate Metrics
    Monitor-->>Green: Health Confirmed
    
    LB->>Blue: 100% Traffic
    LB->>Green: Switch to 100% Traffic
    
    Note over Blue: Standby (v1.0)
    Note over Green: Active Production (v2.0)
    
    alt Rollback Required
        LB->>Blue: Switch Back to 100%
        Note over Blue: Active Production (v1.0)
        Note over Green: Failed Deployment
    end
```

### Canary Deployment

```mermaid
graph TB
    subgraph "Canary Deployment Process"
        V1[Version 1.0<br/>90% Traffic]
        V2[Version 2.0<br/>10% Traffic]
        
        subgraph "Traffic Split"
            ROUTER[Traffic Router]
            METRICS[Metrics Collection]
            ANALYSIS[Automated Analysis]
        end
        
        subgraph "Decision Logic"
            SUCCESS{Metrics OK?}
            INCREASE[Increase Traffic]
            ROLLBACK[Rollback]
            COMPLETE[Complete Deployment]
        end
    end
    
    ROUTER --> V1
    ROUTER --> V2
    
    V1 --> METRICS
    V2 --> METRICS
    METRICS --> ANALYSIS
    ANALYSIS --> SUCCESS
    
    SUCCESS -->|Yes| INCREASE
    SUCCESS -->|No| ROLLBACK
    INCREASE --> SUCCESS
    INCREASE --> COMPLETE
```

### Rolling Update Configuration

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: brownbear-web
  namespace: brownbear-production
spec:
  replicas: 6
  strategy:
    type: RollingUpdate
    rollingUpdate:
      maxUnavailable: 1
      maxSurge: 2
  selector:
    matchLabels:
      app: brownbear-web
  template:
    metadata:
      labels:
        app: brownbear-web
        version: v2.0.0
    spec:
      containers:
      - name: web
        image: brownbear/web:v2.0.0
        ports:
        - containerPort: 8080
        resources:
          requests:
            cpu: 200m
            memory: 256Mi
          limits:
            cpu: 500m
            memory: 512Mi
        livenessProbe:
          httpGet:
            path: /health
            port: 8080
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /ready
            port: 8080
          initialDelaySeconds: 5
          periodSeconds: 5
```

## Service Mesh Integration

### Istio Service Mesh Architecture

```mermaid
graph TB
    subgraph "Control Plane"
        ISTIOD[Istiod]
        PILOT[Pilot]
        CITADEL[Citadel]
        GALLEY[Galley]
    end
    
    subgraph "Data Plane"
        subgraph "Pod 1"
            APP1[Application]
            ENVOY1[Envoy Proxy]
        end
        
        subgraph "Pod 2"
            APP2[Application]
            ENVOY2[Envoy Proxy]
        end
        
        subgraph "Pod N"
            APPN[Application]
            ENVOYN[Envoy Proxy]
        end
    end
    
    subgraph "Ingress Gateway"
        ISTIO_GATEWAY[Istio Gateway]
        ENVOY_GATEWAY[Envoy Gateway]
    end
    
    ISTIOD --> ENVOY1
    ISTIOD --> ENVOY2
    ISTIOD --> ENVOYN
    ISTIOD --> ENVOY_GATEWAY
    
    APP1 <--> ENVOY1
    APP2 <--> ENVOY2
    APPN <--> ENVOYN
    
    ENVOY1 <--> ENVOY2
    ENVOY2 <--> ENVOYN
    ENVOY1 <--> ENVOYN
    
    ENVOY_GATEWAY --> ENVOY1
    ENVOY_GATEWAY --> ENVOY2
    ENVOY_GATEWAY --> ENVOYN
```

### Traffic Management Policies

```yaml
# Virtual Service for Traffic Routing
apiVersion: networking.istio.io/v1beta1
kind: VirtualService
metadata:
  name: brownbear-web
  namespace: brownbear-production
spec:
  hosts:
  - brownbear.example.com
  gateways:
  - brownbear-gateway
  http:
  - match:
    - headers:
        canary:
          exact: "true"
    route:
    - destination:
        host: brownbear-web
        subset: canary
      weight: 100
  - route:
    - destination:
        host: brownbear-web
        subset: stable
      weight: 90
    - destination:
        host: brownbear-web
        subset: canary
      weight: 10

---
# Destination Rule for Load Balancing
apiVersion: networking.istio.io/v1beta1
kind: DestinationRule
metadata:
  name: brownbear-web
  namespace: brownbear-production
spec:
  host: brownbear-web
  trafficPolicy:
    loadBalancer:
      simple: LEAST_CONN
    connectionPool:
      tcp:
        maxConnections: 100
      http:
        http1MaxPendingRequests: 10
        maxRequestsPerConnection: 2
    circuitBreaker:
      consecutiveErrors: 5
      interval: 30s
      baseEjectionTime: 30s
  subsets:
  - name: stable
    labels:
      version: v1.0.0
  - name: canary
    labels:
      version: v2.0.0
```

## Auto-Scaling Configuration

### Horizontal Pod Autoscaler (HPA)

```mermaid
graph TB
    subgraph "HPA Components"
        HPA_CONTROLLER[HPA Controller]
        METRICS_SERVER[Metrics Server]
        CUSTOM_METRICS[Custom Metrics API]
        EXTERNAL_METRICS[External Metrics API]
    end
    
    subgraph "Scaling Metrics"
        CPU[CPU Utilization]
        MEMORY[Memory Utilization]
        REQUESTS[Requests per Second]
        QUEUE_LENGTH[Queue Length]
        CUSTOM[Custom Business Metrics]
    end
    
    subgraph "Scaling Actions"
        SCALE_UP[Scale Up Pods]
        SCALE_DOWN[Scale Down Pods]
        MAINTAIN[Maintain Current]
    end
    
    HPA_CONTROLLER --> METRICS_SERVER
    HPA_CONTROLLER --> CUSTOM_METRICS
    HPA_CONTROLLER --> EXTERNAL_METRICS
    
    METRICS_SERVER --> CPU
    METRICS_SERVER --> MEMORY
    CUSTOM_METRICS --> REQUESTS
    CUSTOM_METRICS --> QUEUE_LENGTH
    EXTERNAL_METRICS --> CUSTOM
    
    CPU --> SCALE_UP
    MEMORY --> SCALE_DOWN
    REQUESTS --> MAINTAIN
```

### HPA Configuration Example

```yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: brownbear-web-hpa
  namespace: brownbear-production
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: brownbear-web
  minReplicas: 3
  maxReplicas: 20
  metrics:
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70
  - type: Resource
    resource:
      name: memory
      target:
        type: Utilization
        averageUtilization: 80
  - type: Pods
    pods:
      metric:
        name: requests_per_second
      target:
        type: AverageValue
        averageValue: "100"
  behavior:
    scaleUp:
      stabilizationWindowSeconds: 60
      policies:
      - type: Percent
        value: 100
        periodSeconds: 15
      - type: Pods
        value: 2
        periodSeconds: 60
    scaleDown:
      stabilizationWindowSeconds: 300
      policies:
      - type: Percent
        value: 10
        periodSeconds: 60
```

### Cluster Autoscaler

```mermaid
graph TB
    subgraph "Cluster Autoscaler Process"
        PENDING_PODS[Pending Pods]
        NODE_UTILIZATION[Node Utilization Check]
        SCALE_DECISION[Scaling Decision]
        
        subgraph "Scale Up"
            ADD_NODES[Add Nodes]
            PROVISION[Node Provisioning]
            JOIN_CLUSTER[Join Cluster]
        end
        
        subgraph "Scale Down"
            IDENTIFY_UNDERUTILIZED[Identify Underutilized Nodes]
            DRAIN_PODS[Drain Pods]
            REMOVE_NODES[Remove Nodes]
        end
    end
    
    PENDING_PODS --> SCALE_DECISION
    NODE_UTILIZATION --> SCALE_DECISION
    
    SCALE_DECISION -->|Scale Up| ADD_NODES
    SCALE_DECISION -->|Scale Down| IDENTIFY_UNDERUTILIZED
    
    ADD_NODES --> PROVISION
    PROVISION --> JOIN_CLUSTER
    
    IDENTIFY_UNDERUTILIZED --> DRAIN_PODS
    DRAIN_PODS --> REMOVE_NODES
```

## Resource Management

### Resource Quotas and Limits

```yaml
# Namespace Resource Quota
apiVersion: v1
kind: ResourceQuota
metadata:
  name: brownbear-production-quota
  namespace: brownbear-production
spec:
  hard:
    requests.cpu: "20"
    requests.memory: 40Gi
    limits.cpu: "40"
    limits.memory: 80Gi
    persistentvolumeclaims: "10"
    pods: "50"
    services: "20"
    secrets: "50"
    configmaps: "50"

---
# Limit Range for Default Constraints
apiVersion: v1
kind: LimitRange
metadata:
  name: brownbear-production-limits
  namespace: brownbear-production
spec:
  limits:
  - default:
      cpu: 500m
      memory: 512Mi
    defaultRequest:
      cpu: 100m
      memory: 128Mi
    type: Container
  - max:
      cpu: "2"
      memory: 4Gi
    min:
      cpu: 50m
      memory: 64Mi
    type: Container
  - max:
      storage: 10Gi
    min:
      storage: 1Gi
    type: PersistentVolumeClaim
```

### Quality of Service Classes

```mermaid
graph TB
    subgraph "QoS Classes"
        GUARANTEED[Guaranteed<br/>requests = limits]
        BURSTABLE[Burstable<br/>requests < limits]
        BESTEFFORT[BestEffort<br/>no requests/limits]
    end
    
    subgraph "Priority and Preemption"
        HIGH_PRIORITY[High Priority Workloads]
        MEDIUM_PRIORITY[Medium Priority Workloads]
        LOW_PRIORITY[Low Priority Workloads]
    end
    
    subgraph "Scheduling Behavior"
        SCHEDULE_FIRST[Schedule First]
        SCHEDULE_NORMAL[Normal Scheduling]
        PREEMPT_FIRST[Preempted First]
    end
    
    GUARANTEED --> HIGH_PRIORITY
    BURSTABLE --> MEDIUM_PRIORITY
    BESTEFFORT --> LOW_PRIORITY
    
    HIGH_PRIORITY --> SCHEDULE_FIRST
    MEDIUM_PRIORITY --> SCHEDULE_NORMAL
    LOW_PRIORITY --> PREEMPT_FIRST
```

## Networking and Ingress

### Ingress Architecture

```mermaid
graph TB
    subgraph "External Traffic"
        INTERNET[Internet Traffic]
        CDN[Content Delivery Network]
        DNS[DNS Resolution]
    end
    
    subgraph "Load Balancing"
        CLOUD_LB[Cloud Load Balancer]
        WAF[Web Application Firewall]
        SSL_TERM[SSL Termination]
    end
    
    subgraph "Ingress Layer"
        INGRESS_CONTROLLER[Ingress Controller]
        INGRESS_RESOURCE[Ingress Resources]
        SERVICE_MESH_GATEWAY[Service Mesh Gateway]
    end
    
    subgraph "Service Layer"
        CLUSTER_IP[ClusterIP Services]
        NODE_PORT[NodePort Services]
        LOAD_BALANCER[LoadBalancer Services]
    end
    
    subgraph "Pod Network"
        WEB_PODS[Web Pods]
        API_PODS[API Pods]
        WORKER_PODS[Worker Pods]
    end
    
    INTERNET --> CDN
    CDN --> DNS
    DNS --> CLOUD_LB
    
    CLOUD_LB --> WAF
    WAF --> SSL_TERM
    SSL_TERM --> INGRESS_CONTROLLER
    
    INGRESS_CONTROLLER --> INGRESS_RESOURCE
    INGRESS_RESOURCE --> SERVICE_MESH_GATEWAY
    
    SERVICE_MESH_GATEWAY --> CLUSTER_IP
    SERVICE_MESH_GATEWAY --> NODE_PORT
    SERVICE_MESH_GATEWAY --> LOAD_BALANCER
    
    CLUSTER_IP --> WEB_PODS
    NODE_PORT --> API_PODS
    LOAD_BALANCER --> WORKER_PODS
```

### Network Policies

```yaml
# Default Deny All Traffic
apiVersion: networking.k8s.io/v1
kind: NetworkPolicy
metadata:
  name: default-deny-all
  namespace: brownbear-production
spec:
  podSelector: {}
  policyTypes:
  - Ingress
  - Egress

---
# Allow Web to API Communication
apiVersion: networking.k8s.io/v1
kind: NetworkPolicy
metadata:
  name: web-to-api
  namespace: brownbear-production
spec:
  podSelector:
    matchLabels:
      app: brownbear-api
  policyTypes:
  - Ingress
  ingress:
  - from:
    - podSelector:
        matchLabels:
          app: brownbear-web
    ports:
    - protocol: TCP
      port: 8080

---
# Allow API to Database
apiVersion: networking.k8s.io/v1
kind: NetworkPolicy
metadata:
  name: api-to-database
  namespace: brownbear-production
spec:
  podSelector:
    matchLabels:
      app: brownbear-api
  policyTypes:
  - Egress
  egress:
  - to:
    - namespaceSelector:
        matchLabels:
          name: database
    ports:
    - protocol: TCP
      port: 3306
  - to: []
    ports:
    - protocol: UDP
      port: 53
```

This container orchestration documentation provides comprehensive guidance for deploying, scaling, and managing Brown Bear ALM on Kubernetes with advanced features like service mesh integration, auto-scaling, and network security policies.
