<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => 'required|string|max:20',
            'company_name' => 'required|string|max:255',
            'company_address' => 'required|string|max:255',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string',
            'translations.*.company_description' => 'required|string'
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'company_name' => $validated['company_name'],
            'company_address' => $validated['company_address'],
            'role' => 'seller',
            'status' => 'pending' // Admin tasdiqlashi kerak
        ]);

        // Ko'p tillilik ma'lumotlarini saqlash
        foreach ($request->translations as $translation) {
            $user->translations()->create([
                'locale' => $translation['locale'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'company_description' => $translation['company_description']
            ]);
        }

        return response()->json([
            'message' => 'Seller registration successful. Please wait for admin approval.',
            'user' => $user->load('translations')
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)
            ->where('role', 'seller')
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your account is not active. Please wait for admin approval.'
            ], 403);
        }

        $token = $user->createToken('seller-token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'user' => $user->load('translations'),
            'token' => $token
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('translations')
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
