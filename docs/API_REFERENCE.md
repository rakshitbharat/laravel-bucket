# API Reference Guide

LaraBucket exposes two sets of API endpoints:
1. **Admin Dashboard APIs**: Used by the administration panel for bucket CRUD, folder creation, and file browsing.
2. **Client Adapter APIs**: Used by the Flysystem disk adapter to perform storage operations.

---

## 🔒 1. Admin Dashboard APIs

These endpoints are prefixed by `/api` (default) and authenticated via the `AuthenticateLaraBucketAdmin` middleware, which expects a Bearer token in the `Authorization` header.

### 🔑 Authentication (Login)
* **Endpoint**: `POST /api/auth/login`
* **Headers**: `Content-Type: application/json`
* **Request Body**:
  ```json
  {
    "email": "super@admin.com",
    "password": "password"
  }
  ```
* **Response (200 OK)**:
  ```json
  {
    "user": {
      "id": 1,
      "name": "Super Administrator",
      "email": "super@admin.com"
    },
    "token": "..." // Used as Bearer token for subsequent requests
  }
  ```

### 🪣 List Buckets
* **Endpoint**: `GET /api/buckets`
* **Headers**: `Authorization: Bearer <admin_token>`
* **Response (200 OK)**:
  ```json
  [
    {
      "id": 1,
      "name": "corporate-assets",
      "slug": "corporate-assets",
      "secret_key": "sk_live_...",
      "size_used": 1048576,
      "size_limit": 104857600,
      "is_active": true,
      "created_at": "2026-06-24T10:00:00.000000Z",
      "updated_at": "2026-06-24T10:15:00.000000Z"
    }
  ]
  ```

### 🪣 Create Bucket
* **Endpoint**: `POST /api/buckets`
* **Headers**: `Authorization: Bearer <admin_token>`, `Content-Type: application/json`
* **Request Body**:
  ```json
  {
    "name": "ecommerce-photos",
    "size_limit": 52428800 // In bytes (e.g. 50MB)
  }
  ```
* **Response (201 Created)**:
  ```json
  {
    "id": 2,
    "name": "ecommerce-photos",
    "slug": "ecommerce-photos",
    "secret_key": "sk_live_generated_key_abc123",
    "size_used": 0,
    "size_limit": 52428800,
    "is_active": true,
    "created_at": "2026-06-24T12:00:00.000000Z",
    "updated_at": "2026-06-24T12:00:00.000000Z"
  }
  ```

### 🪣 Update Bucket
* **Endpoint**: `PUT /api/buckets/{id}`
* **Headers**: `Authorization: Bearer <admin_token>`, `Content-Type: application/json`
* **Request Body**:
  ```json
  {
    "name": "ecommerce-photos-updated",
    "size_limit": 104857600 // In bytes (e.g. 100MB)
  }
  ```
* **Response (200 OK)**:
  ```json
  {
    "success": true,
    "bucket": {
      "id": 2,
      "name": "ecommerce-photos-updated",
      "slug": "ecommerce-photos-updated",
      "size_limit": 104857600,
      "size_used": 0
    }
  }
  ```

### 🪣 Delete Bucket
* **Endpoint**: `DELETE /api/buckets/{id}`
* **Headers**: `Authorization: Bearer <admin_token>`
* **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Bucket and all associated files deleted successfully"
  }
  ```

### 📁 Browse Bucket Files
* **Endpoint**: `GET /api/buckets/{bucketId}/files`
* **Headers**: `Authorization: Bearer <admin_token>`
* **Query Parameters**:
  * `path`: Optional folder path to filter contents (e.g., `images/avatars` or `/`).
* **Response (200 OK)**:
  ```json
  [
    {
      "name": "logos",
      "type": "folder",
      "path": "logos",
      "size": 0,
      "modified": 1782310122
    },
    {
      "name": "hero.jpg",
      "type": "file",
      "path": "hero.jpg",
      "size": 204857,
      "modified": 1782310155,
      "mime_type": "image/jpeg",
      "url": "https://storage.yourdomain.com/storage/corporate-assets/hero.jpg"
    }
  ]
  ```

### 📁 Create Dashboard Folder
* **Endpoint**: `POST /api/buckets/{bucketId}/folders`
* **Headers**: `Authorization: Bearer <admin_token>`, `Content-Type: application/json`
* **Request Body**:
  ```json
  {
    "name": "new-folder-name",
    "path": "parent-folder/sub-folder"
  }
  ```
* **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Folder created successfully"
  }
  ```

