<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\OTP;
use App\Notifications\SendOTP;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Rules\PasswordValidator;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'display_name' => 'required|string|max:255',
                'email'       => 'required|email|unique:users,email',
                'password'    => ['required', 'string', new PasswordValidator],
            ]);

            // Check if user with this email already exists but is not active
            $user = User::withTrashed()->where('email', $validated['email'])->first();

            if ($user) {
                if ($user->is_active) {
                    return response()->json([
                        'status'  => 'failed',
                        'message' => 'Email already registered',
                    ], 422);
                }
                // If user exists but is not active, delete the old record
                $user->forceDelete();
            }

            // Create a new user but don't save it yet
            $user = new User([
                'id'            => (string) Str::uuid(),
                'display_name'  => $validated['display_name'],
                'unique_name'   => $this->generateUniqueUsername($validated['display_name']),
                'email'         => $validated['email'],
                'password'      => Hash::make($validated['password']),
                'is_active'     => false, // User will be activated after email verification
            ]);

            // Save the user to get an ID
            $user->save();

            // Generate and send OTP
            $otp = OTP::generate($user->email,type: 'register');
            $user->notify(new SendOTP($otp->otp, 'register'));

            return response()->json([
                'status'  => 'success',
                'message' => 'OTP has been sent to your email. Please verify your email to complete registration.',
                'verification_required' => true,
                'data'    => [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                ],
            ], 200);
        } catch (ValidationException $e) {
            // Return validation errors (e.g. duplicate email, invalid fields)
            return response()->json([
                'status'  => 'failed',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            // Catch-all for any other exception
            \Log::info('error in register :', ['exception_message' => $e->getMessage()]);
            return response()->json([
                'status'  => 'failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            // Resend OTP if user exists but email is not verified
            $otp = OTP::generate($user->email, 'register');
            $user->notify(new SendOTP($otp->otp, 'register'));

            return response()->json([
                'status' => 'unverified',
                'message' => 'Please verify your email address. A new OTP has been sent to your email.',
                'user_id' => $user->id,
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([], 204);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }




    /**
     * Generate a unique username from display name
     */
    protected function generateUniqueUsername($displayName)
    {
        $username = Str::slug($displayName, '');
        $original = $username;
        $count = 1;

        while (User::where('unique_name', $username)->exists()) {
            $username = $original . $count++;
        }

        return $username;
    }

    /**
     * Verify OTP for registration
     */
    public function verifyRegistration(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->is_active) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Account is already verified',
            ], 400);
        }

        if (OTP::validateOTP($request->email, $request->otp, 'register')) {
            $user->is_active = true;
            $user->email_verified_at = now();
            $user->save();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status'  => 'success',
                'message' => 'Email verified successfully',
                'data'    => [
                    'user'  => $user,
                    'token' => $token,
                ],
            ]);
        }

        return response()->json([
            'status'  => 'failed',
            'message' => 'Invalid or expired OTP',
        ], 422);
    }

    /**
     * Request OTP for account deletion
     */
    public function requestDeleteAccount(Request $request)
    {
        $user = $request->user();

        // Generate and send OTP
        $otp = OTP::generate($user->email, 'delete_account');
        $user->notify(new SendOTP($otp->otp, 'delete_account'));

        return response()->json([
            'status'  => 'success',
            'message' => 'OTP has been sent to your email to confirm account deletion.',
        ]);
    }

    /**
     * Delete account after OTP verification
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!OTP::validateOTP($user->email, $request->otp, 'delete_account')) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Invalid or expired OTP',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Revoke all tokens
            $user->tokens()->delete();

            // Delete uploaded avatar if exists
            if ($user->avatar_url && Storage::disk('public')->exists($user->avatar_url)) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            // Delete user's posts (and media if exists)
            foreach ($user->posts as $post) {
                if ($post->media_url && Storage::disk('public')->exists($post->media_url)) {
                    Storage::disk('public')->delete($post->media_url);
                }
                $post->delete();
            }

            // Soft delete the user
            $user->delete();

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Account deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete account. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'display_name' => 'required|string|min:4|max:255',
            'unique_name' => 'nullable|string|min:4|max:255|alpha_dash|unique:users,unique_name,' . $user->id,
            'avatar' => 'nullable|image|max:2048',
        ]);

        $user->display_name = $request->display_name;

        // ✅ Handle unique_name change if present
        if ($request->filled('unique_name') && $request->unique_name !== $user->unique_name) {
            $canChange = !$user->unique_name_changed_at || now()->diffInMonths($user->unique_name_changed_at) >= 2;

            if (!$canChange) {
                return response()->json([
                    'message' => 'You can only change your unique name once every 2 months.',
                ], 422);
            }

            $user->unique_name = $request->unique_name;
            $user->unique_name_changed_at = now();
        }

        // ✅ Handle avatar image
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $filename = uniqid() . '.' . $avatar->getClientOriginalExtension();
            $path = $avatar->storeAs('avatars', $filename, 'public');
            $user->avatar_url = asset('storage/' . $path);
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user,
        ]);
    }


    public function requestPasswordReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate and send OTP
        $otp = OTP::generate($user->email, 'reset_password');
        $user->notify(new SendOTP($otp->otp, 'reset_password'));

        return response()->json([
            'status' => 'success',
            'message' => 'OTP has been sent to your email to reset your password.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'reset_token' => 'required|string',
            'password' => ['required', 'string', 'confirmed', new PasswordValidator],
        ]);

        $user = User::where('email', $request->email)->first();
        $cacheKey = "reset_password_token_{$user->id}";
        $cachedToken = cache($cacheKey);

        if (!$cachedToken || $cachedToken !== $request->reset_token) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid or expired reset token',
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Remove the token after use
        cache()->forget($cacheKey);

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset successfully. You can now log in.',
        ]);
    }

    public function verifyResetOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!OTP::validateOTP($request->email, $request->otp, 'reset_password')) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid or expired OTP',
            ], 422);
        }

        // Generate a short-lived reset token (e.g., random string, valid for 10 minutes)
        $resetToken = bin2hex(random_bytes(32));
        cache(["reset_password_token_{$user->id}" => $resetToken], now()->addMinutes(10));

        return response()->json([
            'status' => 'success',
            'message' => 'OTP verified. Use the reset token to reset your password.',
            'reset_token' => $resetToken,
        ]);
    }


    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'id'            => (string) Str::uuid(),
                    'display_name'  => $googleUser->getName(),
                    'unique_name'   => $this->generateUniqueUsername($googleUser->getName()),
                    'password'      => Hash::make(Str::random(24)), // random password
                    'is_active'     => true, // Google users are automatically active
                    'email_verified_at' => now(), // Google emails are verified
                ]
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status'  => 'success',
                'message' => 'Logged in with Google successfully',
                'data'    => [
                    'user'  => $user,
                    'token' => $token,
                ],
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Google login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Google Sign-In with ID Token from Flutter app
     */
    public function loginWithGoogle(Request $request)
    {
        $validated = $request->validate([
            'id_token' => 'required|string',
            'email' => 'required|email',
            'display_name' => 'required|string',
        ]);

        $idToken = $validated['id_token'];
        $googleEmail = $validated['email'];
        $googleName = $validated['display_name'];

        // Note: In production, you should verify the ID token server-side
        // For now, we'll trust the data from the Flutter app
        // You can implement proper verification later using Google's verification endpoint

        // Find or create user
        $user = User::firstOrCreate(
            ['email' => $googleEmail],
            [
                'id'            => (string) Str::uuid(),
                'display_name'  => $googleName,
                'unique_name'   => $this->generateUniqueUsername($googleName),
                'password'      => Hash::make(Str::random(24)),
                'is_active'     => true,
                'email_verified_at' => now(),
            ]
        );

        // If user already exists, ensure they are active
        if (!$user->is_active) {
            $user->update([
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }
}
