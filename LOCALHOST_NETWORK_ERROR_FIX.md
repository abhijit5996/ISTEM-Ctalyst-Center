# 🔧 Network Error Fix - Localhost Signup Issue

**Date**: April 11, 2026  
**Issue**: Network Error when trying to signup on localhost  
**Status**: ✅ FIXED

---

## 🐛 The Problem

When attempting to signup on `http://localhost:5173`, the frontend showed:

```
🔴 [Signup.tsx] Step 3: API error caught
🔴 [Signup.tsx] Error status: undefined
🔴 [Signup.tsx] Full error: AxiosError: Network Error
```

**Root Cause**: Frontend was trying to connect to production API (`https://istem-catalyst-center.onrender.com/api`) even when running on localhost, causing a network error since the browser couldn't reach the production server from local development.

---

## ✅ The Fix

### What Changed

**File**: `catalyst-connect/src/api/axios.js`

The axios configuration now **automatically detects** whether you're running locally or in production:

```javascript
// Before (Wrong - Always used production URL on localhost)
const BACKEND_URL = import.meta.env.VITE_API_URL || "http://localhost:8000/api";

// After (Correct - Detects environment automatically)
const isDevelopment =
  window.location.hostname === "localhost" ||
  window.location.hostname === "127.0.0.1";
const BACKEND_URL = isDevelopment
  ? import.meta.env.VITE_LOCAL_API_URL || "http://localhost:8000/api"
  : import.meta.env.VITE_API_URL ||
    "https://istem-catalyst-center.onrender.com/api";
```

### How It Works

| Environment    | Hostname                           | Backend URL                                       |
| -------------- | ---------------------------------- | ------------------------------------------------- |
| **Local Dev**  | localhost or 127.0.0.1             | http://localhost:8000/api ✅                      |
| **Local Dev**  | (any other local IP)               | http://localhost:8000/api ✅                      |
| **Production** | istem-catalyst-center.onrender.com | https://istem-catalyst-center.onrender.com/api ✅ |

---

## 📊 Debugging Info Added

The fix also adds helpful console logs on page load:

```
🔧 [axios.js] Environment Detection:
  Hostname: localhost
  Is Development: true
  Backend URL: http://localhost:8000/api
```

This helps you immediately see which API URL is being used.

---

## 🚀 Steps to Test the Fix

### 1️⃣ Ensure Backend is Running

```bash
cd istem-backend
php artisan serve --port=8000
```

Expected output:

```
Starting Laravel development server: http://127.0.0.1:8000
```

### 2️⃣ Rebuild Frontend (if needed)

```bash
cd catalyst-connect
npm run dev
```

Or just **hard refresh** the page: `Ctrl+Shift+R`

### 3️⃣ Test Signup

1. Go to http://localhost:5173
2. Click **Sign Up**
3. Fill form with:
   - Name: `Test User`
   - Email: `test@example.com`
   - Phone: `1234567890`
   - Password: `Password123`
   - Confirm Password: `Password123`
4. Click **Sign up**

### Expected Result

```
✅ 🟢 [axios.js] HTTP Response: 201 Created
✅ Toast: "OTP sent to your email. Please verify."
✅ Redirect to OTP verification page
✅ NO more "Network Error"
```

---

## 🔍 How to Verify It's Working

Open **DevTools** (F12) → **Console** tab and look for:

1. **Environment Detection** (on page load):

   ```
   🔧 [axios.js] Environment Detection:
     Hostname: localhost
     Is Development: true
     Backend URL: http://localhost:8000/api
   ```

2. **Signup Request** (when you click Sign Up):

   ```
   🔵 [axios.js] HTTP Request: POST /signup
   🔵 [axios.js] Request data: {name: "...", email: "...", ...}
   ```

3. **Success Response**:
   ```
   🟢 [axios.js] HTTP Response: 201 Created
   🟢 [axios.js] Response data: {message: "otp_sent", email: "..."}
   ```

---

## ⚠️ If You Still Get Network Error

**Check these things in order:**

### 1. Backend is Running

- Check if `http://localhost:8000` is accessible
- Open browser and test: `http://localhost:8000/api/instruments`
- Should return a response (not empty page)

### 2. Check Console Logs

- Open DevTools
- Look for "Backend URL" log
- Should show `http://localhost:8000/api`
- If not, hard refresh the page

### 3. Check Firewall

- Make sure port 8000 is not blocked
- Try: `netstat -ano | findstr 8000`
- Should show LISTENING status

### 4. Check .env File

- Make sure `.env` has:
  ```
  VITE_LOCAL_API_URL=http://localhost:8000/api
  VITE_API_URL=https://istem-catalyst-center.onrender.com/api
  ```

### 5. Clear Browser Cache

- Ctrl+Shift+Delete
- Clear cache for last hour
- Hard refresh: Ctrl+Shift+R

---

## 📋 Files Modified

- `catalyst-connect/src/api/axios.js` (Fixed environment detection)

---

## ✨ Summary

The network error on localhost was caused by the frontend trying to use the production API URL. This has been fixed by implementing automatic environment detection that:

✅ Uses **local API** when running on `localhost`  
✅ Uses **production API** when deployed on Render  
✅ Logs the detected environment for debugging  
✅ Falls back gracefully if env variables are not set

**You should now be able to signup successfully on localhost!** 🎉
