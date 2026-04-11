# 🐛 Signup Process Debugging Guide

## Complete Setup for Tracing User Signup Flow

All files have been enhanced with step-by-step debugging logs. Use this guide to track issues through the entire signup process.

---

## 📍 Frontend Debugging Points

### 1. **[src/pages/Signup.tsx](src/pages/Signup.tsx)** - Form & Submission

**Logs:**

- 🔵 Step 1: Form submission started
- 🔵 Step 2: Validation passed → calling API
- 🟢 Step 3: API response received
- 🟢 Step 4: OTP sent successfully → navigating to verify page
- 🔴 Step 3: API error caught (with status & response data)

**How to View:**

1. Open DevTools → Console tab
2. Fill signup form and submit
3. Look for logs starting with `[Signup.tsx]`

**Example Output:**

```
🔵 [Signup.tsx] Step 1: Form submission started
🔵 [Signup.tsx] Form data: {name: "John", email: "john@example.com", phone: "", password: "***"}
🔵 [Signup.tsx] Step 2: Validation passed, calling signup API
```

---

### 2. **[src/api/services/authService.js](src/api/services/authService.js)** - API Service Layer

**Logs:**

- 🔵 signup() called (with data)
- 🟢 signup() response received
- 🔴 signup() error caught

**How to View:**

1. DevTools → Console
2. Search for `[authService.js]` logs

**Example Output:**

```
🔵 [authService.js] signup() called with data: {...}
🟢 [authService.js] signup() response: {message: "otp_sent", email: "john@example.com"}
```

---

### 3. **[src/api/axios.js](src/api/axios.js)** - HTTP Layer

**Logs (Request Interceptor):**

- 🔵 HTTP Request method and URL
- 🔵 Request data payload
- 🔵 Request headers
- 🔵 Auth token added (if present)

**Logs (Response Interceptor):**

- 🟢 HTTP Response status and status text
- 🟢 Response data
- 🔴 HTTP error status
- 🔴 Error response data
- 🔴 Timeout/CORS/Network errors

**How to View:**

1. DevTools → Console
2. Search for `[axios.js]` logs

**Example Output:**

```
🔵 [axios.js] HTTP Request: POST /signup
🔵 [axios.js] Request data: {name: "John", email: "john@example.com", ...}
🟢 [axios.js] HTTP Response: 201 Created
🟢 [axios.js] Response data: {message: "otp_sent", email: "john@example.com"}
```

---

## 📍 Backend Debugging Points

### 4. **[app/Http/Controllers/AuthController.php](istem-backend/app/Http/Controllers/AuthController.php)** - Main Signup Logic

**Logs:**

- 🔵 Step 1: signup() called (with request data)
- 🟢 Step 2: Validation passed (with validated data)
- 🟢 Step 3: User created in database (with user ID & email)
- 🟢 Step 4: OTP sent successfully

**How to View:**

1. Local: `tailf storage/logs/laravel.log`
2. Production (Render): Check Render's built-in log viewer

**Example Output:**

```
[2026-04-09 15:30:45] local.INFO: 🔵 [AuthController] Step 1: signup() called
[2026-04-09 15:30:45] local.INFO: 🔵 [AuthController] Request data: {"name":"John","email":"john@example.com",...}
[2026-04-09 15:30:45] local.INFO: 🟢 [AuthController] Step 2: Validation passed
[2026-04-09 15:30:46] local.INFO: 🟢 [AuthController] Step 3: User created in database
[2026-04-09 15:30:46] local.INFO: 🟢 [AuthController] Step 4: OTP sent successfully
```

---

### 5. **sendOtpForUser() Private Method** - OTP Generation & Sending

**Logs:**

- 🔵 sendOtpForUser() called (with user email)
- 🔵 Generated OTP (6-digit code, user_id)
- 🟢 OTP saved to database (with expiry time)
- 🔵 Sending OTP email
- 🟢 OTP email sent successfully
- 🔴 Failed to send OTP email (error message)

**Example Output:**

```
[2026-04-09 15:30:46] local.INFO: 🔵 [AuthController] sendOtpForUser() called for user: {"email":"john@example.com"}
[2026-04-09 15:30:46] local.INFO: 🔵 [AuthController] Generated OTP: {"otp":"123456","user_id":1}
[2026-04-09 15:30:46] local.INFO: 🟢 [AuthController] OTP saved to database
[2026-04-09 15:30:46] local.INFO: 🔵 [AuthController] Sending OTP email to: {"email":"john@example.com"}
[2026-04-09 15:30:47] local.INFO: 🟢 [AuthController] OTP email sent successfully
```

---

### 6. **[app/Mail/OTPMail.php](istem-backend/app/Mail/OTPMail.php)** - Email Class

**Logs:**

- 🔵 OTPMail constructor called (with masked OTP)
- 🔵 build() called - preparing email

**Example Output:**

