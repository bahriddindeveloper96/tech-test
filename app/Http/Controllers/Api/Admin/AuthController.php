<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'phone' => 'required|unique:users',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string',
            'translations.*.first_name' => 'required|string',
            'translations.*.last_name' => 'required|string',
            'company_name' => 'nullable|string',
            'company_address' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => $request->role ?? 'admin',
                'status' => $request->role === 'seller' ? 'pending' : 'active',
                'company_name' => $request->company_name,
                'company_address' => $request->company_address
            ]);

            foreach ($request->translations as $translation) {
                UserTranslation::create([
                    'user_id' => $user->id,
                    'locale' => $translation['locale'],
                    'first_name' => $translation['first_name'],
                    'last_name' => $translation['last_name']
                ]);
            }

            DB::commit();

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user->load('translations'),
                'token' => $token
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

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
