# Architecture & Design

## System Structure

LaraBucket operates as a central storage authority.

1.  **The Server (LaraBucket)**:
    - Hosts the physical files in `storage/app/public/{bucket_name}`.
    - Exposes a REST API for file operations (`/upload`, `/delete`, `/list`).
    - Authenticates requests using Bearer tokens (The "Secret Key").

2.  **The Client (Your App)**:
    - Connects via HTTP.
    - Uses the `larabucket` filesystem driver to abstract API calls.

## Security Model

- **Single Admin**: Only one super-user controls the infrastructure.
- **Bucket Isolation**:
    - Each bucket has a unique `secretKey`.
    - API requests must include this key in the Authorization header.
    - Clients can only access the bucket matching their provided credentials.

## Frontend Architecture (React)

The UI is built as a Single Page Application (SPA).

- **`App.tsx`**: Main router. Handles high-level navigation between the Dashboard and File Browser.
- **`services/mockService.ts`**: Currently simulates backend latency and database operations. In production, this service would make `fetch()` calls to the Laravel Backend API.
- **`components/FileBrowser.tsx`**: A complex stateful component managing:
    - Path navigation (`currentPath` state).
    - File selection/operations.
    - View modes (Grid/List).
- **`views/AdminDashboard.tsx`**: manages CRUD for buckets and displays analytics charts.
