# API Reference

This document outlines the REST API contract expected by the LaraBucket frontend. The backend implementation should adhere to these endpoints to ensure full compatibility with the UI.

## Authentication

### Login
**POST** `/api/auth/login`

Returns a session token or cookie.

**Request:**
```json
{
  "email": "super@admin.com",
  "password": "password"
}
```

**Response:**
```json
{
  "user": {
    "id": "u1",
    "name": "Super Administrator",
    "email": "super@admin.com"
  },
  "token": "..." // Optional if using Sanctum/Cookies
}
```

---

## Buckets

### List Buckets
**GET** `/api/buckets`

**Response:**
```json
[
  {
    "id": "b1",
    "name": "corporate-assets",
    "ownerEmail": "admin@corp.com",
    "storageLimitMb": 1000,
    "storageUsedMb": 450,
    "createdAt": "2023-10-15T10:00:00Z"
  }
]
```

### Create Bucket
**POST** `/api/buckets`

**Request:**
```json
{
  "name": "new-bucket",
  "ownerEmail": "client@example.com",
  "storageLimitMb": 500,
  "secretKey": "optional-custom-key"
}
```

### Update Bucket
**PUT** `/api/buckets/{id}`

**Request:**
```json
{
  "name": "updated-name",
  "storageLimitMb": 2000,
  "ownerEmail": "new-email@example.com",
  "secretKey": "new-secret-key"
}
```

### Delete Bucket
**DELETE** `/api/buckets/{id}`

Deletes the bucket and **all** contained files.

---

## File Operations

### List Files
**GET** `/api/buckets/{bucketId}/files`

**Query Parameters:**
- `path`: (string) The folder path to list (e.g., `/` or `/images/`).

**Response:**
```json
[
  {
    "id": "f1",
    "bucketId": "b1",
    "name": "vacation",
    "type": "folder",
    "size": 0,
    "path": "/",
    "updatedAt": "..."
  },
  {
    "id": "f2",
    "bucketId": "b1",
    "name": "photo.jpg",
    "type": "file",
    "size": 102400,
    "mimeType": "image/jpeg",
    "path": "/",
    "updatedAt": "..."
  }
]
```

### Upload File
**POST** `/api/buckets/{bucketId}/upload`

**Body:** `multipart/form-data`
- `file`: (Binary) The file content.
- `path`: (string) The target folder path.

### Create Folder
**POST** `/api/buckets/{bucketId}/folders`

**Request:**
```json
{
  "name": "new-folder-name",
  "path": "/current/path/"
}
```

### Delete File
**DELETE** `/api/files/{fileId}`
