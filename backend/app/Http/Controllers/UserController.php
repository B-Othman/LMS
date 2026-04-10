<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\IndexUsersRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(IndexUsersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = $this->users->paginateUsers($request->validated());

        return $this->success(
            UserResource::collection($users->getCollection())->resolve(),
            meta: [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', [User::class, $this->tenantContext->tenantId()]);

        $user = $this->users->createUser($request->user(), $request->validated());

        return $this->success(
            new UserResource($user),
            'User created successfully.',
            201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->users->findUser($id);

        $this->authorize('view', $user);

        return $this->success(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->users->findUser($id);

        $this->authorize('update', $user);

        $updatedUser = $this->users->updateUser($request->user(), $user, $request->validated());

        return $this->success(
            new UserResource($updatedUser),
            'User updated successfully.',
        );
    }

    public function destroy(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $user = $this->users->findUser($id);

        $this->authorize('delete', $user);

        $this->users->deleteUser($request->user(), $user);

        return $this->success(
            message: 'User deleted successfully.',
        );
    }
}
