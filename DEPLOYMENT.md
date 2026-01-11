# Deployment Guide

LaraBucket consists of two parts:
1. **The React Frontend** (this repository).
2. **The Laravel Backend** (API & Storage Engine).

## Frontend Deployment

### 1. Build the Application
To generate the production-ready static files:

```bash
npm run build
```

This will create a `dist/` directory containing the compiled HTML, CSS, and JavaScript.

### 2. Serving the Frontend
You have two main options for serving the frontend:

**Option A: Separate Host (Recommended)**
- Upload the contents of `dist/` to a static host like Vercel, Netlify, or an S3 bucket.
- Ensure you configure CORS on your Laravel backend to allow requests from your frontend domain.

**Option B: Inside Laravel**
- Copy the contents of `dist/` into your Laravel application's `public/` directory (or a subdirectory like `public/app`).
- Set up a Laravel route to serve the `index.html` for the dashboard path.

```php
// routes/web.php
Route::get('/admin/{any?}', function () {
    return file_get_contents(public_path('app/index.html'));
})->where('any', '.*');
```

## Environment Configuration

In production, you must point the frontend to your real Laravel API.

1. Create a `.env.production` file in the root of the React project.
2. Define the API URL:

```env
VITE_API_BASE_URL=https://your-laravel-api.com/api
```

*(Note: You will need to update `services/mockService.ts` to use `fetch` and this environment variable instead of returning mock data.)*
