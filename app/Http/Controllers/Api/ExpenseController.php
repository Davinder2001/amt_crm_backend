<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $expenses = Expense::with(['items', 'invoices', 'users'])->get();

        return ExpenseResource::collection($expenses);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company_id;

        $validator = Validator::make($request->all(), [
            'heading'      => ['required', 'string', 'max:255'],
            'price'        => ['required', 'numeric', 'min:0'],
            'status'       => ['nullable', 'in:pending,approved,rejected'],
            'file'         => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'description'  => ['nullable', 'string'],
            'invoice_ids'  => ['nullable', 'array'],
            'invoice_ids.*' => ['integer', 'exists:invoices,id'],
            'item_ids'     => ['nullable', 'array'],
            'item_ids.*'   => ['integer', 'exists:store_items,id'],
            'user_ids'  => ['nullable', 'array'], // ready if needed
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['company_id'] = $companyId;

        if ($request->hasFile('file')) {
            $data['file_path'] = $this->saveFile($request->file('file'));
        }

        $expense = Expense::create($data);

        $expense->invoices()->sync($data['invoice_ids'] ?? []);
        $expense->items()->sync($data['item_ids'] ?? []);

        return (new ExpenseResource($expense->refresh()))->response()->setStatusCode(201);
    }


    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \App\Http\Resources\ExpenseResource
     */
    public function show($id)
    {
        $expense = Expense::findOrFail($id);
        return new ExpenseResource($expense);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \App\Http\Resources\ExpenseResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'heading'      => ['sometimes', 'string', 'max:255'],
            'price'        => ['sometimes', 'numeric', 'min:0'],
            'status'       => ['nullable', 'in:pending,approved,rejected'],
            'file'         => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'description'  => ['nullable', 'string'],
            'invoice_ids'  => ['nullable', 'array'],
            'invoice_ids.*' => ['integer', 'exists:invoices,id'],
            'item_ids'     => ['nullable', 'array'],
            'item_ids.*'   => ['integer', 'exists:items,id'],
            // 'user_ids'  => ['nullable', 'array'],
            // 'user_ids.*'=> ['integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('file')) {
            if ($expense->file_path && File::exists(public_path($expense->file_path))) {
                File::delete(public_path($expense->file_path));
            }
            $data['file_path'] = $this->saveFile($request->file('file'));
        }

        $expense->update($data);

        // Sync relationships
        if (isset($data['invoice_ids'])) {
            $expense->invoices()->sync($data['invoice_ids']);
        }
        if (isset($data['item_ids'])) {
            $expense->items()->sync($data['item_ids']);
        }
        // if (isset($data['user_ids'])) {
        //     $expense->users()->sync($data['user_ids']);
        // }

        return new ExpenseResource($expense->refresh());
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);

        if ($expense->file_path && File::exists(public_path($expense->file_path))) {
            File::delete(public_path($expense->file_path));
        }

        $expense->delete();

        return response()->json(['message' => 'Expense deleted.']);
    }

    /**
     * Save the uploaded file and return its path.
     *
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     * @return string
     */
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
