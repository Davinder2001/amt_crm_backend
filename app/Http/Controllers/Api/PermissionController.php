<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Item, Invoice, User, Task, Package};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    /**
     * Display a listing of the permissions.
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::all()->groupBy('group');

        $grouped = $permissions->map(function ($groupPermissions, $groupName) {
            return [
                'group' => $groupName,
                'permissions' => $groupPermissions,
            ];
        })->values();

        return response()->json($grouped);
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|unique:permissions,name|string|max:255',
        ]);

        $permission = Permission::create($data);

        return response()->json($permission, 201);
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission): JsonResponse
    {
        return response()->json($permission);
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, Permission $permission): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|unique:permissions,name,' . $permission->id . '|string|max:255',
        ]);

        $permission->update($data);

        return response()->json($permission);
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(Permission $permission): JsonResponse
    {
        $permission->delete();
        return response()->json(['message' => 'Permission deleted successfully']);
    }
    /**
     * Remove the specified permission.
     */
    public function packagesAllowCheck(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:item,invoice,employee,task'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request type.',
                'errors' => $validator->errors()
            ], 422);
        }

        $type               = $request->type;
        $selectedCompany    = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyId          = $selectedCompany->company_id;
        $package            = Package::find($selectedCompany->company->package_id ?? 1);

        if (!$package) {
            return response()->json([
                'success' => false,
                'allowed' => false,
                'message' => 'No valid package found for the company.',
            ], 403);
        }

        $packageType = $package->package_type ?? 'monthly';

        // Field mapping
        $limitMap = [
            'item' => 'items_number',
            'invoice' => 'invoices_number',
            'employee' => 'employee_numbers',
            'task' => 'daily_tasks_number',
        ];
        $modelMap = [
            'item'      => Item::class,
            'invoice'   => Invoice::class,
            'employee'  => User::class,
            'task'      => Task::class,
        ];

        $limitField = $limitMap[$type];
        $modelClass = $modelMap[$type];
        $limit = $package->$limitField ?? 0;

        // Build the query
        $query = $modelClass::where('company_id', $companyId);

        // Exclude admin role for employee count
        if ($type === 'employee') {
            $query->whereHas('roles', fn($q) => $q->where('name', '!=', 'admin'));
        }

        $now = now();
        if ($packageType === 'monthly') {
            $query->whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month);
        } elseif ($packageType === 'yearly') {
            $query->whereYear('created_at', $now->year);
        }

        $currentCount = $query->count();

        if ($currentCount >= $limit) {
            return response()->json([
                'success' => false,
                'allowed' => false,
                'message' => "Limit reached for {$type}s in your {$packageType} package. Max allowed: {$limit}.",
            ], 403);
        }

        return response()->json([
            'success' => true,
            'allowed' => true,
            'message' => "You can create a new {$type}.",
        ]);
    }
}
