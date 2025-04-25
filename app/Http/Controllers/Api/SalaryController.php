<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SalaryHistory;
use App\Http\Resources\SalaryResource;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

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


    /**
     * Generate salary slip for the specified employee.
     */
    public function salarySlip($id)
    {
        $employee = User::where('user_type', 'employee')->with(['roles.permissions', 'companies', 'meta'])->find($id); 
    
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Employee not found.'
            ], 404);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Employee retrieved successfully.',
            'employee' => new SalaryResource($employee),
        ], 200);
    }


    /**
     * Download the salary slip as a PDF.
     */
    public function downloadPdfSlip($id)
    {
        $user           =   User::where('user_type', 'employee')
                            ->with(['roles.permissions', 'companies', 
                            'meta', 'salaryHistories', 'employeeDetail'])
                            ->findOrFail($id);

        $employeeData   =   (new SalaryResource($user))->toArray(request());
    
        $pdf = PDF::loadView('pdf.salary-slip', [
            'employee' => $employeeData
        ]);
    
        return response()->json([
            'status'     => true,
            'message'    => 'PDF generated successfully.',
            'pdf_base64' => base64_encode($pdf->output()),
            'file_name'  => 'salary-slip-' . $user->id . '.pdf',
        ]);
    }
}