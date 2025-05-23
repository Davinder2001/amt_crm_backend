<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ManagedTable;
use App\Models\TableMetaField;
use Illuminate\Support\Facades\Validator;

class CustomTableColumnController extends Controller
{
    /**
     * Create or update a managed table entry
     */
    public function storeManagedTable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id'    => 'required|exists:companies,id',
            'user_id'       => 'nullable|exists:users,id',
            'table_name'    => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'    => 'error',
                'errors'    => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $managedTable = ManagedTable::updateOrCreate(
            ['company_id'   => $validated['company_id'],
             'table_name'   => $validated['table_name']],
            ['user_id'      => $validated['user_id'] ?? null]
        );

        return response()->json([
            'status' => 'success',
            'table' => $managedTable
        ]);
    }

    /**
     * List all managed tables for a company
     */
    public function listManagedTables(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated  = $validator->validated();
        $tables     = ManagedTable::where('company_id', $validated['company_id'])
                    ->with('metaFields')
                    ->get();

        return response()->json([
            'status' => 'success', 
            'tables' => $tables
        ]);
    }

    /**
     * Add or update meta field for a managed table
     */
    public function storeMetaField(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'managed_table_id' => 'required|exists:managed_tables,id',
            'meta_key'         => 'required|string|max:255',
            'meta_value'       => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $meta = TableMetaField::updateOrCreate(
            ['managed_table_id' => $validated['managed_table_id'],
            'meta_key'          => $validated['meta_key']],
            ['meta_value'       => $validated['meta_value']]
        );

        return response()->json([
            'status' => 'success', 
            'meta' => $meta
        ]);
    }

    /**
     * List all meta fields for a specific managed table
     */
    public function listMetaFields($managedTableId)
    {
        $metaFields = TableMetaField::where('managed_table_id', $managedTableId)->get();

        return response()->json([
            'status' => 'success', 
            'meta_fields' => $metaFields
        ]);
    }

    /**
     * Delete a meta field
     */
    public function deleteMetaField($id)
    {
        $meta = TableMetaField::findOrFail($id);
        $meta->delete();

        return response()->json([
            'status' => 'success', 
            'message' => 'Meta field deleted successfully.'
        ]);
    }

    /**
     * Delete a managed table and its meta fields
     */
    public function deleteManagedTable($id)
    {
        $table = ManagedTable::findOrFail($id);
        $table->delete();

        return response()->json([
            'status' => 'success', 
            'message' => 'Managed table deleted.'
        ]);
    }
}
