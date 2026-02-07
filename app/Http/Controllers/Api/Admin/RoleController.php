<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Traits\ApiResponse;

class RoleController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of roles.
     */
    public function index()
    {
        $roles = Role::all();
        return $this->success($roles);
    }
}
