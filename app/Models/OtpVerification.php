<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OtpVerification extends Model
{
    use HasFactory;

    protected $table = 'otp_verifications';

    protected $fillable = [
        'email',
        'otp',
        'expires_at', // opsional, kalau kamu pakai expired OTP
    ];

    public $timestamps = true; // true jika kamu pakai created_at dan updated_at
}
