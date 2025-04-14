<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Models\OtpVerification;



class AuthController extends Controller
{
    public function signup(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users',
            'fullname' => 'required|string',
            'phone_number' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'username' => $request->username,
            'fullname' => $request->fullname,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Signup successful'], 200);
    }


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah'
            ], 401);
        }

        // Generate token
        $token = $user->createToken("API TOKEN")->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $user,
            'token' => $token
        ], 200);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $otp = rand(1000, 9999);

        // Simpan OTP ke tabel otp_verifications
        OtpVerification::updateOrCreate(
            ['email' => $request->email],
            ['otp' => $otp, 'updated_at' => now()]
        );

        // Kirim ke email
        Mail::raw("Your OTP is: $otp", function ($message) use ($request) {
            $message->to($request->email)->subject("OTP Verification");
        });

        return response()->json(['message' => 'OTP sent']);
    }


    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required'
        ]);

        $otpData = OtpVerification::where('email', $request->email)->first();

        if ($otpData && $otpData->otp == $request->otp) {
            $otpData->delete(); 
            return response()->json(['message' => 'OTP verified']);
        }

        return response()->json(['message' => 'Invalid OTP'], 422);
    }

    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Kirim OTP, sementara: 1234 (simulasi)
            // Bisa disimpan ke session atau DB jika perlu

            return response()->json([
                'status' => true,
                'message' => 'Email is registered. OTP sent to your email.',
                'otp' => '1234' // hanya untuk debug/testing, di production jangan kirim ke frontend
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Email is not registered.'
            ], 404);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'new_password' => 'required|min:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->password = bcrypt($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password reset successful'], 200);
    }
}
