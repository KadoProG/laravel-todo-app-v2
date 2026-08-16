#!/usr/bin/env bash
#
# tfstate を置く S3 バケットを作成する。
# Terraform の管理対象にすると「state を作るための state」が必要になるため、
# このバケットだけは手動で用意する。
#
# 使い方:
#   ./bootstrap-backend.sh                     # 既定のリージョンとプレフィックスで作成
#   PREFIX=my-tfstate REGION=us-east-1 ./bootstrap-backend.sh
#
set -euo pipefail

REGION="${REGION:-ap-northeast-1}"
PREFIX="${PREFIX:-todo-practices-tfstate}"

ACCOUNT_ID="$(aws sts get-caller-identity --query Account --output text)"
BUCKET="${PREFIX}-${ACCOUNT_ID}"

echo "リージョン: ${REGION}"
echo "バケット名: ${BUCKET}"
echo

if aws s3api head-bucket --bucket "$BUCKET" 2>/dev/null; then
  echo "バケットは既に存在します。設定の適用のみ行います。"
else
  # us-east-1 だけは LocationConstraint を受け付けない
  if [ "$REGION" = "us-east-1" ]; then
    aws s3api create-bucket \
      --bucket "$BUCKET" \
      --region "$REGION"
  else
    aws s3api create-bucket \
      --bucket "$BUCKET" \
      --region "$REGION" \
      --create-bucket-configuration "LocationConstraint=${REGION}"
  fi
  echo "バケットを作成しました。"
fi

# state は上書きされるため、事故からの復旧手段としてバージョニングを有効にする
aws s3api put-bucket-versioning \
  --bucket "$BUCKET" \
  --versioning-configuration Status=Enabled

# state には DB のパスワード等が平文で入るため暗号化する
aws s3api put-bucket-encryption \
  --bucket "$BUCKET" \
  --server-side-encryption-configuration '{
    "Rules": [{
      "ApplyServerSideEncryptionByDefault": { "SSEAlgorithm": "AES256" },
      "BucketKeyEnabled": true
    }]
  }'

# 同じ理由でパブリックアクセスを全て塞ぐ
aws s3api put-public-access-block \
  --bucket "$BUCKET" \
  --public-access-block-configuration \
    "BlockPublicAcls=true,IgnorePublicAcls=true,BlockPublicPolicy=true,RestrictPublicBuckets=true"

# 古いバージョンが際限なく溜まらないようにする
aws s3api put-bucket-lifecycle-configuration \
  --bucket "$BUCKET" \
  --lifecycle-configuration '{
    "Rules": [{
      "ID": "expire-noncurrent-versions",
      "Status": "Enabled",
      "Filter": {},
      "NoncurrentVersionExpiration": { "NoncurrentDays": 90 }
    }]
  }'

echo
echo "完了しました。各リポジトリの terraform/backend.hcl に次の値を設定してください。"
echo
echo "  bucket = \"${BUCKET}\""
echo "  region = \"${REGION}\""
