<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignUserRoleRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserRoleController extends Controller
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    public function store(AssignUserRoleRequest $request, int $id): JsonResponse
    {
        $targetUser = $this->users->findUser($id);

        $this->authorize('update', $targetUser);

        $updatedUser = $this->users->syncUserRoles($request->user(), $targetUser, $request->roleIds());
        $message = count($request->roleIds()) === 1 ? 'Role assigned successfully.' : 'Roles updated successfully.';

        return $this->success(
            new UserResource($updatedUser),
            $message,
        );
    }
}
