# LaraBucket

**LaraBucket** is a self-hosted object storage management solution designed to provide an S3-compatible experience for Laravel applications. It allows you to manage multiple "Buckets" (storage containers) through a modern, responsive web interface.

## 🚀 Features

- **Single Super Admin**: Centralized control panel for all storage operations.
- **Bucket Management**: Create, rename, resize, and delete storage buckets.
- **File Browser**:
  - Drag & Drop Uploads
  - Folder Management (Create/Delete)
  - Grid & List Views
  - Public URL Generation
- **Visual Analytics**: Real-time visualization of storage usage vs limits.
- **Seamless Laravel Integration**: Zero-code integration for client apps using standard Laravel Storage facades.

## 🛠 Tech Stack

- **Frontend**: React 18, TypeScript, Vite
- **Styling**: Tailwind CSS
- **Icons**: Lucide React
- **Charts**: Recharts
- **State/Mock**: Local mock services (for demo/development)

## 📦 Quick Start

1. **Install Dependencies**: `npm install`
2. **Run Development Server**: `npm run dev`
3. **Login**:
   - Email: `super@admin.com`
   - Password: `password` (or click "Auto-fill Admin Credentials")

## 📖 Documentation

- [User Guide](./USER_GUIDE.md) - How to manage buckets and files.
- [Laravel Integration](./LARAVEL_INTEGRATION.md) - Connecting your Laravel apps (includes Adapter code).
- [API Reference](./API_REFERENCE.md) - Backend API contract.
- [Deployment](./DEPLOYMENT.md) - Build and hosting instructions.
- [Architecture](./ARCHITECTURE.md) - Under the hood details.
