<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:lecturer,student',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Email or Password is incorrect'], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in'   => JWTAuth::factory()->getTTL() * 60,
            'user'         => JWTAuth::user(),
        ]);
    }

    public function me()
    {
        return response()->json(['user' => JWTAuth::user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = JWTAuth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $user = JWTAuth::user();

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            // Delete the old avatar if it exists
            Storage::disk('public')->delete($user->avatar);
        }

        $file = $request->file('avatar');
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '_' . uniqid() . '.' . $extension;
        $path = $file->storeAs('avatars', $filename, 'public');

        $user->avatar = $path;
        $user->save();

        return response()->json([
            'message' => 'Avatar uploaded successfully',
            'avatar_url' => Storage::url($path),
            'user' => $user
        ]);
    }

    public function logout()
    {
        try {
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json(['message' => 'Token not provided'], 400);
            }

            JWTAuth::invalidate($token);

            return response()->json(['message' => 'Successfully logged out']);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Failed to logout, please try again'], 500);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Invalid token, please login again'], 401);
        }
    }

    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh();
            return response()->json([
                'access_token' => $newToken,
                'token_type' => 'Bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ]);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token is invalid'], 401);
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token has expired, please login again'], 401);
        } catch (TokenBlacklistedException $e) {
            return response()->json(['message' => 'Token is blacklisted, please login again'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Could not refresh token'], 500);
        }
    }
}
