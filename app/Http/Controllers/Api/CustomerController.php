<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;

class CustomerController extends Controller
{
    public function index()
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $customers = Customer::where('company_id', $selectedCompany->id)->latest()->get();

        return response()->json(['customers' => $customers]);
    }

    public function store(Request $request)
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'number'=> 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['company_id'] = $selectedCompany->id;

        $customer = Customer::create($data);

        return response()->json([
            'message' => 'Customer created successfully',
            'customer' => $customer
        ], 201);
    }

    public function show($id)
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $customer = Customer::where('company_id', $selectedCompany->id)->find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        return response()->json(['customer' => $customer]);
    }

    public function update(Request $request, $id)
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $customer = Customer::where('company_id', $selectedCompany->id)->find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'  => 'sometimes|required|string|max:255',
            'number'=> 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer->update($validator->validated());

        return response()->json([
            'message' => 'Customer updated successfully',
            'customer' => $customer
        ]);
    }

    public function destroy($id)
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $customer = Customer::where('company_id', $selectedCompany->id)->find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $customer->delete();

        return response()->json(['message' => 'Customer deleted successfully']);
    }
}
