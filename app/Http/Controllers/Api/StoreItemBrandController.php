<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreItemBrand;
use Illuminate\Http\Request;
use App\Services\SelectedCompanyService;

class StoreItemBrandController extends Controller
{
    protected $selectCompanyService;

    public function __construct(SelectedCompanyService $selectCompanyService)
    {
        $this->selectCompanyService = $selectCompanyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return StoreItemBrand::get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $company = $this->selectCompanyService->getSelectedCompanyOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $storeItemBrand = StoreItemBrand::create([
            'name' => $validated['name'],
            'company_id' => $company->id,
        ]);

        return response()->json($storeItemBrand, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(StoreItemBrand $storeItemBrand)
    {
        $company = $this->selectCompanyService->getSelectedCompanyOrFail();

        if ($storeItemBrand->company_id !== $company->id) {
            abort(403, 'This brand does not belong to your selected company');
        }

        return $storeItemBrand;
    }

    /**
     * Update the specified resource in storage by ID.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $storeItemBrand = StoreItemBrand::findOrFail($id);
        $storeItemBrand->update($validated);

        return response()->json($storeItemBrand, 200);
    }

    /**
     * Remove the specified resource from storage by ID.
     */
    public function destroy($id)
    {
        $storeItemBrand = StoreItemBrand::findOrFail($id);
        $storeItemBrand->delete();

        return response()->json(null, 204);
    }
}
