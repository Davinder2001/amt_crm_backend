<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TableManagement;
use App\Models\TableMeta;
use Illuminate\Support\Facades\Auth;
use App\Services\SelectedCompanyService;
use App\Models\TablesListed;
use App\Models\TableColumn;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TableColumnController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table_id'          => 'required|exists:tables_listed,id',
            'meta'              => 'required|array|min:1',
            'meta.*.col_name'   => 'required|string|max:255',
            'meta.*.value'      => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors'  => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {

            $activeCompanyId    = SelectedCompanyService::getSelectedCompanyOrFail();
            $userId             = Auth::id();

            $table = TableManagement::updateOrCreate(
                [
                    'company_id' => $activeCompanyId->company->id,
                    'user_id'    => $userId,
                ],
                [
                    'tables_listed_id' => $request->table_id,
                ]
            );

            $trueCols = [];

            foreach ($request->meta as $metaData) {
                $colName = $metaData['col_name'];
                $value = $metaData['value'];

                if ($value === true) {
                    TableMeta::updateOrCreate(
                        ['table_id' => $table->id, 'col_name' => $colName],
                        ['value' => true]
                    );

                    $trueCols[] = $colName;
                } else {

                    TableMeta::where('table_id', $table->id)->where('col_name', $colName)->delete();
                }
            }

            TableMeta::where('table_id', $table->id)->whereNotIn('col_name', $trueCols)->delete();
            DB::commit();

            return response()->json([
                'message' => 'Table and its columns processed successfully.',
                'data'    => $table->load('metas')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process table.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function storeTable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $table = TablesListed::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Table created successfully.',
            'data'    => $table,
        ]);
    }


    public function storeColumn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table_id'     => 'required|exists:tables_listed,id',
            'column_name'  => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $column = TableColumn::create([
            'table_id'     => $request->table_id,
            'column_name'  => $request->column_name,
        ]);

        return response()->json([
            'message' => 'Column added successfully.',
            'data'    => $column,
        ]);
    }
}