### 📁 Delete Dashboard File
* **Endpoint**: `DELETE /api/files/{fileId}`
* **Headers**: `Authorization: Bearer <admin_token>`
* **Query Parameters**:
  * `bucket_id`: Required ID of the bucket.
  * `path`: Required file path relative to the bucket.
* **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "File deleted successfully"
  }
  ```

---

## ⚡ 2. Client Adapter APIs

These endpoints are authenticated via `AuthenticateLaraBucketClient` middleware. 
The client must send the secret key either in the `X-API-KEY` header or as a Bearer token in the `Authorization` header.

### 🔍 Check File Existence
* **Endpoint**: `GET /api/files` or `HEAD /api/files`
* **Headers**: `X-API-KEY: sk_live_...`
* **Query Parameters**:
  * `path`: Required file path (e.g., `avatars/user_1.png`).
* **Response (200 OK)**:
  * Returns HTTP status `200` if the file exists.
  * Returns HTTP status `404` if the file does not exist.

### 📥 Download File Content
* **Endpoint**: `GET /api/files/download`
* **Headers**: `X-API-KEY: sk_live_...`
* **Query Parameters**:
  * `path`: Required file path.
* **Response (200 OK)**:
  * Serves the raw file content in the response body.

### 📤 Upload File
* **Endpoint**: `POST /api/buckets/{bucketSlug}/upload`
* **Headers**: `X-API-KEY: sk_live_...`
* **Body**: `multipart/form-data`
  * `file`: Binary file upload.
  * `path`: Optional destination folder path.
* **Response (200 OK)**:
  ```json
  {
    "success": true,
    "path": "avatars/user_1.png",
    "url": "https://storage.yourdomain.com/storage/my-bucket/avatars/user_1.png"
  }
  ```

### 🗑️ Delete File
* **Endpoint**: `DELETE /api/files`
* **Headers**: `X-API-KEY: sk_live_...`
* **Query Parameters**:
  * `path`: Required file path.
* **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "File deleted successfully"
  }
  ```

### ℹ️ Get File Metadata
* **Endpoint**: `GET /api/files/metadata`
* **Headers**: `X-API-KEY: sk_live_...`
* **Query Parameters**:
  * `path`: Required file path.
* **Response (200 OK)**:
  ```json
  {
    "success": true,
    "size": 1048576,
    "mime_type": "image/png",
    "last_modified": 1782310122
  }
  ```

### 📋 Copy File
* **Endpoint**: `POST /api/files/copy`
* **Headers**: `X-API-KEY: sk_live_...`, `Content-Type: application/json`
* **Request Body**:
  ```json
  {
    "source": "avatars/user_1.png",
    "destination": "avatars/backup_user_1.png"
  }
  ```
* **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "File copied successfully"
  }
  ```

---

## 🌐 3. Public File Serving

Files can be served publicly (without authentication credentials) if they are located inside a public bucket directory.

* **Endpoint**: `GET /storage/{path}`
* **Format**: `https://storage.yourdomain.com/storage/{bucketSlug}/{filePath}`
* **Example**: `https://storage.yourdomain.com/storage/marketing/logos/badge.svg`
* **Response**: Serves the raw file content with the correct MIME type dynamically resolved by the server. Supports HTTP range requests (essential for audio and video streaming).
