<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateUserStatusRequest;
use App\Http\Requests\User\AssignRoleRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\StoreRoleRequest;
use App\Http\Requests\User\UpdateRoleRequest;
use App\Http\Requests\User\StorePermissionRequest;
use App\Http\Requests\User\UpdatePermissionRequest;
use App\Http\Requests\User\UpdateRolePermissionsRequest;
use App\Services\UserService;
use App\Http\Resources\UserResource;
use App\Http\Resources\RoleResource;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(): JsonResponse
    {
        $users = $this->userService->getUsers();

        return response()->json([
            'status' => 'success', 
            'data' => UserResource::collection($users)
        ]);
    }

    public function updateStatus(UpdateUserStatusRequest $request, $id): JsonResponse
    {
        try {
            $user = $this->userService->updateUserStatus($request->user(), (int) $id, $request->status);

            return response()->json([
                'status' => 'success', 
                'message' => 'User status updated', 
                'data' => new UserResource($user)
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function assignRole(AssignRoleRequest $request, $id): JsonResponse
    {
        try {
            $user = $this->userService->assignRole((int) $id, (int) $request->role_id);

            return response()->json([
                'status' => 'success', 
                'message' => "Role {$user->roles->first()->name} assigned to {$user->first_name}", 
                'data' => new UserResource($user)
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getRoles(): JsonResponse
    {
        $roles = $this->userService->getRoles();

        return response()->json([
            'status' => 'success', 
            'data' => RoleResource::collection($roles)
        ]);
    }

    public function getPermissions(): JsonResponse
    {
        $permissions = $this->userService->getPermissions();

        return response()->json([
            'status' => 'success', 
            'data' => PermissionResource::collection($permissions)
        ]);
    }

    public function updateRolePermissions(UpdateRolePermissionsRequest $request, $id): JsonResponse
    {
        try {
            $role = $this->userService->updateRolePermissions((int) $id, $request->permission_ids);

            return response()->json([
                'status' => 'success',
                'message' => "Permissions for role {$role->name} updated successfully",
                'data' => new RoleResource($role)
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => new UserResource($user)
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function storeRole(StoreRoleRequest $request): JsonResponse
    {
        try {
            $role = $this->userService->storeRole($request->validated());

            return response()->json([
                'status' => 'success', 
                'message' => 'Role created', 
                'data' => new RoleResource($role)
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateRole(UpdateRoleRequest $request, $id): JsonResponse
    {
        try {
            $role = $this->userService->updateRole((int) $id, $request->validated());

            return response()->json([
                'status' => 'success', 
                'message' => 'Role updated', 
                'data' => new RoleResource($role->load('permissions'))
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroyRole($id): JsonResponse
    {
        try {
            $this->userService->deleteRole((int) $id);

            return response()->json(['status' => 'success', 'message' => 'Role deleted']);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function storePermission(StorePermissionRequest $request): JsonResponse
    {
        try {
            $permission = $this->userService->storePermission($request->validated());

            return response()->json([
                'status' => 'success', 
                'message' => 'Permission created', 
                'data' => new PermissionResource($permission)
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function updatePermission(UpdatePermissionRequest $request, $id): JsonResponse
    {
        try {
            $permission = $this->userService->updatePermission((int) $id, $request->validated());

            return response()->json([
                'status' => 'success', 
                'message' => 'Permission updated', 
                'data' => new PermissionResource($permission)
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroyPermission($id): JsonResponse
    {
        try {
            $this->userService->deletePermission((int) $id);

            return response()->json(['status' => 'success', 'message' => 'Permission deleted']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
