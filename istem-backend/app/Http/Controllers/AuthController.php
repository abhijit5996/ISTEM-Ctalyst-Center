<?php

namespace App\Http\Controllers;

use App\Services\EmailService;
use App\Models\Booking;
use App\Models\Queue;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        Log::info("🔵 [AuthController] Step 1: signup() called");
        Log::info("🔵 [AuthController] Request data:", $request->all());
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        Log::info("🟢 [AuthController] Step 2: Validation passed");
        Log::info("🟢 [AuthController] Validated data:", $validated);

        try {
            $user = new User();
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->phone = $validated['phone'] ?? null;
            $user->password = Hash::make($validated['password']);
            $user->email_verified = false;
            $user->save();

            Log::info("🟢 [AuthController] Step 3: User created in database");
            Log::info("🟢 [AuthController] User ID:", ['id' => $user->id, 'email' => $user->email]);

            if (! $this->sendOtpForUser($user)) {
                Log::error("🔴 [AuthController] Step 4: OTP send failed", ['email' => $user->email, 'user_id' => $user->id]);
                return response()->json([
                    'error' => 'otp_send_failed',
                    'message' => 'Failed to send OTP email. Please try again later.',
                ], 500);
            }

            Log::info("🟢 [AuthController] Step 4: OTP sent successfully");

            return response()->json([
                'message' => 'otp_sent',
                'email' => $user->email,
            ], 201);
        } catch (\Throwable $e) {
            Log::error("🔴 [AuthController] Step 5: signup() failed", [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return response()->json([
                'error' => 'signup_failed',
                'message' => 'Unable to complete signup at this time. Please try again later.',
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        if (! $user->email_verified) {
            return response()->json(['error' => 'Email not verified', 'code' => 'email_not_verified'], 403);
        }

        $token = Str::random(60);
        $user->api_token = $token;
        $user->save();

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
            'otpVerified' => true,
        ]);
    }

    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        if (! $this->sendOtpForUser($user)) {
            Log::error("🔴 [AuthController] OTP send failed", ['email' => $user->email, 'user_id' => $user->id]);
            return response()->json([
                'error' => 'otp_send_failed',
                'message' => 'Failed to send OTP email. Please try again later.',
            ], 500);
        }

        return response()->json(['message' => 'otp_sent']);
    }

    public function resendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        if (! $this->sendOtpForUser($user)) {
            Log::error("🔴 [AuthController] OTP resend failed", ['email' => $user->email, 'user_id' => $user->id]);
            return response()->json([
                'error' => 'otp_send_failed',
                'message' => 'Failed to send OTP email. Please try again later.',
            ], 500);
        }

        return response()->json(['message' => 'otp_sent']);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        if (! $user->otp || ! $user->otp_expires_at || now()->gt($user->otp_expires_at)) {
            return response()->json(['error' => 'OTP expired', 'code' => 'otp_expired'], 400);
        }

        if ($user->otp !== $validated['otp']) {
            return response()->json(['error' => 'Invalid OTP', 'code' => 'otp_invalid'], 400);
        }

        $user->otp = null;
        $user->otp_expires_at = null;
        $user->email_verified = true;
        $token = Str::random(60);
        $user->api_token = $token;
        $user->save();

        return response()->json([
            'message' => 'otp_verified',
            'token' => $token,
            'user' => $this->userPayload($user),
            'otpVerified' => true,
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        if (! $this->sendOtpForUser($user)) {
            Log::error("🔴 [AuthController] OTP send failed", ['email' => $user->email, 'user_id' => $user->id]);
            return response()->json([
                'error' => 'otp_send_failed',
                'message' => 'Failed to send OTP email. Please try again later.',
            ], 500);
        }

        return response()->json(['message' => 'otp_sent']);
    }

    public function verifyResetOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        if (! $user->otp || ! $user->otp_expires_at || now()->gt($user->otp_expires_at)) {
            return response()->json(['error' => 'OTP expired', 'code' => 'otp_expired'], 400);
        }

        if ($user->otp !== $validated['otp']) {
            return response()->json(['error' => 'Invalid OTP', 'code' => 'otp_invalid'], 400);
        }

        return response()->json(['message' => 'otp_valid']);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        if (! $user->otp || ! $user->otp_expires_at || now()->gt($user->otp_expires_at)) {
            return response()->json(['error' => 'OTP expired', 'code' => 'otp_expired'], 400);
        }

        if ($user->otp !== $validated['otp']) {
            return response()->json(['error' => 'Invalid OTP', 'code' => 'otp_invalid'], 400);
        }

        $user->password = Hash::make($validated['password']);
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        return response()->json(['message' => 'password_updated']);
    }

    public function profile(Request $request)
    {
        $user = $this->getUserFromToken($request);

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'user' => $this->userPayload($user),
        ]);
    }

    public function userBookings(Request $request)
    {
        $user = $this->getUserFromToken($request);

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $bookings = Booking::where(function ($q) use ($user) {
            $q->where('email', $user->email)
              ->orWhere('user_email', $user->email);
        })->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $bookings,
        ]);
    }

    public function userQueue(Request $request)
    {
        $user = $this->getUserFromToken($request);

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $queue = Queue::where('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $queue,
        ]);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if (! $user) {
            $user = new User();
            $user->name = $googleUser->getName() ?: ($googleUser->getNickname() ?: 'Google User');
            $user->email = $googleUser->getEmail();
            $user->google_id = $googleUser->getId();
            $user->profile_picture = $googleUser->getAvatar();
            $user->password = Hash::make(Str::random(16));
            $user->email_verified = true;
        } else {
            $user->google_id = $user->google_id ?: $googleUser->getId();
            $user->profile_picture = $user->profile_picture ?: $googleUser->getAvatar();
            if (! $user->email_verified) {
                $user->email_verified = true;
            }
        }

        $token = Str::random(60);
        $user->api_token = $token;
        $user->save();

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        return redirect($frontendUrl . '/oauth/google/callback?token=' . $token);
    }

    private function sendOtpForUser(User $user): bool
    {
        Log::info("🔵 [AuthController] sendOtpForUser() called for user:", ['email' => $user->email]);
        
        $otp = (string) random_int(100000, 999999);
        Log::info("🔵 [AuthController] Generated OTP:", ['otp' => $otp, 'user_id' => $user->id]);
        
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save();
        
        Log::info("🟢 [AuthController] OTP saved to database");
        Log::info("🟢 [AuthController] OTP expiry:", ['expires_at' => $user->otp_expires_at]);

        Log::info("🔵 [AuthController] Sending OTP email to:", ['email' => $user->email, 'user_id' => $user->id]);

        try {
            EmailService::sendOTP($user->email, $otp);
            Log::info("🟢 [AuthController] OTP email sent successfully", ['email' => $user->email, 'user_id' => $user->id, 'provider' => 'resend']);
            return true;
        } catch (\Throwable $e) {
            Log::error("🔴 [AuthController] Resend mail failed:", ['error' => $e->getMessage(), 'email' => $user->email, 'user_id' => $user->id]);
            return false;
        }
    }

    private function getUserFromToken(Request $request): ?User
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7);

        if (! $token) {
            return null;
        }

        return User::where('api_token', $token)->first();
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile_picture' => $user->profile_picture,
            'google_id' => $user->google_id,
        ];
    }
}
