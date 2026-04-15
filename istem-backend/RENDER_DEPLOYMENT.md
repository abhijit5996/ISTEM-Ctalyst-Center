# Render Deployment Configuration Guide

## Critical: Environment Variables on Render Dashboard

Your `.env` file will NOT be used on Render. You must set all environment variables in the Render dashboard.

### Steps to Configure on Render:

1. Go to your Render service dashboard
2. Click **Environment** → **Environment Variables**
3. Add the following variables:

```
APP_NAME=ISTEM Catalyst
APP_ENV=production
APP_KEY=base64:VvLIl9GDmsUdT2I/j8MEkGKgVsolVS0wmKk+qQ0csf4=
APP_DEBUG=false
APP_URL=https://istem-ctalyst-center.onrender.com

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

SESSION_DRIVER=database
SESSION_LIFETIME=120
```

### Critical Settings Explained:

- **DB_HOST**: `mainline.proxy.rlwy.net` (not `127.0.0.1`)
- **DB_SSL**: `true` (Railway requires SSL)
- **APP_ENV**: `production` (not `local`)
- **APP_DEBUG**: `false` (never true in production)

### After Adding Variables:

1. Click **Save**
2. The service will automatically redeploy
3. Check logs to verify database connection works

### Verify It Works:

1. Make a test API request:

```bash
curl https://istem-ctalyst-center.onrender.com/api/signup \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@example.com","password":"password123"}'
```

Should return `201` with OTP sent message (not 500).

### Troubleshooting:

- **Still getting 500 errors**: Check Render logs for database errors
- **Connection refused**: Verify Railway database is running and credentials are correct
- **SSL certificate errors**: Try removing `DB_SSL=true` and use `DB_SSL=false`

---

**Last Updated**: April 9, 2026
