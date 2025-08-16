# Brown Bear ALM - GCP Infrastructure
# This configuration deploys a production-ready Brown Bear ALM platform on GCP

terraform {
  required_version = ">= 1.5"
  required_providers {
    google = {
      source  = "hashicorp/google"
      version = "~> 4.84"
    }
    google-beta = {
      source  = "hashicorp/google-beta"
      version = "~> 4.84"
    }
    kubernetes = {
      source  = "hashicorp/kubernetes"
      version = "~> 2.21"
    }
    helm = {
      source  = "hashicorp/helm"
      version = "~> 2.10"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.5"
    }
  }

  backend "gcs" {
    bucket = "brownbear-terraform-state"
    prefix = "production/terraform.tfstate"
  }
}

provider "google" {
  project = var.project_id
  region  = var.region
  zone    = var.zone
}

provider "google-beta" {
  project = var.project_id
  region  = var.region
  zone    = var.zone
}

provider "kubernetes" {
  host                   = "https://${module.gke.endpoint}"
  token                  = data.google_client_config.default.access_token
  cluster_ca_certificate = base64decode(module.gke.ca_certificate)
}

provider "helm" {
  kubernetes {
    host                   = "https://${module.gke.endpoint}"
    token                  = data.google_client_config.default.access_token
    cluster_ca_certificate = base64decode(module.gke.ca_certificate)
  }
}

data "google_client_config" "default" {}

locals {
  name = "${var.cluster_name}-${var.environment}"

  tags = {
    environment = var.environment
    project     = "brownbear"
    managed-by  = "terraform"
  }
}

# VPC Network
resource "google_compute_network" "main" {
  name                    = "${local.name}-vpc"
  auto_create_subnetworks = false
  routing_mode           = "REGIONAL"
}

# Subnets
resource "google_compute_subnetwork" "private" {
  name          = "${local.name}-private"
  ip_cidr_range = "10.0.0.0/24"
  region        = var.region
  network       = google_compute_network.main.id

  secondary_ip_range {
    range_name    = "k8s-pod-range"
    ip_cidr_range = "10.1.0.0/16"
  }

  secondary_ip_range {
    range_name    = "k8s-service-range"
    ip_cidr_range = "10.2.0.0/16"
  }

  private_ip_google_access = true
}

# Cloud Router
resource "google_compute_router" "main" {
  name    = "${local.name}-router"
  region  = var.region
  network = google_compute_network.main.id
}

# Cloud NAT
resource "google_compute_router_nat" "main" {
  name                               = "${local.name}-nat"
  router                            = google_compute_router.main.name
  region                            = var.region
  nat_ip_allocate_option            = "AUTO_ONLY"
  source_subnetwork_ip_ranges_to_nat = "ALL_SUBNETWORKS_ALL_IP_RANGES"

  log_config {
    enable = true
    filter = "ERRORS_ONLY"
  }
}

# GKE Cluster
module "gke" {
  source = "terraform-google-modules/kubernetes-engine/google//modules/private-cluster"
  version = "~> 29.0"

  project_id = var.project_id
  name       = local.name
  region     = var.region

  network           = google_compute_network.main.name
  subnetwork        = google_compute_subnetwork.private.name
  ip_range_pods     = "k8s-pod-range"
  ip_range_services = "k8s-service-range"

  kubernetes_version           = var.cluster_version
  release_channel             = "STABLE"
  enable_private_endpoint     = false
  enable_private_nodes        = true
  master_ipv4_cidr_block      = "172.16.0.0/28"

  horizontal_pod_autoscaling  = true
  network_policy              = true
  enable_binary_authorization = false

  node_pools = [
    {
      name            = "general-pool"
      machine_type    = var.machine_type
      node_locations  = "${var.zone},${substr(var.zone, 0, length(var.zone)-1)}b,${substr(var.zone, 0, length(var.zone)-1)}c"
      min_count       = var.min_node_count
      max_count       = var.max_node_count
      initial_node_count = var.node_count
      disk_size_gb    = var.disk_size_gb
      disk_type       = var.disk_type
      image_type      = "COS_CONTAINERD"
      enable_autoscaling = true
      auto_repair     = true
      auto_upgrade    = true
      service_account = google_service_account.gke_nodes.email

      node_metadata = "GKE_METADATA"

      oauth_scopes = [
        "https://www.googleapis.com/auth/cloud-platform"
      ]
    },
    {
      name            = "gitlab-pool"
      machine_type    = "e2-standard-8"
      node_locations  = var.zone
      min_count       = 1
      max_count       = 3
      initial_node_count = 2
      disk_size_gb    = 100
      disk_type       = var.disk_type
      image_type      = "COS_CONTAINERD"
      enable_autoscaling = true
      auto_repair     = true
      auto_upgrade    = true
      service_account = google_service_account.gke_nodes.email

      node_metadata = "GKE_METADATA"

      oauth_scopes = [
        "https://www.googleapis.com/auth/cloud-platform"
      ]

      taints = [{
        key    = "gitlab"
        value  = "true"
        effect = "NO_SCHEDULE"
      }]

      labels = {
        role = "gitlab"
      }
    }
  ]

  node_pools_oauth_scopes = {
    all = [
      "https://www.googleapis.com/auth/cloud-platform",
    ]
  }

  node_pools_labels = {
    all = local.tags
  }

  node_pools_tags = {
    all = ["gke-node"]
  }
}

