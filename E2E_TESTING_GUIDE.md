# 🧪 End-to-End Testing Guide - ISTEM Catalyst Center

**Website Status**: ✅ Deployed on Render | 🔧 Available for Local Testing

**Test Date**: April 11, 2026  
**Configuration**: Supports both production (Render) and local (XAMPP) environments

---

## 📋 Configuration Status

### Environment File Setup ✅

```
Frontend: catalyst-connect/.env
- VITE_API_URL → Production: https://istem-catalyst-center.onrender.com/api
- VITE_LOCAL_API_URL → Local: http://localhost:8000/api
```

Both URLs are now configured - frontend will use production by default, but can switch to localhost when needed.

---

## 🔄 Complete User Signup & Auth Flow

### 1️⃣ **SIGNUP FLOW** (Pages/Components)

#### Frontend: Sign Up Form

**File**: `catalyst-connect/src/pages/Signup.tsx`
**Status**: ✅ FIXED & ENHANCED

**Form Fields**:

- ✅ Name (required)
- ✅ Email (required, must be unique)
- ✅ Phone (optional)
- ✅ Password (required, min 8 characters)
- ✅ Confirm Password (required, must match)

**Frontend Validation** (Added):

```javascript
✅ All fields required check
✅ Password match validation
✅ Password length validation (min 8)
✅ Better error messages
```

**Backend**: Signup Controller
**File**: `istem-backend/app/Http/Controllers/AuthController.php::signup()`

**Backend Validation**:

```php
- name: required|string|max:255
- email: required|email|unique:users,email ✅
- phone: nullable|string|max:20
- password: required|string|min:8
```

**Process**:

1. ✅ Validate all fields
2. ✅ Hash password using bcrypt
3. ✅ Create user with email_verified = false
4. ✅ Send OTP to email
5. ✅ Return 201 with 'otp_sent' message

---

### 2️⃣ **OTP VERIFICATION FLOW**

#### Frontend: OTP Verification

**File**: `catalyst-connect/src/pages/OTPVerification.tsx`

**Features**:

- ✅ 6-digit OTP input
- ✅ 5-minute countdown timer
- ✅ Resend OTP option
- ✅ Email display

**Backend**: OTP Verification
**File**: `istem-backend/app/Http/Controllers/AuthController.php::verifyOtp()`

**Verification Process**:

1. ✅ Check if OTP exists and not expired
2. ✅ Compare with provided OTP
3. ✅ Mark email_verified = true
4. ✅ Generate API token
5. ✅ Return token + user data

---

### 3️⃣ **LOGIN FLOW**

#### Frontend: Login Form

**File**: `catalyst-connect/src/pages/Login.tsx`

**Fields**:

- Email (required)
- Password (required)

#### Backend: Login Controller

**File**: `istem-backend/app/Http/Controllers/AuthController.php::login()`

**Process**:

1. ✅ Validate email & password
2. ✅ Check if user exists
3. ✅ Verify password hash
4. ✅ Check if email is verified (must be true!)
5. ✅ Generate API token
6. ✅ Return token + user data

---

### 4️⃣ **PROFILE & PROTECTED ENDPOINTS**

#### Get User Profile

**Endpoint**: `GET /api/user/profile`
**Auth**: Required (Bearer token)

**File**: `istem-backend/app/Http/Controllers/AuthController.php::profile()`

**Returns**:

```json
{
  "user": {
    "id": 1,
    "name": "User Name",
    "email": "user@example.com",
    "phone": "1234567890",
    "profile_picture": null,
    "google_id": null
  }
}
```

#### Get User Bookings

**Endpoint**: `GET /api/bookings/user`
**Auth**: Required
**File**: `istem-backend/app/Http/Controllers/AuthController.php::userBookings()`

#### Get User Queue

**Endpoint**: `GET /api/queue/user`
**Auth**: Required
**File**: `istem-backend/app/Http/Controllers/AuthController.php::userQueue()`

---

## 🧪 TESTING SCENARIOS

### Test Case 1: Successful Signup

**Steps**:

1. Navigate to https://istem-catalyst-center.onrender.com
2. Click "Sign Up"
3. Fill form:
   - Name: "Test User"
   - Email: "test\_$(date +%s)@test.com"
   - Phone: "9876543210"
   - Password: "SecurePass123"
   - Confirm Password: "SecurePass123"
4. Click "Sign up"

**Expected Result**:

- ✅ Toast: "OTP sent to your email. Please verify."
- ✅ Navigate to `/verify-otp?email=...`
- ✅ User created in database

**How to Verify**:

```bash
# Check database
SELECT * FROM users WHERE email = 'test_...@test.com';
# Should show email_verified = 0 (false)
```

---

### Test Case 2: Password Mismatch

**Steps**:

1. Go to signup
2. Fill passwords differently:
   - Password: "SecurePass123"
   - Confirm Password: "DifferentPass123"
3. Click "Sign up"

**Expected Result**:

- ✅ Toast error: "Passwords do not match"
- ❌ No API call made
- ❌ User NOT created

---

### Test Case 3: Password Too Short

**Steps**:

1. Go to signup
2. Enter password: "Short1" (6 chars)
3. Fill rest of form

**Expected Result**:

- ✅ Toast error: "Password must be at least 8 characters"

---

### Test Case 4: OTP Verification

**Steps**:

1. Complete signup
2. Check email for OTP
3. Enter OTP on verification page
4. Click "Verify"

**Expected Result**:

- ✅ Toast: "Email verified successfully"
- ✅ Redirect to home "/"
- ✅ Auth token stored in localStorage
- ✅ User profile accessible

**Backend**: OTP sent to configured email (abhijitdasRahul010304@gmail.com)

