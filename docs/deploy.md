# AWS へのデプロイ

ECS Fargate + ALB + RDS(MySQL) 構成。インフラは `terraform/`、デプロイは GitHub Actions の `Deploy` ワークフローが担当する。

## 構成

```
GitHub Actions --(OIDC)--> AWS
                            |
                            +-- ECR        イメージ置き場
                            +-- ECS Fargate (nginx + php-fpm を1コンテナに同居)
                            +-- ALB        :80 -> タスク:80、ヘルスチェックは /up
                            +-- RDS MySQL  プライベートサブネット
                            +-- Secrets Manager  APP_KEY / JWT_SECRET / DB_PASSWORD
```

ECS タスクはプライベートサブネットに置き、NAT Gateway 経由で ECR と Secrets Manager にアクセスする。

## 初回セットアップ

### 1. tfstate 用の S3 バケットを用意する

state を置くバケット自体は Terraform で管理できない（それを作るための state が必要になる）ため、
最初の一度だけスクリプトで作成する。

```bash
cd terraform
./bootstrap-backend.sh
```

バケット名は `todo-practices-tfstate-<アカウントID>` になる。
名前やリージョンを変えたい場合は環境変数で渡す。

```bash
PREFIX=my-tfstate REGION=us-east-1 ./bootstrap-backend.sh
```

スクリプトは以下を設定する。何度実行しても同じ結果になる。

| 設定 | 理由 |
| --- | --- |
| バージョニング | state の破損や誤った apply から戻せるようにする |
| デフォルト暗号化 (AES256) | state に DB パスワード等が平文で入るため |
| パブリックアクセスブロック | 同上 |
| 古いバージョンを 90 日で削除 | 際限なく溜まるのを防ぐ |

このバケットは 2 リポジトリで共有し、`key` だけを分ける。

### 2. Terraform を適用する

```bash
cd terraform
cp backend.hcl.example backend.hcl            # バケット名を書き換える
cp terraform.tfvars.example terraform.tfvars  # frontend_url を書き換える
terraform init -backend-config=backend.hcl
terraform apply
```

`frontend_url` はフロントエンド側の CloudFront URL。まだ無い場合は暫定値のまま apply し、
フロントエンド構築後に正しい値へ書き換えて再度 apply する。

GitHub OIDC プロバイダは AWS アカウントに 1 つしか作れない。
このリポジトリ側で作成する前提のため、`create_github_oidc_provider` は既定の `true` のままでよい。

### 3. GitHub のシークレットを設定する

`terraform output` の値を、リポジトリの Settings > Secrets and variables > Actions に登録する。

| シークレット名 | 値 |
| --- | --- |
| `AWS_ROLE_ARN` | `terraform output github_deploy_role_arn` |
| `AWS_SUBNET_IDS` | `terraform output private_subnet_ids` をカンマ区切りにしたもの |
| `AWS_SECURITY_GROUP_ID` | `terraform output ecs_security_group_id` |

`AWS_SUBNET_IDS` は `subnet-aaa,subnet-bbb` の形式で、引用符や空白を含めないこと。

### 4. 初回デプロイ

Terraform が作るタスク定義は ECR に `latest` タグが無い状態を指すため、
初回はサービスのタスクが起動しない。main に push して `Deploy` を一度流すと解消する。

## デプロイ

`main` への push、または Actions からの手動実行で `Deploy` が動く。

1. イメージをビルドして ECR に push（`latest` と commit SHA の 2 タグ）
2. 現行タスク定義を取得し、イメージだけ差し替えて新リビジョンを登録
3. `php artisan migrate --force` を単発タスクとして実行し、終了コードを確認
4. ECS サービスを新リビジョンで更新し、安定するまで待機

マイグレーションを単発タスクにしているのは、複数タスクが同時に migrate を叩くのを避けるため。
失敗した場合はサービス更新に進まず、稼働中のバージョンがそのまま残る。

## 環境変数

非機密の値は `terraform/ecs.tf` の `local.app_environment` に定義する。
機密の値は Secrets Manager に置き、タスク定義の `secrets` から注入する。

`SESSION_DRIVER` と `CACHE_STORE` にデータベースドライバを使っていないのは、
マイグレーション未適用の状態でもコンテナが起動できるようにするため。

## ログ

CloudWatch Logs の `/ecs/laravel-todo-v2-prod` に集約される。
nginx のアクセスログ、php-fpm のエラー、Laravel のログ（`LOG_CHANNEL=stderr`）が同じグループに出る。

## HTTPS と CORS について

ALB 自体は HTTP のみで、証明書を持たない。ブラウザからは
フロントエンド側の CloudFront に生やした `/api/*` ビヘイビア経由でアクセスする。

```
ブラウザ --https--> CloudFront --+--> S3 (静的ファイル)
                                 +--http--> ALB --> ECS  (/api/*)
```

同一オリジンになるため CORS のプリフライトは発生しない。
`FRONTEND_URL` は CORS 設定のために残してあるが、直接 ALB を叩くとき以外は経由しない。

`bootstrap/app.php` で `trustProxies(at: '*')` を設定しており、
CloudFront と ALB が付ける `X-Forwarded-Proto` を信頼して https として扱う。
これが無いと Laravel が生成する URL が http になる。

独自ドメインを使う場合は ACM 証明書を発行して CloudFront に紐付ける。
ALB 側は VPC 内の通信なので HTTP のままでよい。