```
[2026-04-09 15:30:46] local.INFO: 🔵 [OTPMail] Constructor called {"otp":"123***"}
[2026-04-09 15:30:46] local.INFO: 🔵 [OTPMail] build() called - preparing email
```

---

## 🔍 Complete Signup Flow Trace

### Successful Signup Flow (Happy Path)

```
FRONTEND
  └─ User submits form
  └─ 🔵 [Signup.tsx] Step 1: Form submission started
  └─ 🔵 [Signup.tsx] Step 2: Validation passed, calling signup API
  └─ 🔵 [authService.js] signup() called with data
  └─ 🔵 [axios.js] HTTP Request: POST /signup
  └─ 🔵 [axios.js] Request data: {...}

NETWORK
  └─ HTTP POST request sent to backend

BACKEND
  └─ 🔵 [AuthController] Step 1: signup() called
  └─ 🔵 [AuthController] Step 2: Validation passed
  └─ 🔵 [AuthController] Step 3: User created in database
  └─ 🔵 [AuthController] sendOtpForUser() called
  └─ 🔵 [AuthController] Generated OTP
  └─ 🟢 [AuthController] OTP saved to database
  └─ 🔵 [OTPMail] Constructor called
  └─ 🔵 [OTPMail] build() called
  └─ 🟢 [AuthController] OTP email sent successfully
  └─ Returns 201 response

NETWORK
  └─ HTTP 201 response with {message: "otp_sent", email: "..."}

FRONTEND
  └─ 🟢 [axios.js] HTTP Response: 201 Created
  └─ 🟢 [axios.js] Response data received
  └─ 🟢 [authService.js] signup() response: {message: "otp_sent"}
  └─ 🟢 [Signup.tsx] Step 3: API response received
  └─ 🟢 [Signup.tsx] Step 4: OTP sent successfully
  └─ Navigate to /verify-otp page
```

---

## 🐛 Troubleshooting Common Issues

### Issue 1: "Email already in use" (422)

**Debug Steps:**

1. Check `[authService.js]` logs - should show error response
2. Check `[Signup.tsx]` logs - should show status 422
3. Check backend logs - validation error will be shown

**Example:**

```
🔴 [Signup.tsx] Change status: 422
🔴 [Signup.tsx] Error data: {"message":"...","errors":{"email":["The email has already been taken."]}}
```

---

### Issue 2: Request Timeout (30000ms exceeded)

**Debug Steps:**

1. Check `[axios.js]` logs for timeout error
2. Check backend logs - likely no entry = server unreachable
3. Verify backend URL in `.env` is correct
4. Check if backend is running

**Example:**

```
🔴 [axios.js] Error code: ECONNABORTED
🔴 [axios.js] Request timeout - Server did not respond within 30000ms
🔴 [Signup.tsx] Request timeout - API server may be down
```

---

### Issue 3: Email Not Sending

**Debug Steps:**

1. Check backend logs for `🟢 [AuthController] OTP email sent successfully`
2. If not present, check for `🔴 [AuthController] Failed to send OTP email: {error}`
3. Check mail configuration in `.env`:
   - MAIL_MAILER=smtp
   - MAIL_HOST=smtp.gmail.com
   - MAIL_PORT=587
   - MAIL_USERNAME & MAIL_PASSWORD

**Example Error:**

```
🔴 [AuthController] Failed to send OTP email: {"error":"SMTP authentication failed"}
```

---

### Issue 4: CORS Error

**Debug Steps:**

1. Check `[axios.js]` logs for "Network error - possibly CORS issue"
2. Check backend logs - no entry = request didn't reach server
3. Verify backend CORS config allows frontend URL

**Example:**

```
🔴 [axios.js] Network error - possibly CORS issue or server unreachable
🔴 [axios.js] Target URL: /signup
```

---

## 🔧 How to Enable/Disable Debugging

### Frontend

- Debugging is **always enabled** (uses `console.log`)
- To disable: Search for `console.log` and remove or comment out

### Backend

- Debugging writes to `storage/logs/laravel.log`
- Check `.env` for `LOG_LEVEL=error|debug`
- Set to `debug` for all logs, `error` for only errors

---

## 📊 Real-Time Monitoring

### Local Development

```bash
# Watch backend logs in real-time
tail -f istem-backend/storage/logs/laravel.log

# Or use Laravel's built-in log viewer
php artisan log:tail
```

### Production (Render)

1. Sign in to Render Dashboard
2. Select your service
3. Click "Logs" tab
4. Logs auto-stream in real-time

---

## ✅ Checklist for Complete Debugging

- [ ] Frontend form submission logs visible in DevTools Console
- [ ] API service layer logs show request payload
- [ ] Axios interceptor logs show HTTP request details
- [ ] Backend logs show signup() validation passed
- [ ] Backend logs show user created in database
- [ ] Backend logs show OTP generated and saved
- [ ] Backend logs show email sent successfully
- [ ] Axios responses logs show 201 status
- [ ] Frontend logs show navigation to verify-otp page

---

**Last Updated:** April 9, 2026  
**Version:** 1.0
