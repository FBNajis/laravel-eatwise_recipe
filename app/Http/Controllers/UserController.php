<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
                    'email' => $user->email,
                    'image' => $user->image_path ? asset('storage/' . $user->image_path) : null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'fullname' => 'required|string',
            'username' => 'required|string|unique:users,username,' . $user->id,
            'phone_number' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:25600',
        ]);

        $user->fullname = $validated['fullname'];
        $user->username = $validated['username'];
        $user->phone_number = $validated['phone_number'] ?? $user->phone_number;

        if ($request->filled('password')) {
            $user->password = bcrypt($validated['password']);
        }

        if ($request->hasFile('image')) {
            if ($user->image_path) {
                Storage::disk('public')->delete($user->image_path);
            }

            $imagePath = $request->file('image')->store('users', 'public');
            $user->image_path = $imagePath;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'image_url' => $user->image_path ? asset('storage/' . $user->image_path) : null
        ]);
    }
}