# Service Account for GKE Nodes
resource "google_service_account" "gke_nodes" {
  account_id   = "${local.name}-gke-nodes"
  display_name = "GKE Node Service Account"
  description  = "Service account for GKE nodes"
}

resource "google_project_iam_member" "gke_nodes" {
  for_each = toset([
    "roles/logging.logWriter",
    "roles/monitoring.metricWriter",
    "roles/monitoring.viewer",
    "roles/stackdriver.resourceMetadata.writer"
  ])

  project = var.project_id
  role    = each.value
  member  = "serviceAccount:${google_service_account.gke_nodes.email}"
}

# Random password for database
resource "random_password" "db_password" {
  length  = 16
  special = true
}

# Private Service Connection for Cloud SQL
resource "google_compute_global_address" "private_ip_address" {
  name          = "${local.name}-private-ip"
  purpose       = "VPC_PEERING"
  address_type  = "INTERNAL"
  prefix_length = 16
  network       = google_compute_network.main.id
}

resource "google_service_networking_connection" "private_vpc_connection" {
  network                 = google_compute_network.main.id
  service                 = "servicenetworking.googleapis.com"
  reserved_peering_ranges = [google_compute_global_address.private_ip_address.name]
}

# Cloud SQL MySQL Instance
resource "google_sql_database_instance" "mysql" {
  name             = "${local.name}-mysql"
  database_version = "MYSQL_8_0"
  region           = var.region

  deletion_protection = true

  settings {
    tier              = var.db_tier
    disk_size         = var.db_disk_size
    disk_type         = "PD_SSD"
    disk_autoresize   = true
    availability_type = "REGIONAL"

    backup_configuration {
      enabled                        = true
      start_time                     = "03:00"
      point_in_time_recovery_enabled = true
      binary_log_enabled            = true
      backup_retention_settings {
        retained_backups = 30
      }
    }

    maintenance_window {
      day         = 7
      hour        = 3
      update_track = "stable"
    }

    ip_configuration {
      ipv4_enabled                                  = false
      private_network                               = google_compute_network.main.id
      enable_private_path_for_google_cloud_services = true
    }

    insights_config {
      query_insights_enabled  = true
      record_application_tags = true
      record_client_address   = true
    }
  }

  depends_on = [google_service_networking_connection.private_vpc_connection]
}

# Cloud SQL Database
resource "google_sql_database" "tuleap" {
  name     = "tuleap"
  instance = google_sql_database_instance.mysql.name
}

# Cloud SQL User
resource "google_sql_user" "admin" {
  name     = "admin"
  instance = google_sql_database_instance.mysql.name
  password = random_password.db_password.result
}

# Memorystore Redis Instance
resource "google_redis_instance" "redis" {
  name           = "${local.name}-redis"
  memory_size_gb = var.redis_memory_size_gb
  region         = var.region

  location_id             = var.zone
  alternative_location_id = "${substr(var.zone, 0, length(var.zone)-1)}b"

  authorized_network = google_compute_network.main.id

  redis_version     = "REDIS_7_0"
  display_name      = "Brown Bear Redis Cache"
  tier              = "STANDARD_HA"

  auth_enabled            = true
  transit_encryption_mode = "SERVER_AUTHENTICATION"

  maintenance_policy {
    weekly_maintenance_window {
      day = "SUNDAY"
      start_time {
        hours   = 3
        minutes = 0
        seconds = 0
        nanos   = 0
      }
    }
  }
}

# Cloud Storage Bucket for backups
resource "google_storage_bucket" "backups" {
  name     = "${local.name}-backups"
  location = var.region

  versioning {
    enabled = true
  }

  lifecycle_rule {
    condition {
      age = 30
    }
    action {
      type = "Delete"
    }
  }
}

# Service Account for Workload Identity
resource "google_service_account" "brownbear" {
  account_id   = "${local.name}-workload"
  display_name = "Brown Bear Workload Service Account"
}

resource "google_project_iam_member" "brownbear" {
  for_each = toset([
    "roles/secretmanager.secretAccessor",
    "roles/storage.objectAdmin",
    "roles/cloudsql.client"
  ])

  project = var.project_id
  role    = each.value
  member  = "serviceAccount:${google_service_account.brownbear.email}"
}

# Kubernetes Service Account for Workload Identity
resource "google_service_account_iam_member" "workload_identity" {
  service_account_id = google_service_account.brownbear.name
  role               = "roles/iam.workloadIdentityUser"
  member             = "serviceAccount:${var.project_id}.svc.id.goog[brownbear/brownbear-service-account]"
}

# Firewall Rules
resource "google_compute_firewall" "allow_internal" {
  name    = "${local.name}-allow-internal"
  network = google_compute_network.main.id

  allow {
    protocol = "tcp"
    ports    = ["0-65535"]
  }

  allow {
    protocol = "udp"
    ports    = ["0-65535"]
  }

  allow {
    protocol = "icmp"
  }

  source_ranges = ["10.0.0.0/8"]
}

resource "google_compute_firewall" "allow_health_check" {
  name    = "${local.name}-allow-health-check"
  network = google_compute_network.main.id

  allow {
    protocol = "tcp"
    ports    = ["80", "443", "8080"]
  }

  source_ranges = ["130.211.0.0/22", "35.191.0.0/16"]
  target_tags   = ["gke-node"]
}
