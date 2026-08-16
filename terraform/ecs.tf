resource "aws_ecs_cluster" "main" {
  name = local.name

  setting {
    name  = "containerInsights"
    value = "disabled"
  }

  tags = { Name = local.name }
}

resource "aws_cloudwatch_log_group" "app" {
  name              = "/ecs/${local.name}"
  retention_in_days = var.log_retention_days

  tags = { Name = local.name }
}

locals {
  container_name = "app"

  # 非機密の環境変数。機密値は secrets で Secrets Manager から注入する
  app_environment = [
    { name = "APP_NAME", value = var.project },
    { name = "APP_ENV", value = "production" },
    { name = "APP_DEBUG", value = "false" },
    # CloudFront 経由で同一オリジン配信するため、公開 URL はフロントと同じになる
    { name = "APP_URL", value = var.frontend_url },
    { name = "FRONTEND_URL", value = var.frontend_url },
    { name = "LOG_CHANNEL", value = "stderr" },
    { name = "LOG_LEVEL", value = "info" },
    { name = "DB_CONNECTION", value = "mysql" },
    { name = "DB_HOST", value = aws_db_instance.main.address },
    { name = "DB_PORT", value = tostring(aws_db_instance.main.port) },
    { name = "DB_DATABASE", value = var.db_name },
    { name = "DB_USERNAME", value = var.db_username },
    # データベースドライバは使わない。マイグレーション未適用でも起動できるようにするため
    { name = "SESSION_DRIVER", value = "cookie" },
    { name = "CACHE_STORE", value = "file" },
    { name = "QUEUE_CONNECTION", value = "sync" },
    # コンテナのファイルシステムは揮発性のため、アップロードは S3 に置く。
    # 認証情報は環境変数ではなくタスクロールから取る
    { name = "FILESYSTEM_DISK", value = "s3" },
    { name = "AWS_BUCKET", value = aws_s3_bucket.uploads.bucket },
    { name = "AWS_DEFAULT_REGION", value = var.aws_region },
  ]

  app_secrets = [
    { name = "APP_KEY", valueFrom = "${aws_secretsmanager_secret.app.arn}:APP_KEY::" },
    { name = "JWT_SECRET", valueFrom = "${aws_secretsmanager_secret.app.arn}:JWT_SECRET::" },
    { name = "DB_PASSWORD", valueFrom = "${aws_secretsmanager_secret.app.arn}:DB_PASSWORD::" },
  ]
}

resource "aws_ecs_task_definition" "app" {
  family                   = local.name
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = var.task_cpu
  memory                   = var.task_memory
  execution_role_arn       = aws_iam_role.ecs_execution.arn
  task_role_arn            = aws_iam_role.ecs_task.arn

  runtime_platform {
    operating_system_family = "LINUX"
    cpu_architecture        = "X86_64"
  }

  container_definitions = jsonencode([
    {
      name      = local.container_name
      image     = "${aws_ecr_repository.app.repository_url}:latest"
      essential = true

      portMappings = [
        {
          containerPort = 80
          protocol      = "tcp"
        }
      ]

      environment = local.app_environment
      secrets     = local.app_secrets

      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.app.name
          "awslogs-region"        = var.aws_region
          "awslogs-stream-prefix" = "app"
        }
      }
    }
  ])

  tags = { Name = local.name }
}

resource "aws_ecs_service" "app" {
  name            = local.name
  cluster         = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.app.arn
  desired_count   = var.desired_count
  launch_type     = "FARGATE"

  network_configuration {
    subnets          = aws_subnet.private[*].id
    security_groups  = [aws_security_group.ecs.id]
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.app.arn
    container_name   = local.container_name
    container_port   = 80
  }

  deployment_minimum_healthy_percent = 100
  deployment_maximum_percent         = 200

  health_check_grace_period_seconds = 60

  # CI がイメージを更新するため、Terraform はタスク定義のリビジョンを追わない
  lifecycle {
    ignore_changes = [task_definition, desired_count]
  }

  depends_on = [aws_lb_listener.http]

  tags = { Name = local.name }
}
