<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;

class ExpenseController extends Controller
{
    /**
     * Get the validation rules for storing/updating an expense.
     *
     * @param bool $isStore
     * @return array
     */
    protected function rules($isStore = true)
    {
        $rules = [
            'heading'        => ['required', 'string', 'max:255'],
            'price'      => ['required', 'numeric', 'min:0'],
            'status'      => ['nullable', 'string', 'in:pending,approved,rejected'],
            'file'        => [$isStore ? 'required' : 'sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'description' => ['nullable', 'string'],
            // add other fields as needed
        ];

        if (!$isStore) {
            // For update, file is not required
            $rules['file'][0] = 'sometimes';
        }

        return $rules;
    }

    /* --------------------------------------------------------------
     |  List / index
     * -------------------------------------------------------------*/
    public function index()
    {
        $query = Expense::query();

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        // If you prefer pagination, swap ->get() for ->paginate(20)
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

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')
                                         ->store('expenses', 'public');
        }

        $expense = Expense::create($data);

        return (new ExpenseResource($expense))->response()->setStatusCode(201);
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

        if ($request->hasFile('file')) {
            if ($expense->file_path) {
                Storage::disk('public')->delete($expense->file_path);
            }
            $data['file_path'] = $request->file('file')
                                         ->store('expenses', 'public');
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

        if ($expense->file_path) {
            Storage::disk('public')->delete($expense->file_path);
        }

        $expense->delete();

        return response()->json(['message' => 'Expense deleted.']);
    }
}
