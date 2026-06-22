# Quick Start Guide - Deploy lên GCP trong 15 phút

Hướng dẫn nhanh để deploy dự án lên Google Cloud Platform.

## Prerequisites

```bash
# Cài đặt Google Cloud SDK
curl https://sdk.cloud.google.com | bash
exec -l $SHELL

# Login
gcloud auth login
```

## Bước 1: Setup PlanetScale (5 phút)

```bash
# 1. Đăng ký tài khoản tại: https://planetscale.com/
# 2. Tạo database mới có tên "mail-app"
# 3. Chọn region gần bạn nhất (Singapore)
# 4. Lấy connection string:
#    - Vào Database Settings → Passwords
#    - Click "New password" → Copy connection string
#    - Format: mysql://user:pass@host/database?sslmode=require
```

**Lưu connection string lại, ví dụ:**
```
mysql://abc123:pscale_pw_xyz@aws.connect.psdb.cloud/mail-app?sslmode=require
```

## Bước 2: Setup GCP Project (3 phút)

```bash
# Tạo project
gcloud projects create mail-app-prod --name="Mail App"
gcloud config set project mail-app-prod

# Enable APIs
gcloud services enable run.googleapis.com \
    cloudbuild.googleapis.com \
    containerregistry.googleapis.com \
    secretmanager.googleapis.com \
    storage-api.googleapis.com
```

**⚠️ Quan trọng:** Bật billing cho project tại:
https://console.cloud.google.com/billing

## Bước 3: Setup Cloud Storage (2 phút)

```bash
# Tạo bucket (thay YOUR_UNIQUE_NAME bằng tên unique)
export BUCKET_NAME="mail-app-files-$(date +%s)"
gsutil mb -p mail-app-prod -c STANDARD -l asia-southeast1 gs://${BUCKET_NAME}/

# Tạo service account
gcloud iam service-accounts create mail-app-storage \
    --display-name="Mail App Storage"

# Lấy email service account
export SA_EMAIL=$(gcloud iam service-accounts list \
    --filter="displayName:Mail App Storage" \
    --format="value(email)")

# Cấp quyền
gsutil iam ch serviceAccount:${SA_EMAIL}:roles/storage.objectAdmin gs://${BUCKET_NAME}/

# Tạo key file
gcloud iam service-accounts keys create gcs-key.json \
    --iam-account=${SA_EMAIL}

echo "✓ Bucket name: ${BUCKET_NAME}"
echo "✓ Key file created: gcs-key.json"
```

## Bước 4: Setup Secrets (2 phút)

```bash
# 1. Generate APP_KEY
php artisan key:generate --show
# Output: base64:xxxxx... (copy cái này)

# 2. Tạo secrets
echo -n "base64:xxxxx..." | gcloud secrets create APP_KEY --data-file=-

echo -n "mysql://user:pass@host/db?sslmode=require" | \
    gcloud secrets create DB_CONNECTION_URL --data-file=-

gcloud secrets create GCS_KEY_FILE --data-file=gcs-key.json

# 3. Cấp quyền
PROJECT_NUMBER=$(gcloud projects describe mail-app-prod --format="value(projectNumber)")

for SECRET in APP_KEY DB_CONNECTION_URL GCS_KEY_FILE; do
    gcloud secrets add-iam-policy-binding $SECRET \
        --member="serviceAccount:${PROJECT_NUMBER}-compute@developer.gserviceaccount.com" \
        --role="roles/secretmanager.secretAccessor"
done

echo "✓ Secrets created successfully"
```

## Bước 5: Deploy to Cloud Run (3 phút)

```bash
# Build và deploy
gcloud builds submit --tag gcr.io/mail-app-prod/mail-app

gcloud run deploy mail-app \
    --image gcr.io/mail-app-prod/mail-app \
    --platform managed \
    --region asia-southeast1 \
    --allow-unauthenticated \
    --min-instances 0 \
    --max-instances 10 \
    --memory 512Mi \
    --cpu 1 \
    --timeout 300 \
    --set-env-vars "APP_ENV=production,APP_DEBUG=false,FILESYSTEM_DISK=gcs,GCS_PROJECT_ID=mail-app-prod,GCS_BUCKET=${BUCKET_NAME},DB_CONNECTION=mysql" \
    --set-secrets "APP_KEY=APP_KEY:latest,DB_CONNECTION_URL=DB_CONNECTION_URL:latest,GCS_KEY_FILE=GCS_KEY_FILE:latest"

# Lấy URL
export APP_URL=$(gcloud run services describe mail-app \
    --platform managed \
    --region asia-southeast1 \
    --format "value(status.url)")

echo "✓ Deployed at: ${APP_URL}"
```

## Bước 6: Run Migrations (1 phút)

```bash
# Kết nối vào PlanetScale từ local
# Cài PlanetScale CLI
brew install planetscale/tap/pscale  # MacOS
# hoặc: curl -fsSL https://raw.githubusercontent.com/planetscale/cli/main/install.sh | sh

# Login và connect
pscale auth login
pscale connect mail-app main

# Trong terminal khác, chạy migrations
DB_CONNECTION_URL="mysql://user:pass@127.0.0.1:3306/mail-app?sslmode=disable" \
    php artisan migrate --force
```

## Bước 7: Test Application

```bash
# Mở trong browser
open ${APP_URL}

# Hoặc test bằng curl
curl -I ${APP_URL}/health
# Expect: HTTP/2 200
```

## Tổng kết

```bash
# Xem logs
gcloud run logs tail mail-app --region asia-southeast1

# Xem chi phí
open https://console.cloud.google.com/billing

# Update code (sau khi commit changes)
gcloud builds submit --tag gcr.io/mail-app-prod/mail-app && \
    gcloud run deploy mail-app \
        --image gcr.io/mail-app-prod/mail-app \
        --region asia-southeast1
```

## Chi phí dự kiến

- **Cloud Run**: $0.10 - $2/tháng (3 ngày sử dụng)
- **Cloud Storage**: $0.05/tháng (2GB)
- **PlanetScale**: $0 (free tier)
- **Tổng**: ~$0.15 - $2.05/tháng

## Troubleshooting

### Lỗi: "Permission denied"
```bash
# Re-authenticate
gcloud auth login
gcloud auth application-default login
```

### Lỗi: "Service not found"
```bash
# Check project
gcloud config get-value project

# Check region
gcloud run services list
```

### Lỗi: Database connection
```bash
# Test connection
pscale connect mail-app main

# Check secret
gcloud secrets versions access latest --secret=DB_CONNECTION_URL
```

### Container không start
```bash
# Check logs
gcloud run logs tail mail-app --region asia-southeast1

# Build lại
gcloud builds submit --tag gcr.io/mail-app-prod/mail-app
```

## Next Steps

- Setup custom domain: https://cloud.google.com/run/docs/mapping-custom-domains
- Setup CI/CD with GitHub: Xem file `DEPLOYMENT_GUIDE.md`
- Monitoring & Alerts: https://console.cloud.google.com/monitoring
