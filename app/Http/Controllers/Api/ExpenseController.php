<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;          // <— use File instead of Storage
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;

class ExpenseController extends Controller
{
    /* --------------------------------------------------------------
     |  Centralised validation rules
     * -------------------------------------------------------------*/
    protected function rules(bool $isStore = true): array
    {
        return [
            'heading'     => ['required', 'string', 'max:255'],
            'price'       => ['required', 'numeric', 'min:0'],
            'status'      => ['nullable', 'in:pending,approved,rejected'],
            'file'        => [$isStore ? 'required' : 'sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'description' => ['nullable', 'string'],
        ];
    }

    /* --------------------------------------------------------------
     |  List
     * -------------------------------------------------------------*/
    public function index()
    {
        $query = Expense::query();

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        return ExpenseResource::collection(
            $query->latest()->get()
        );
    }

    /* --------------------------------------------------------------
     |  Store
     * -------------------------------------------------------------*/
    public function store(Request $request)
    {
        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company_id;

        $validator = Validator::make($request->all(), $this->rules(true));
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data               = $validator->validated();
        $data['company_id'] = $companyId;

        /* ----------  Handle file upload directly to /public/uploads/expenses  ---------- */
        if ($request->hasFile('file')) {
            $data['file_path'] = $this->saveFile($request->file('file'));
        }

        $expense = Expense::create($data);

        return (new ExpenseResource($expense))
               ->response()
               ->setStatusCode(201);
    }

    /* --------------------------------------------------------------
     |  Show
     * -------------------------------------------------------------*/
    public function show(Expense $expense)
    {
        return new ExpenseResource($expense);
    }

    /* --------------------------------------------------------------
     |  Update
     * -------------------------------------------------------------*/
    public function update(Request $request, Expense $expense)
    {
        $validator = Validator::make($request->all(), $this->rules(false));
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        /* ----------  Replace file if a new one is sent  ---------- */
        if ($request->hasFile('file')) {
            // delete old file if it exists
            if ($expense->file_path && File::exists(public_path($expense->file_path))) {
                File::delete(public_path($expense->file_path));
            }
            // save new file
            $data['file_path'] = $this->saveFile($request->file('file'));
        }

        $expense->update($data);

        return new ExpenseResource($expense->refresh());
    }

    /* --------------------------------------------------------------
     |  Destroy
     * -------------------------------------------------------------*/
    public function destroy(Expense $expense)
    {
        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company_id;

        if ($expense->company_id !== $companyId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // delete associated file
        if ($expense->file_path && File::exists(public_path($expense->file_path))) {
            File::delete(public_path($expense->file_path));
        }

        $expense->delete();

        return response()->json(['message' => 'Expense deleted.']);
    }

    /* ==============================================================
     |  Helper: save file to /public/uploads/expenses and return path
     * ==============================================================*/
    protected function saveFile($uploadedFile): string
    {
        $uploadDir = public_path('uploads/expenses');

        // ensure directory exists
        if (!File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $filename = time() . '_' . preg_replace('/\s+/', '_', $uploadedFile->getClientOriginalName());
        $uploadedFile->move($uploadDir, $filename);

        // store relative path in DB
        return 'uploads/expenses/' . $filename;
    }
}
