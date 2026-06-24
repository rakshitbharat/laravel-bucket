#!/bin/bash
BASE_URL="http://localhost:8000/api"
ADMIN_EMAIL="super@admin.com"
ADMIN_PASSWORD="password"

echo "=== LaraBucket API Test Shell Script ==="

# 1. Login
echo -e "\n[1] Authenticating as Admin..."
LOGIN_RESP=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$ADMIN_EMAIL\",\"password\":\"$ADMIN_PASSWORD\"}")

TOKEN=$(echo "$LOGIN_RESP" | grep -o '"token":"[^"]*' | grab_value)
# Fallback if grab_value isn't a utility, let's use sed
if [ -z "$TOKEN" ]; then
  TOKEN=$(echo "$LOGIN_RESP" | sed -n 's/.*"token":"\([^"]*\)".*/\1/p')
fi

if [ -z "$TOKEN" ]; then
  echo "FAILED to login. Response:"
  echo "$LOGIN_RESP"
  exit 1
fi

echo "SUCCESS: Logged in! Admin Token: ${TOKEN:0:15}..."

# 2. Check Buckets
echo -e "\n[2] Checking allocated namespaces..."
BUCKETS_RESP=$(curl -s -X GET "$BASE_URL/buckets" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Buckets List:"
echo "$BUCKETS_RESP"

# Let's extract test-bucket ID and Key
BUCKET_ID=$(echo "$BUCKETS_RESP" | sed -n 's/.*"id":"\([0-9]*\)","name":"test-bucket".*/\1/p')
BUCKET_KEY=$(echo "$BUCKETS_RESP" | sed -n 's/.*"name":"test-bucket"[^}]*"secretKey":"\([^"]*\)".*/\1/p')

if [ -z "$BUCKET_KEY" ]; then
  # Try to find bucket slug
  BUCKET_KEY=$(echo "$BUCKETS_RESP" | sed -n 's/.*"slug":"test-bucket"[^}]*"secretKey":"\([^"]*\)".*/\1/p')
  BUCKET_ID=$(echo "$BUCKETS_RESP" | sed -n 's/.*"id":"\([^"]*\)","name":"test-bucket".*/\1/p')
fi

if [ -z "$BUCKET_KEY" ]; then
  echo "Creating namespace 'test-bucket'..."
  CREATE_RESP=$(curl -s -X POST "$BASE_URL/buckets" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"name":"test-bucket","ownerEmail":"test@larabucket.com","storageLimitMb":500}')
  
  echo "Create Response: $CREATE_RESP"
  BUCKET_ID=$(echo "$CREATE_RESP" | sed -n 's/.*"id":"\([^"]*\)".*/\1/p')
  BUCKET_KEY=$(echo "$CREATE_RESP" | sed -n 's/.*"secretKey":"\([^"]*\)".*/\1/p')
fi

echo "Active Bucket ID: $BUCKET_ID"
echo "Active Bucket API Key: $BUCKET_KEY"

# 3. Create dummy file
echo -e "\n[3] Creating a dummy image/file..."
echo "LaraBucket Test Image Content" > dummy_test.png
echo "Created dummy_test.png"

# 4. Upload file using X-API-KEY
echo -e "\n[4] Uploading file using Client Upload API..."
UPLOAD_RESP=$(curl -s -X POST "$BASE_URL/buckets/test-bucket/upload" \
  -H "X-API-KEY: $BUCKET_KEY" \
  -H "Accept: application/json" \
  -F "file=@dummy_test.png" \
  -F "path=/")

echo "Upload Response:"
echo "$UPLOAD_RESP"

# 5. List Files in Bucket
echo -e "\n[5] Listing files in test-bucket..."
FILES_RESP=$(curl -s -X GET "$BASE_URL/buckets/$BUCKET_ID/files?path=/" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Files inside test-bucket:"
echo "$FILES_RESP"

# Extract file id
FILE_ID=$(echo "$FILES_RESP" | sed -n 's/.*"id":"\([^"]*\)".*/\1/p')

if [ -n "$FILE_ID" ]; then
  # 6. Verify Download
  echo -e "\n[6] Verifying file download..."
  DOWNLOAD_URL=$(echo "$UPLOAD_RESP" | sed -n 's/.*"url":"\([^"]*\)".*/\1/p' | sed 's/\\//g')
  echo "Downloading from $DOWNLOAD_URL"
  DOWNLOAD_RESP=$(curl -s -o downloaded_test.png -w "%{http_code}" "$DOWNLOAD_URL")
  echo "Download HTTP Status: $DOWNLOAD_RESP"
  if [ "$DOWNLOAD_RESP" == "200" ]; then
    echo "SUCCESS: File downloaded! Content:"
    cat downloaded_test.png
  else
    echo "FAILED to download"
  fi
fi

# Clean up
rm -f dummy_test.png downloaded_test.png
echo -e "\n=== LaraBucket API Test Completed ==="
