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
    public function index(Request $request)
    {
<<<<<<< HEAD
        $query = Expense::query();

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        return ExpenseResource::collection(
            $query->latest()->get()
        );
=======
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
>>>>>>> main
    }


    public function store(Request $request)
    {
        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company_id;

        $validator = Validator::make($request->all(), [
            'heading'     => ['required', 'string', 'max:255'],
            'price'       => ['required', 'numeric', 'min:0'],
            'status'      => ['nullable', 'in:pending,approved,rejected'],
            'file'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'description' => ['nullable', 'string'],
            'tags'        => ['nullable', 'array'],
            'tags.*.name' => ['required', 'string', 'max:50'],
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

        $data['tags'] = $data['tags'] ?? [];

        $expense = Expense::create($data);

        return (new ExpenseResource($expense))
            ->response()
            ->setStatusCode(201);
    }

    public function show($id)
    {
        $expense = Expense::findOrFail($id);
        return new ExpenseResource($expense);
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'heading'     => ['sometimes', 'string', 'max:255'],
            'price'       => ['sometimes', 'numeric', 'min:0'],
            'status'      => ['nullable', 'in:pending,approved,rejected'],
            'file'        => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'description' => ['nullable', 'string'],
            'tags'        => ['nullable', 'array'],
            'tags.*.name' => ['required', 'string', 'max:50'],
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

        if (array_key_exists('tags', $data)) {
            $data['tags'] = $data['tags'] ?? [];
        }

        $expense->update($data);

        return new ExpenseResource($expense->refresh());
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
