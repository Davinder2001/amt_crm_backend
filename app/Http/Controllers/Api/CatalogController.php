<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\SelectedCompanyService;

class CatalogController extends Controller
{
    // 1. Get all items in the catalog
    public function index(): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $catalogItems = Item::where('company_id', $selectedCompany->company_id)->where('catalog', true)->get();
        return response()->json($catalogItems);
    }

    // 2. Add item to catalog
    public function addToCatalog($id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $item = Item::where('company_id', $selectedCompany->company_id)->find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found.'], 404);
        }

        $item->catalog = true;
        $item->save();

        return response()->json(['message' => 'Item added to catalog successfully.', 'item' => $item]);
    }

    // 3. Remove item from catalog
    public function removeFromCatalog($id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $item = Item::where('company_id', $selectedCompany->company_id)->find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found.'], 404);
        }

        $item->catalog = false;
        $item->save();

        return response()->json(['message' => 'Item removed from catalog successfully.', 'item' => $item]);
    }
}
