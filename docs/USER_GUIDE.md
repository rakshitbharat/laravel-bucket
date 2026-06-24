# User Guide

## Logging In
Access the dashboard using the Super Admin credentials.
- **Default Email**: `super@admin.com`

## Dashboard Overview
The main dashboard provides a high-level view of your system health:
- **Total Buckets**: Number of active storage containers.
- **Storage Utilization**: A bar chart showing used space vs. allocated limits for each bucket.

## Managing Buckets

### Creating a Bucket
1. Click the **"Create Bucket"** button in the top right.
2. **Name**: Enter a unique name (e.g., `marketing-assets`).
3. **Limit**: Set the maximum storage size in MB.
4. **Reference Email**: Enter the email of the person responsible for this bucket (for reference only).
5. **Secret Key**: Auto-generated. This key acts as the password for API access.
6. Click **Create**.

### Editing & Deleting
- **Edit**: Click the pencil icon to change the name, limit, or regenerate the secret key.
- **Delete**: Click the trash icon. **Warning:** This will permanently delete the bucket and ALL files inside it.

### Connecting
Click the **"Connect"** button on any bucket row to view specific `.env` configurations for that bucket.

## File Browser
Click **"Browse"** on any bucket to open the file manager.

- **Navigation**: Use the breadcrumb bar at the top to navigate back to parent folders.
- **Upload**: Click the "Upload" button or drag and drop files onto the browser area.
- **Folders**: Click "New Folder" to organize content.
- **View Modes**: Toggle between Grid (thumbnails) and List (details) views.
- **Context Actions**: Click a file to select it.
  - **Get URL**: Copies the public access link to your clipboard.
  - **Delete**: Removes the file.
