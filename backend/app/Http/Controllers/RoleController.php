<?php

namespace App\Http\Controllers;

use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Services\RoleManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleManagementService $roleManagement,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $roles = $this->roleManagement->paginateVisibleRoles(
            $request->user(),
            (int) $request->integer('per_page', 15),
        );

        return $this->success(
            RoleResource::collection($roles->getCollection())->resolve(),
            meta: [
                'current_page' => $roles->currentPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
                'last_page' => $roles->lastPage(),
            ],
        );
    }

    public function permissions(Request $request, int $id): JsonResponse
    {
        $role = $this->roleManagement->getVisibleRole($request->user(), $id);

        return $this->success([
            'role' => (new RoleResource($role))->resolve(),
            'permissions' => PermissionResource::collection($role->permissions)->resolve(),
        ]);
    }
}
