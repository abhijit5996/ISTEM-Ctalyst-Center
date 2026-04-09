# 🔧 Fix 500 Error - Action Plan

## Problem

Your Render backend is getting 500 errors because:

- ❌ Cannot connect to database (trying 127.0.0.1 instead of Railway host)
- ❌ Environment variables not set on Render dashboard

## Solution

### Step 1: Update Render Environment Variables ⚠️ CRITICAL

Go to your Render dashboard for `istem-ctalyst-center`:

1. Click **Environment**
2. Set these variables (copy-paste them):

```
APP_NAME=ISTEM Catalyst
APP_ENV=production
APP_KEY=base64:VvLIl9GDmsUdT2I/j8MEkGKgVsolVS0wmKk+qQ0csf4=
APP_DEBUG=false
APP_URL=https://istem-catalyst-center.onrender.com

DB_CONNECTION=mysql
DB_HOST=mainline.proxy.rlwy.net
DB_PORT=22047
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=dUPJOlSpKwbBuHPQJgYfLReNuHAtvzSV
DB_SSL=true

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=abhijitdasRahul010304@gmail.com
MAIL_PASSWORD=mtjzoidxxbjtplwh
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=abhijitdasRahul010304@gmail.com
MAIL_FROM_NAME=ISTEM Catalyst

LOG_CHANNEL=stack
LOG_LEVEL=error
```

3. Click **Save** button
4. Render will auto-redeploy your service

### Step 2: Test After Deployment

Wait 2-3 minutes for redeploy to complete, then test:

```bash
curl -X POST https://istem-catalyst-center.onrender.com/api/signup \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123"
  }'
```

**Expected Response**: `201` with message "otp_sent" ✅

### Step 3: Test Frontend Signup

1. Go to `https://istem-catalyst-center.onrender.com/signup`
2. Fill in form and click "Sign up"
3. Should see "OTP sent to your email" message ✅

---

## Files Already Updated Locally

✅ `.env` - Production configuration  
✅ CORS config - Fixed domain  
✅ Signup component - Better error messages  
✅ Axios - Enhanced error logging

## What NOT to Do

❌ Don't rely on `.env` file on Render - use dashboard vars only  
❌ Don't use `APP_DEBUG=true` in production  
❌ Don't commit `.env` to git - it has passwords

---

**If you still get 500 errors after Step 1:**

- Check Render logs for database connection errors
- Verify Railway database is running (check Railway dashboard)
- Try toggling `DB_SSL` between `true` and `false`
