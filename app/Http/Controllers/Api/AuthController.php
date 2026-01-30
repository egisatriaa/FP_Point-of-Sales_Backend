<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->error('Invalid credentials', 401);
        }

        if (! $user->is_active) {
            return $this->error('Account is inactive', 403);
        }

        if (! Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials', 401);
        }

        $user->update([
            'last_login_at' => now(),
        ]);

        $token = $user->createToken('pos-token')->plainTextToken;

        return $this->success([
            'user_id' => $user->id,
            'name'    => $user->name,
            'email'   => $user->email,
            'role'    => $user->role->role_name,
            'token'   => $token,
        ], 'Login successful');
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'role_id'   => $validated['role_id'],
            'is_active' => true,
        ]);

        $token = $user->createToken('pos-token')->plainTextToken;

        return $this->success([
            'user_id' => $user->id,
            'name'    => $user->name,
            'email'   => $user->email,
            'role'    => $user->role->role_name,
            'token'   => $token,
        ], 'Register successful', 201);
    }

    public function logout()
    {
        request()->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out');
    }
}
