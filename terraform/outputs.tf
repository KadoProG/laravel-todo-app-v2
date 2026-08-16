output "alb_dns_name" {
  description = "API のエンドポイント。フロントエンドの VITE_BACKEND_URL に設定する"
  value       = "http://${aws_lb.main.dns_name}"
}

output "ecr_repository_url" {
  description = "ECR リポジトリの URL"
  value       = aws_ecr_repository.app.repository_url
}

output "ecs_cluster_name" {
  description = "ECS クラスタ名"
  value       = aws_ecs_cluster.main.name
}

output "ecs_service_name" {
  description = "ECS サービス名"
  value       = aws_ecs_service.app.name
}

output "ecs_task_family" {
  description = "ECS タスク定義のファミリー名"
  value       = aws_ecs_task_definition.app.family
}

output "github_deploy_role_arn" {
  description = "GitHub Actions の AWS_ROLE_ARN シークレットに設定する値"
  value       = aws_iam_role.github_deploy.arn
}

output "private_subnet_ids" {
  description = "マイグレーションタスクを起動するサブネット"
  value       = aws_subnet.private[*].id
}

output "ecs_security_group_id" {
  description = "マイグレーションタスクに割り当てるセキュリティグループ"
  value       = aws_security_group.ecs.id
}

output "rds_endpoint" {
  description = "RDS のエンドポイント"
  value       = aws_db_instance.main.address
}
