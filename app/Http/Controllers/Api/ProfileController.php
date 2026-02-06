<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;
use App\Traits\ApiResponse;

class ProfileController extends Controller
{
    use ApiResponse;
    public function __construct(
        protected ImageUploadService $imageUploadService
    ) {}

    /**
     * Update user profile
     * 
     * @OA\Post(
     *     path="/profile",
     *     summary="Update user profile",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Profile data",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "email"},
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Profile Image")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user(); // Get authenticated user

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id)
            ],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'], // 'image' in form-data
        ]);

        if ($request->hasFile('image')) {
            // Upload to public/uploads/profiles
            $path = $this->imageUploadService->upload(
                $request->file('image'),
                'profiles',
                $user->profile_img
            );
            $validated['profile_img'] = $path; // Map to DB column
        }

        unset($validated['image']); // Remove from array, not in DB

        // Only update fillable fields
        $user->update($validated);
        
        // Reload role if needed for resource
        $user->load('role');

        return $this->success(new \App\Http\Resources\UserResource($user), 'Profile updated successfully');
    }


    /**
     * Get user profile
     *
     * @OA\Get(
     *     path="/user/profile",
     *     summary="Get user profile",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OA\Property(property="role", type="string", example="admin"),
     *                 @OA\Property(property="avatar_url", type="string", example="http://localhost/uploads/profiles/default.jpg"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('role'); // Ensure role is loaded

        return $this->success(new \App\Http\Resources\UserResource($user));
    }
    /**
     * Change user password
     *
     * @OA\Post(
     *     path="/user/change-password",
     *     summary="Change user password",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Password change data",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"current_password", "new_password", "new_password_confirmation"},
     *                 @OA\Property(property="current_password", type="string", format="password", example="oldpassword"),
     *                 @OA\Property(property="new_password", type="string", format="password", example="newpassword"),
     *                 @OA\Property(property="new_password_confirmation", type="string", format="password", example="newpassword")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password changed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid current password",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Current password does not match")
     *         )
     *     )
     * )
     */
    public function changePassword(\App\Http\Requests\Auth\ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        // Verify current password
        if (! \Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password does not match', 400); 
        }

        // Update password
        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->new_password)
        ]);

        return $this->success(null, 'Password changed successfully');
    }
}
