# Laravel の APP_KEY は base64:<32バイトのbase64> という形式である必要がある
resource "random_bytes" "app_key" {
  length = 32
}

resource "random_password" "jwt_secret" {
  length  = 64
  special = false
}

resource "aws_secretsmanager_secret" "app" {
  name        = "${local.name}/app"
  # Laravel アプリの実行時シークレット
  description = "Runtime secrets for the Laravel app"

  # 検証中の作り直しを妨げないよう、削除待機期間を最短にする
  recovery_window_in_days = 0
}

resource "aws_secretsmanager_secret_version" "app" {
  secret_id = aws_secretsmanager_secret.app.id

  secret_string = jsonencode({
    APP_KEY     = "base64:${random_bytes.app_key.base64}"
    JWT_SECRET  = random_password.jwt_secret.result
    DB_PASSWORD = random_password.db.result
  })
}
