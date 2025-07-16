<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10); 

        $expenses = Expense::with(['items', 'invoices', 'users'])->latest()->paginate($perPage);

        return response()->json([
            'status' => true,
            'expenses' => ExpenseResource::collection($expenses->items()),
            'pagination' => [
                'current_page'   => $expenses->currentPage(),
                'per_page'       => $expenses->perPage(),
                'total'          => $expenses->total(),
                'last_page'      => $expenses->lastPage(),
                'next_page_url'  => $expenses->nextPageUrl(),
                'prev_page_url'  => $expenses->previousPageUrl(),
            ]
        ]);
    }


    public function store(Request $request)
    {
        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company_id;

        $validator = Validator::make($request->all(), [
            'heading'                   => ['required', 'string', 'max:255'],
            'price'                     => ['required', 'numeric', 'min:0'],
            'status'                    => ['nullable', 'in:pending,approved,paid,rejected'],
            'file'                      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'description'               => ['nullable', 'string'],
            'invoice_ids'               => ['nullable', 'array'],
            'invoice_ids.*'             => ['integer', 'exists:invoices,id'],
            'user_ids'                  => ['nullable', 'array'],
            'user_ids.*'                => ['integer', 'exists:users,id'],
            'items_batches'            => ['nullable', 'array'],
            'items_batches.*.item_id'  => ['required', 'integer', 'exists:store_items,id'],
            'items_batches.*.batch_id' => ['required', 'integer', 'exists:item_batches,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['company_id'] = $companyId;

        if ($request->hasFile('file')) {
            $data['file_path'] = $this->saveFile($request->file('file'));
        }

        DB::beginTransaction();
        try {
            $expense = Expense::create($data);

            // Sync pivot relationships
            $expense->invoices()->sync($data['invoice_ids'] ?? []);
            $expense->users()->sync($data['user_ids'] ?? []);

            if (!empty($data['items_batches'])) {
                $pivotData = [];
                foreach ($data['items_batches'] as $pair) {
                    $pivotData[$pair['item_id']] = ['batch_id' => $pair['batch_id']];
                }
                $expense->items()->sync($pivotData);
            }

            DB::commit();
            return (new ExpenseResource($expense->refresh()))->response()->setStatusCode(201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $expense = Expense::with(['items', 'invoices', 'users'])->findOrFail($id);
        return new ExpenseResource($expense);
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'heading'                   => ['sometimes', 'string', 'max:255'],
            'price'                     => ['sometimes', 'numeric', 'min:0'],
            'status'                    => ['nullable', 'in:pending,approved,paid,rejected'],
            'file'                      => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'description'               => ['nullable', 'string'],
            'invoice_ids'               => ['nullable', 'array'],
            'invoice_ids.*'             => ['integer', 'exists:invoices,id'],
            'user_ids'                  => ['nullable', 'array'],
            'user_ids.*'                => ['integer', 'exists:users,id'],
            'items_batches'            => ['nullable', 'array'],
            'items_batches.*.item_id'  => ['required', 'integer', 'exists:store_items,id'],
            'items_batches.*.batch_id' => ['required', 'integer', 'exists:item_batches,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('file')) {
            if ($expense->file_path && File::exists(public_path($expense->file_path))) {
                File::delete(public_path($expense->file_path));
            }
            $data['file_path'] = $this->saveFile($request->file('file'));
        }

        DB::beginTransaction();
        try {
            $expense->update($data);

            if (isset($data['invoice_ids'])) {
                $expense->invoices()->sync($data['invoice_ids']);
            }

            if (isset($data['user_ids'])) {
                $expense->users()->sync($data['user_ids']);
            }

            if (isset($data['items_batches'])) {
                $pivotData = [];
                foreach ($data['items_batches'] as $pair) {
                    $pivotData[$pair['item_id']] = ['batch_id' => $pair['batch_id']];
                }
                $expense->items()->sync($pivotData);
            }

            DB::commit();
            return new ExpenseResource($expense->refresh());
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);

        if ($expense->file_path && File::exists(public_path($expense->file_path))) {
            File::delete(public_path($expense->file_path));
        }

        $expense->delete();

        return response()->json(['message' => 'Expense deleted.']);
    }

    protected function saveFile($uploadedFile): string
    {
        $uploadDir = public_path('uploads/expenses');

        if (!File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $filename = time() . '_' . preg_replace('/\s+/', '_', $uploadedFile->getClientOriginalName());
        $uploadedFile->move($uploadDir, $filename);

        return 'uploads/expenses/' . $filename;
    }
}
