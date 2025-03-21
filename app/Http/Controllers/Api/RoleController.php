<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Services\SelectedCompanyService;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('permissions')->get();

        return response()->json([
            'message' => 'Roles retrieved successfully.',
            'roles'   => $roles,
            'total'   => $roles->count(),
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string',
            'guard_name'    => 'nullable|string',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);
        
            $roleExists = Role::where('name', $request->name)
            ->where('company_id', $request->company_id)
            ->exists();

        if ($roleExists) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => ['A role with this name already exists for this company.'],
            ], 422);
        }
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }


        $company = SelectedCompanyService::getCompanyIdOrFail();
    
        $data = $validator->validated();
        $data['guard_name'] = $data['guard_name'] ?? 'web';
        $data['company_id'] = $company;

        $role = Role::create($data);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json([
            'message' => 'Role created successfully.',
            'role'    => $role->load('permissions'),
        ], 201);
    }

    public function show(Role $role)
    {
        return response()->json([
            'message' => 'Role retrieved successfully.',
            'role'    => $role->load('permissions'),
        ], 200);
    }

    public function update(Request $request, Role $role)
    {
        
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|unique:roles,name,' . $role->id,
            'guard_name'    => 'nullable|string',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['guard_name'] = $data['guard_name'] ?? 'web';
        $role->update($data);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json([
            'message' => 'Role updated successfully.',
            'role'    => $role->load('permissions'),
        ], 200);
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.',
        ], 200);
    }

    private function getCompanyIdOrFail()
    {
        $user = Auth::user();
        $company = CompanyUser::where('user_id', $user->id)
            ->where('status', 1)
            ->with('company')
            ->first();

        if (!$company) {
            abort(response()->json([
                'message' => 'Company not found or inactive.',
            ], 422));
        }

        return $company->company_id; 
    }
}
