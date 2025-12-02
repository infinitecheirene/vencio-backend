<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
                'last_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'phone' => ['required', 'string', 'regex:/^[0-9]{11}$/', 'unique:users,phone'],
                'password' => ['required', 'confirmed', Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()],
            ], [
                // Custom error messages
                'first_name.required' => 'First name is required',
                'first_name.regex' => 'First name can only contain letters and spaces',
                'last_name.required' => 'Last name is required',
                'last_name.regex' => 'Last name can only contain letters and spaces',
                'email.required' => 'Email address is required',
                'email.email' => 'Please enter a valid email address',
                'email.unique' => 'This email address is already registered',
                'phone.required' => 'Phone number is required',
                'phone.regex' => 'Phone number must be exactly 11 digits',
                'phone.unique' => 'This phone number is already registered',
                'password.required' => 'Password is required',
                'password.confirmed' => 'Password confirmation does not match',
                'password.min' => 'Password must be at least 8 characters',
            ]);

            if ($validator->fails()) {
                // Get the first error message
                $firstError = $validator->errors()->first();
                
                return response()->json([
                    'success' => false,
                    'message' => $firstError,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create user
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => strtolower(trim($request->email)),
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ],
                    'token' => $token
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration. Please try again.',
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'email'],
                'password' => ['required'],
            ], [
                'email.required' => 'Email address is required',
                'email.email' => 'Please enter a valid email address',
                'password.required' => 'Password is required',
            ]);

            if ($validator->fails()) {
                $firstError = $validator->errors()->first();
                
                return response()->json([
                    'success' => false,
                    'message' => $firstError,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user exists
            $user = User::where('email', strtolower(trim($request->email)))->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this email address'
                ], 404);
            }

            // Attempt authentication
            if (!Auth::attempt([
                'email' => strtolower(trim($request->email)),
                'password' => $request->password
            ])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect password. Please try again.'
                ], 401);
            }

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ],
                    'token' => $token
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login. Please try again.',
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ]);

        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during logout.',
            ], 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get user error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching user data.',
            ], 500);
        }
    }

    /**
     * Check if email exists
     */
    public function checkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'exists' => false,
            ], 422);
        }

        $exists = User::where('email', strtolower(trim($request->email)))->exists();

        return response()->json([
            'success' => true,
            'exists' => $exists,
            'message' => $exists ? 'Email already registered' : 'Email available'
        ]);
    }

    /**
     * Check if phone exists
     */
    public function checkPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'exists' => false,
            ], 422);
        }

        $exists = User::where('phone', $request->phone)->exists();

        return response()->json([
            'success' => true,
            'exists' => $exists,
            'message' => $exists ? 'Phone number already registered' : 'Phone number available'
        ]);
    }
}