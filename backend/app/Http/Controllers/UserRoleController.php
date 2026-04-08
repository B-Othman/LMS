<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignUserRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleManagementService;
use Illuminate\Http\JsonResponse;

class UserRoleController extends Controller
{
    public function __construct(
        private readonly RoleManagementService $roleManagement,
    ) {}

    public function store(AssignUserRoleRequest $request, int $id): JsonResponse
    {
        $targetUser = User::withoutGlobalScopes()->findOrFail($id);

        $this->authorize('update', $targetUser);

        $role = Role::query()->findOrFail($request->integer('role_id'));
        $updatedUser = $this->roleManagement->assignRole($request->user(), $targetUser, $role);

        return $this->success(
            new UserResource($updatedUser),
            'Role assigned successfully.',
        );
    }
}
