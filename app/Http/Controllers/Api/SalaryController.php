<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SalaryHistory;
use App\Http\Resources\SalaryResource;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = User::with(['roles.permissions', 'companies', 'employeeDetail', 'salaryHistories'])->where('user_type', 'employee')->get();
        return SalaryResource::collection($employees);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $employee = User::with(['roles.permissions', 'companies', 'employeeDetail', 'salaryHistories'])->where('user_type', 'employee')->findOrFail($id);
        return new SalaryResource($employee);
    }

    
    /**
     * Store increment.
     */
    public function increment(Request $request, $id)
    {
        $data = $request->validate([
            'new_salary' => 'required|numeric|min:0',
            'reason'     => 'nullable|string',
        ]);

        $employee = User::with('employeeDetail')->where('user_type', 'employee')->findOrFail($id);

        $employeeDetail = $employee->employeeDetail;
        if (!$employeeDetail) {
            return response()->json(['message' => 'Employee detail not found.'], 404);
        }

        $previousSalary = $employeeDetail->salary;
        $employeeDetail->salary = $data['new_salary'];
        $employeeDetail->save();

        SalaryHistory::create([
            'user_id'         => $employee->id,
            'previous_salary' => $previousSalary,
            'new_salary'      => $data['new_salary'],
            'increment_date'  => now(),
            'reason'          => $data['reason'] ?? 'Salary Increment',
        ]);

        return response()->json([
            'message' => 'Salary incremented successfully.',
            'employee' => new SalaryResource($employee->load('roles.permissions', 'companies', 'employeeDetail', 'salaryHistories')),
        ]);
    }
}