---

### Test Case 5: Failed OTP (Expired)

**Steps**:

1. Wait 5 minutes after signup
2. Enter OTP
3. Click "Verify"

**Expected Result**:

- ✅ Toast: "OTP expired. Please resend."

---

### Test Case 6: Login with Verified User

**Steps**:

1. Complete signup + OTP verification
2. Go to login page
3. Enter credentials:
   - Email: (same as signup)
   - Password: (password used in signup)
4. Click "Login"

**Expected Result**:

- ✅ Toast: "Login successful"
- ✅ Redirect to home
- ✅ Auth token stored in localStorage

---

### Test Case 7: Login with Unverified Email

**Steps**:

1. Signup (complete)
2. WITHOUT verifying OTP, try to login
3. Enter credentials

**Expected Result**:

- ✅ Toast: "Email not verified"
- ❌ No login
- ✅ User must verify OTP first

---

### Test Case 8: Duplicate Email Signup

**Steps**:

1. First signup with "user@test.com"
2. Try signup again with same email

**Expected Result**:

- ✅ Toast: "Email already in use or validation failed"
- ✅ HTTP 422 error from backend

---

### Test Case 9: Google OAuth Login

**Steps**:

1. Click "Sign in with Google" on login page
2. Select Google account
3. Authorize app

**Expected Result**:

- ✅ Redirect to home
- ✅ User created if new
- ✅ Auth token stored
- ✅ Profile picture saved (if available)

---

### Test Case 10: Forgot Password Flow

**Steps**:

1. Go to "Forgot Password" page
2. Enter email
3. Click "Send OTP"

**Expected Result**:

- ✅ OTP sent to email
- ✅ Redirect to "Verify Reset OTP" page

**Then**: 4. Enter OTP 5. Add new password + confirm 6. Click "Reset Password"

**Expected Result**:

- ✅ Password updated
- ✅ Toast: "Password updated"
- ✅ Redirect to login

---

## 📊 DATABASE VERIFICATION

### Users Table Schema

```sql
CREATE TABLE users (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  email_verified_at TIMESTAMP,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  google_id VARCHAR(255),
  profile_picture TEXT,
  otp VARCHAR(6),
  otp_expires_at TIMESTAMP,
  email_verified BOOLEAN DEFAULT false,
  api_token VARCHAR(80) UNIQUE,
  remember_token VARCHAR(100),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### Check Created Users

```sql
SELECT id, name, email, email_verified, created_at
FROM users
ORDER BY created_at DESC
LIMIT 10;
```

---

## 🔌 API ENDPOINTS SUMMARY

| Method | Endpoint            | Auth | Purpose                            |
| ------ | ------------------- | ---- | ---------------------------------- |
| POST   | `/signup`           | ❌   | Create new user + send OTP         |
| POST   | `/send-otp`         | ❌   | Resend OTP to email                |
| POST   | `/verify-otp`       | ❌   | Verify OTP + set email_verified    |
| POST   | `/login`            | ❌   | Login with credentials             |
| POST   | `/forgot-password`  | ❌   | Request password reset OTP         |
| POST   | `/verify-reset-otp` | ❌   | Verify reset OTP                   |
| POST   | `/reset-password`   | ❌   | Update password after verification |
| GET    | `/user/profile`     | ✅   | Get current user profile           |
| GET    | `/bookings/user`    | ✅   | Get user's bookings                |
| GET    | `/queue/user`       | ✅   | Get user's queue status            |

---

## 🌐 DEPLOYMENT STATUS

### Production (Render)

- **URL**: https://istem-catalyst-center.onrender.com
- **Backend**: Running on Render with Railway MySQL
- **Frontend**: Deployed & served by Render
- **Email**: Configured with Gmail SMTP
- **Database**: MySQL on Railway

### Local Testing (XAMPP)

- **Backend Port**: 8000 (Laravel dev server)
- **Frontend Port**: 5173 (Vite dev server)
- **Database**: Local MySQL via XAMPP
- **Configuration**: Updated .env for localhost support

---

## ✅ CODE IMPROVEMENTS APPLIED

### 1. Frontend Signup Component

```
✅ Added password confirmation field
✅ Added validation for matching passwords
✅ Added password length validation (min 8)
✅ Improved error handling for different error types
✅ Better error messages for users
✅ Sent password_confirmation in API request
```

### 2. Error Handling

```
✅ 422 validation errors - displayed properly
✅ 409 duplicate email - handled
✅ ECONNABORTED timeout - user-friendly message
✅ Generic errors - caught and displayed
```

---

## 🚀 HOW TO RUN LOCALLY

### Terminal 1: Start Backend

```bash
cd "d:\RAHUL\wordpress project\ISTEM-Catalyst Center\istem-backend"
php artisan serve --port=8000
```

### Terminal 2: Start Frontend

```bash
cd "d:\RAHUL\wordpress project\ISTEM-Catalyst Center\catalyst-connect"
npm run dev
# or
bun run dev
```

### Terminal 3: MySQL (XAMPP)

- Start MySQL from XAMPP control panel
- Backend will connect to localhost MySQL

### Access

- Frontend: http://localhost:5173
- Backend: http://localhost:8000
- API Docs: http://localhost:8000/api/

---

## 📝 NOTES

1. **Email Testing**: In production, OTPs are sent via Gmail SMTP
2. **Password Reset**: Uses same OTP mechanism as signup
3. **Token Storage**: Frontend stores API token in localStorage under 'auth_token'
4. **Session**: Sessions handled via database (configured in Laravel)
5. **CORS**: Configured to allow requests from frontend domain

---

**Last Updated**: April 11, 2026  
**Status**: ✅ Ready for End-to-End Testing
