<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;

class ExpenseController extends Controller
{
    /* --------------------------------------------------------------
     |  List expenses for the selected company
     * --------------------------------------------------------------*/
    public function index()
    {
        $expenses = Expense::get();
        return response()->json($expenses);
    }

    /* --------------------------------------------------------------
     |  Store a new expense
     * --------------------------------------------------------------*/
    public function store(Request $request)
    {
        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company_id;

        $validator = Validator::make($request->all(), [
            'heading'     => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'file'        => 'required|file|mimes:jpg,jpeg,png,pdf,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data               = $validator->validated();
        $data['company_id'] = $companyId;

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('expenses', 'public');
        }

        $expense = Expense::create($data);

        return response()->json($expense, 201);
    }

    /* -------------------------------------------------------------- */
    public function show(Expense $id)
    {
        $expense = Expense::get($id);
        
        if ($expense) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        return response()->json($expense);
    }

    /* -------------------------------------------------------------- */
    public function update(Request $request, Expense $id)
    {
        
         $expense = Expense::get($id);
        
        if ($expense) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'heading'     => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'file'        => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('file')) {
            if ($expense->file_path) {
                Storage::disk('public')->delete($expense->file_path);
            }
            $data['file_path'] = $request->file('file')->store('expenses', 'public');
        }

        $expense->update($data);

        return response()->json($expense);
    }

    /* -------------------------------------------------------------- */
    public function destroy(Expense $expense)
    {
        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company_id;

        if ($expense->company_id !== $companyId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($expense->file_path) {
            Storage::disk('public')->delete($expense->file_path);
        }

        $expense->delete();

        return response()->json(['message' => 'Expense deleted.']);
    }
}
