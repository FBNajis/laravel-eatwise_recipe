<?php

// app/Http/Controllers/UserController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function getUserProfile(Request $request)
    {
        try {
            $email = $request->query('email');

            if (!$email) {
                return response()->json(['message' => 'Email is required'], 400);
            }

            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            return response()->json([
                'user' => [
                    'username' => $user->username,
                    'name' => $user->fullname,
                    'phone' => $user->phone_number,
                    'email' => $user->email
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
