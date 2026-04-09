# CORS Configuration Guide - ISTEM Catalyst Center

## 📋 Overview

This guide explains the CORS (Cross-Origin Resource Sharing) setup for the ISTEM Catalyst Center project to allow the frontend (React) to successfully communicate with the backend (Laravel) API, especially for the Bulk CSV Import feature.

---

## 🎯 Current Setup

### URLs

- **Backend API**: `https://istem-ctalyst-center.onrender.com`
- **Frontend**: `https://istem-ctalyst-center-1.onrender.com`
- **Local Dev Backend**: `http://localhost:8000`
- **Local Dev Frontend**: `http://localhost:5173`

---

## 🔧 Backend Configuration

### 1. CORS Package Installation

The backend uses `fruitcake/laravel-cors` package. It's already added to `composer.json`:

```json
"require": {
    "fruitcake/laravel-cors": "^3.0"
}
```

**Installation Command:**

```bash
composer install
```

### 2. CORS Configuration File

**Location:** `config/cors.php`

**Current Configuration:**

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',      // Vite dev server
        'http://localhost:8080',      // Alternative dev
        'http://localhost:3000',      // Node dev
        'https://istem-ctalyst-center-1.onrender.com', // Production
        env('FRONTEND_URL', 'http://localhost:5173'),
    ],

    'allowed_origins_patterns' => [
        '#https?://.*\.onrender\.com#',    // Render deployments
        '#https?://.*\.vercel\.app#',      // Vercel deployments
        '#https?://.*\.netlify\.app#',     // Netlify deployments
    ],

    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

### 3. Middleware Registration

**Location:** `bootstrap/app.php`

The CORS middleware is registered globally:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->use([
        HandleCors::class,
    ]);
})
```

### 4. Environment Variables

**Location:** `.env`

```env
# Local Development
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173

# Production on Render (set in Render Dashboard)
APP_URL=https://istem-ctalyst-center.onrender.com
FRONTEND_URL=https://istem-ctalyst-center-1.onrender.com
```

---

## 🎨 Frontend Configuration

### 1. Axios Setup

**Location:** `src/api/axios.js`

```javascript
const API = axios.create({
  baseURL: BACKEND_URL,
  headers: {
    Accept: "application/json",
  },
  timeout: 30000,
  withCredentials: false, // ⚠️ Important: false because we use Bearer tokens
});
```

**Key Points:**

- ✅ `withCredentials: false` - Uses Bearer token authentication, not cookies
- ✅ `Accept: "application/json"` - Tells server we accept JSON responses
- ✅ `baseURL` automatically uses `VITE_API_URL` from environment

### 2. Environment Variables

**Location:** `catalyst-connect/.env`

```env
VITE_API_URL=https://istem-ctalyst-center.onrender.com/api
```

This is used automatically during build.

### 3. Import Service

**Location:** `src/api/services/instrumentService.js`

The `importInstruments()` function correctly handles multipart form data:

```javascript
export const importInstruments = (csvFile, images) => {
  const formData = new FormData();
  formData.append("file", csvFile);

  images.forEach((img) => {
    formData.append("images[]", img);
  });

  return API.post("/instruments/import", formData, {
    headers: {
      "Content-Type": "multipart/form-data", // Browser handles this
    },
  });
};
```

---

## 🚀 Deployment Steps (Render)

### Backend (istem-backend)

1. **Install Dependencies:**

   ```bash
   composer install
   ```

2. **Set Environment Variables in Render Dashboard:**
   - `APP_URL=https://istem-ctalyst-center.onrender.com`
   - `FRONTEND_URL=https://istem-ctalyst-center-1.onrender.com`
   - `APP_ENV=production`
   - `APP_DEBUG=false`

3. **Run Build Commands:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan migrate --force
   php artisan storage:link
   ```

### Frontend (catalyst-connect)

1. **Build with Environment Variables:**

   ```bash
   VITE_API_URL=https://istem-ctalyst-center.onrender.com/api npm run build
   ```

2. **Deploy:**
   - Render automatically deploys after build

---

## 🧪 Debugging CORS Issues

### 1. Check Browser Network Tab

Open Developer Tools → Network tab and make an API call:

**Look for:**

- ✅ Request header: `Origin: https://istem-ctalyst-center-1.onrender.com`
- ✅ Response header: `Access-Control-Allow-Origin: https://istem-ctalyst-center-1.onrender.com`
- ✅ OPTIONS request (preflight) should return 200

