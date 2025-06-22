<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\loginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware ;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller implements HasMiddleware
{
    /**
     * Display a listing of the resource.
     */


     public static function middleware() {
        return [
            new Middleware('auth:sanctum', only: ['logout'])
        ];
     }
    public function login(loginRequest $request)
    {

        $email = $request->email;
        $ip = $request->ip();

        // make key for limit login attempts
        $key = 'login_attempts:' . strtolower($email) . ':' . $ip;

        // check if the user has too many attempts
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $minute = ceil($seconds/60);
            return ApiResponse::sendResponse(429, 'Too many login attempts', [
                'message' => "Please try again in {$minute} minutes.",
            ]);
        }



        $credentials = $request->only('email', 'password');

        if (auth()->attempt($credentials)) {

            RateLimiter::clear($key); // Clear the rate limiter on successful login

            $user = auth()->user();

            $token = $user->createToken('auth_token')->plainTextToken;

            return ApiResponse::sendResponse(200, 'Login successful', [
                'user' => auth()->user(),
                'token' => $token,
            ]);
        }
        RateLimiter::hit($key); // Increment the login attempts
        return ApiResponse::sendResponse(401, 'Invalid credentials', null);

    }

    /**
     * register a new use r
     */
    public function Register(RegisterRequest $request)
    {
        $validated =  $request->validated();

        $user = User::create([
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);


        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::sendResponse(201, 'Registration successful',  [
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            $user->tokens()->delete();
            return ApiResponse::sendResponse(200, 'Logout successful', null);
        }
        return ApiResponse::sendResponse(401, 'Unauthorized', null);
    }



}
