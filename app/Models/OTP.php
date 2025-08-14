<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OTP extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'otps';
    protected $fillable = ['id', 'email', 'otp', 'type', 'is_used', 'expires_at'];
    protected $casts = [
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Generate a new OTP for the given email and type
     */
    public static function generate($email, $type = 'register')
    {
        // Invalidate any existing OTPs for this email and type
        self::where('email', $email)
            ->where('type', $type)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        // Generate a 6-digit OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Create new OTP record
        return self::create([
            'id' => (string) Str::uuid(),
            'email' => $email,
            'otp' => $otp,
            'type' => $type,
            'expires_at' => now()->addMinutes(30), // OTP expires in 30 minutes
        ]);
    }

    /**
     * Validate the given OTP for the email and type
     */
    public static function validateOTP($email, $otp, $type)
    {
        $otpRecord = self::where('email', $email)
            ->where('otp', $otp)
            ->where('type', $type)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();

        if ($otpRecord) {
            // Mark OTP as used
            $otpRecord->update(['is_used' => true]);
            return true;
        }

        return false;
    }
}
