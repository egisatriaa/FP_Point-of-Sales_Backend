<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('UserController@index called', [
            'search' => $request->query('search'),
            'role_id' => $request->query('role_id'),
            'user_id' => Auth::id()
        ]);

        $query = User::with('role')->withoutGlobalScope('active_user');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->query('role_id'));
        }

        $users = $query->paginate($request->query('per_page', 10));

        return $this->success(UserResource::collection($users));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users',
            'password'  => 'required|string|min:8',
            'role_id'   => 'required|exists:roles,id',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'role_id'   => $validated['role_id'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->success(new UserResource($user), 'User created successfully', 201);
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::withoutGlobalScope('active_user')->with('role')->findOrFail($id);
        return $this->success(new UserResource($user));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::withoutGlobalScope('active_user')->findOrFail($id);

        $validated = $request->validate([
            'name'      => 'string|max:255',
            'email'     => ['string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'  => 'nullable|string|min:8',
            'role_id'   => 'exists:roles,id',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['password']) && !empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return $this->success(new UserResource($user), 'User updated successfully');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy($id)
    {
        $user = User::withoutGlobalScope('active_user')->findOrFail($id);
        
        // Prevent deleting self
        if ($user->id === Auth::id()) {
            return $this->error('Cannot delete your own account', 422);
        }

        $user->delete();

        return $this->success(null, 'User deleted successfully');
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus($id)
    {
        $user = User::withoutGlobalScope('active_user')->findOrFail($id);
        
        if ($user->id === Auth::id()) {
            return $this->error('Cannot deactivate your own account', 422);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return $this->success(new UserResource($user), 'User status updated');
    }
}