### 2. If CORS Error Persists

**A. Verify Exact Frontend URL**

```javascript
// In browser console
console.log(location.origin); // Should be exact frontend URL
```

**B. Check Backend Logs**

```bash
tail -f storage/logs/laravel.log
```

**C. Clear All Caches**

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan optimize:clear
```

**D. Test with curl**

```bash
curl -X OPTIONS https://istem-ctalyst-center.onrender.com/api/instruments/import \
  -H "Origin: https://istem-ctalyst-center-1.onrender.com" \
  -v
```

Should return `Access-Control-Allow-Origin` header.

---

## 📝 Common Issues & Solutions

### Issue 1: "No 'Access-Control-Allow-Origin' header"

**Cause:** CORS middleware not registered or frontend URL not in allowed list

**Solution:**

1. Verify `bootstrap/app.php` has `HandleCors::class` registered
2. Check `config/cors.php` includes your frontend URL
3. Restart backend server: `php artisan serve`

### Issue 2: OPTIONS request fails with 404

**Cause:** Routes not configured or middleware not running before route matching

**Solution:**

1. Ensure middleware is registered globally, not just in routes
2. Verify `config/cors.php` has `'paths' => ['api/*', ...]`

### Issue 3: CORS works locally but not on Render

**Cause:** Environment variables not set on Render

**Solution:**

1. Go to Render Dashboard
2. Add environment variables:
   - `FRONTEND_URL=https://istem-ctalyst-center-1.onrender.com`
   - `APP_URL=https://istem-ctalyst-center.onrender.com`
3. Redeploy

### Issue 4: Image upload works but CORS still fails

**Cause:** `supports_credentials: true` with `withCredentials: false`

**Solution:**

- Keep `supports_credentials: false` in `config/cors.php`
- Keep `withCredentials: false` in axios.js
- Use Bearer token authentication instead

---

## 🔒 Security Best Practices

### DO ✅

- ✅ Explicitly list allowed origins (not wildcard in production)
- ✅ Use HTTPS in production
- ✅ Set `supports_credentials: false` unless cookies needed
- ✅ Use Bearer token authentication
- ✅ Keep `max_age: 0` for strict CORS checks

### DON'T ❌

- ❌ Don't use `'allowed_origins' => ['*']` in production
- ❌ Don't set `supports_credentials: true` without explicit origins
- ❌ Don't expose sensitive headers unnecessarily
- ❌ Don't use `withCredentials: true` with Bearer tokens

---

## 📚 API Endpoint Test

### Test Import Endpoint with curl

```bash
# Create a test CSV file
echo "instrument_name,category,location,usage_cost,status,description,image
Test Microscope,Scientific,Lab-1,1000,available,Test instrument,test.jpg" > test.csv

# Create a test image
touch test.jpg

# Make request
curl -X POST https://istem-ctalyst-center.onrender.com/api/instruments/import \
  -H "Origin: https://istem-ctalyst-center-1.onrender.com" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@test.csv" \
  -F "images[]=@test.jpg"
```

---

## 📞 Support

If CORS issues persist:

1. Check Render logs: `curl https://istem-ctalyst-center.onrender.com/up`
2. Verify URLs match exactly (no trailing slashes)
3. Clear browser cache: Ctrl+Shift+Delete
4. Try incognito mode (bypass cache)
5. Check network with `withCredentials: false`

---

## ✅ Verification Checklist

- [ ] `config/cors.php` has correct frontend URL
- [ ] `bootstrap/app.php` imports `HandleCors` and registers it
- [ ] `composer.json` has `fruitcake/laravel-cors` in require
- [ ] `axios.js` has `withCredentials: false`
- [ ] `.env` file has `FRONTEND_URL` set correctly
- [ ] Render environment variables are set
- [ ] API routes are in `routes/api.php` (not `web.php`)
- [ ] `php artisan config:clear` and cache clear commands run on Render
- [ ] Browser Network tab shows correct CORS headers

---

**Last Updated:** April 9, 2026
**Version:** 1.0
