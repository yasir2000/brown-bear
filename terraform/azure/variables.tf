variable "resource_group_name" {
  description = "Name of the resource group"
  type        = string
  default     = "brownbear-rg"
}

variable "location" {
  description = "Azure region"
  type        = string
  default     = "East US 2"
}

variable "environment" {
  description = "Environment name"
  type        = string
  default     = "production"
}

variable "cluster_name" {
  description = "AKS cluster name"
  type        = string
  default     = "brownbear-aks"
}

variable "kubernetes_version" {
  description = "Kubernetes version"
  type        = string
  default     = "1.27"
}

variable "node_count" {
  description = "Number of nodes in the default pool"
  type        = number
  default     = 3
}

variable "min_node_count" {
  description = "Minimum number of nodes"
  type        = number
  default     = 1
}

variable "max_node_count" {
  description = "Maximum number of nodes"
  type        = number
  default     = 5
}

variable "node_vm_size" {
  description = "VM size for AKS nodes"
  type        = string
  default     = "Standard_D4s_v3"
}

variable "os_disk_size_gb" {
  description = "OS disk size for nodes"
  type        = number
  default     = 50
}

variable "mysql_admin_username" {
  description = "MySQL administrator username"
  type        = string
  default     = "dbadmin"
}

variable "mysql_sku_name" {
  description = "MySQL SKU name"
  type        = string
  default     = "GP_Standard_D4s_v3"
}

variable "mysql_storage_gb" {
  description = "MySQL storage size in GB"
  type        = number
  default     = 100
}

variable "redis_capacity" {
  description = "Redis cache capacity"
  type        = number
  default     = 1
}

variable "redis_family" {
  description = "Redis cache family"
  type        = string
  default     = "C"
}

variable "redis_sku_name" {
  description = "Redis cache SKU"
  type        = string
  default     = "Standard"
}

variable "domain_name" {
  description = "Domain name for the application"
  type        = string
  default     = "brownbear.example.com"
}
