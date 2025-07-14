<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\TableManagement;
use App\Models\TableMeta;
use App\Services\SelectedCompanyService;

class TableColumnController extends Controller
{
    /**
     * Store a new table
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'table_name' => 'required|string|max:255',
            'table_description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $company = SelectedCompanyService::getSelectedCompanyOrFail();
        
        $table = TableManagement::create([
            'table_name' => $request->table_name,
            'table_description' => $request->table_description,
            'company_id' => $company->company_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Table created successfully.',
            'data' => $table
        ], 201);
    }

    /**
     * Store a new table with columns
     */
    public function storeTable(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'table_name' => 'required|string|max:255',
            'table_description' => 'nullable|string',
            'columns' => 'required|array|min:1',
            'columns.*.column_name' => 'required|string|max:255',
            'columns.*.column_type' => 'required|string|max:100',
            'columns.*.is_required' => 'boolean',
            'columns.*.default_value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $company = SelectedCompanyService::getSelectedCompanyOrFail();
        
        $table = TableManagement::create([
            'table_name' => $request->table_name,
            'table_description' => $request->table_description,
            'company_id' => $company->company_id,
        ]);

        foreach ($request->columns as $column) {
            TableMeta::create([
                'table_id' => $table->id,
                'column_name' => $column['column_name'],
                'column_type' => $column['column_type'],
                'is_required' => $column['is_required'] ?? false,
                'default_value' => $column['default_value'] ?? null,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Table with columns created successfully.',
            'data' => $table->load('metas')
        ], 201);
    }

    /**
     * Store a new column for existing table
     */
    public function storeColumn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:table_managements,id',
            'column_name' => 'required|string|max:255',
            'column_type' => 'required|string|max:100',
            'is_required' => 'boolean',
            'default_value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $column = TableMeta::create([
            'table_id' => $request->table_id,
            'column_name' => $request->column_name,
            'column_type' => $request->column_type,
            'is_required' => $request->is_required ?? false,
            'default_value' => $request->default_value ?? null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Column created successfully.',
            'data' => $column
        ], 201);
    }
} 