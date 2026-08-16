variable "aws_region" {
  description = "リソースを作成するリージョン"
  type        = string
  default     = "ap-northeast-1"
}

variable "project" {
  description = "リソース名のプレフィックス"
  type        = string
  default     = "laravel-todo-v2"
}

variable "environment" {
  description = "環境名"
  type        = string
  default     = "prod"
}

variable "github_repository" {
  description = "OIDC で信頼する GitHub リポジトリ (owner/repo)"
  type        = string
  default     = "KadoProG/laravel-todo-app-v2"
}

variable "create_github_oidc_provider" {
  description = "アカウント内に GitHub OIDC プロバイダを新規作成するか。既に存在する場合は false"
  type        = bool
  default     = true
}

variable "vpc_cidr" {
  description = "VPC の CIDR"
  type        = string
  default     = "10.20.0.0/16"
}

variable "frontend_url" {
  description = "CORS で許可するフロントエンドのオリジン (CloudFront の URL)"
  type        = string
}

variable "db_name" {
  description = "RDS のデータベース名"
  type        = string
  default     = "laravel"
}

variable "db_username" {
  description = "RDS のマスターユーザー名"
  type        = string
  default     = "laravel"
}

variable "db_instance_class" {
  description = "RDS のインスタンスクラス"
  type        = string
  default     = "db.t4g.micro"
}

variable "task_cpu" {
  description = "ECS タスクの CPU ユニット"
  type        = number
  default     = 512
}

variable "task_memory" {
  description = "ECS タスクのメモリ (MiB)"
  type        = number
  default     = 1024
}

variable "desired_count" {
  description = "ECS サービスの希望タスク数"
  type        = number
  default     = 1
}

variable "log_retention_days" {
  description = "CloudWatch Logs の保持日数"
  type        = number
  default     = 30
}
