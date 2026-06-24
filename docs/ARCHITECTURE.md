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

## Frontend Architecture (Blade & Alpine.js)

The administration panel is served as a server-rendered Blade template, augmented by Alpine.js for lightweight client-side state management and reactivity.

- **`dashboard.blade.php`**: The single, unified management console view styled with custom glassmorphism and ambient glow effects.
- **Alpine.js State**: Handles UI transitions, modals (Create/Edit Bucket, Connect Credentials), slide-overs, grid/list view toggles, folder creation, and double-click navigation.
- **SVG Charts**: Renders real-time, responsive storage utilization bars dynamically using SVG paths.
- **Lucide Icons**: Styled via SVG inline graphics for icons.
- **Toast Notifications**: Built in Alpine.js with a CSS animated decay progress bar (linear width decay over 5s) for real-time success/error alerts.
