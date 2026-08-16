# ユーザーアイコンなどのアップロードファイルを置くバケット。
# ECS Fargate のファイルシステムは揮発性でデプロイのたびに消えるため、
# アップロードされたファイルはコンテナ外に逃がす。
resource "aws_s3_bucket" "uploads" {
  # バケット名はグローバルに一意である必要があるためアカウント ID を後ろに付ける
  bucket = "${local.name}-uploads-${data.aws_caller_identity.current.account_id}"

  # 練習用のため、destroy 時に中身ごと消えてよい
  force_destroy = true

  tags = { Name = "${local.name}-uploads" }
}

# 配信は Laravel の GET /api/v1/users/{user}/icon が仲介するため、
# バケット自体は完全に非公開にしておく
resource "aws_s3_bucket_public_access_block" "uploads" {
  bucket = aws_s3_bucket.uploads.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_ownership_controls" "uploads" {
  bucket = aws_s3_bucket.uploads.id

  rule {
    object_ownership = "BucketOwnerEnforced"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "uploads" {
  bucket = aws_s3_bucket.uploads.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}
