<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VendorInvoice;
use App\Models\VendorPaymentHistory;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\{Item, StoreVendor, Category};
use Illuminate\Http\JsonResponse;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use App\Services\SelectedCompanyService;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CategoryTreeResource;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Request;

class BulkActionsController extends Controller
{
    /**
     * Get the item category tree.
     */
    public function getItemCatTree(): JsonResponse
    {
        $categories = Category::with([
            'childrenRecursive',
            'invoice_items.taxes',
            'invoice_items.categories',
            'invoice_items.batches.variants.attributeValues.attribute',
            'invoice_items.batches.item.taxes', // required for variant tax calculation
        ])->get();


        return response()->json(CategoryTreeResource::collection($categories), 200);
    }


    /**
     * Store multiple items in bulk.
     */
    public function storeBulkItems(Request $request): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $validator = Validator::make($request->all(), [
            'invoice_no'            => 'required|string|max:255',
            'vendor_name'           => 'required|string|max:255',
            'vendor_no'             => 'required|string|max:255',
            'vendor_email'          => 'nullable|string|email|max:255',
            'vendor_address'        => 'nullable|string|max:255',
            'bill_photo'            => 'nullable|file|image|max:2048',
            'items'                 => 'required|string',
            'tax_id'                => 'nullable|integer|exists:taxes,id',
            'payment_method'        => 'nullable|string|max:255',
            'credit_payment_type'   => 'nullable|string|max:255',
            'partial_amount'        => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data   = $validator->validated();
        $items  = json_decode($data['items'], true);

        if (!is_array($items)) {
            return response()->json([
                'message' => 'The items field must be a valid JSON array.',
            ], 422);
        }

        $vendor = StoreVendor::firstOrCreate(
            [
                'vendor_number' => $data['vendor_no'],
                'company_id'    => $selectedCompany->company_id,
            ],
            [
                'vendor_name'    => $data['vendor_name'],
                'vendor_email'   => $data['vendor_email'] ?? null,
                'vendor_address' => $data['vendor_address'] ?? null,
            ]
        );

        $invoice = VendorInvoice::create([
            'vendor_id'   => $vendor->id,
            'invoice_no'   => $data['invoice_no'],
            'invoice_date' => now(),
        ]);

        VendorPaymentHistory::create([
            'vendor_invoice_id'     => $invoice->id,
            'payment_method'        => $data['payment_method'] ?? null,
            'credit_payment_type'   => $data['credit_payment_type'] ?? null,
            'partial_amount'        => $data['partial_amount'] ?? null,
            'amount_paid'           => $data['partial_amount'] ?? 0,
            'payment_date'          => now(),
            'note'                  => $data['credit_payment_type'] === 'partial' ? 'Partial payment' : 'Full payment',
        ]);

        $imagePath = null;
        if ($request->hasFile('bill_photo')) {
            $image = $request->file('bill_photo');
            $filename = uniqid('bill_') . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/bills'), $filename);
            $imagePath = 'uploads/bills/' . $filename;
        }

        $taxPercentage = optional(Tax::find($data['tax_id']))->rate;

        $itemCostsWithTax = array_map(function ($item) use ($taxPercentage) {
            $price = (float) $item['price'];
            return round($price + ($taxPercentage ? $price * $taxPercentage / 100 : 0), 2);
        }, $items);

        $uncategorizedCategory = Category::firstOrCreate([
            'company_id' => $selectedCompany->company_id,
            'name'       => 'Uncategorized',
        ]);

        foreach ($items as $index => $itemData) {
            $item = Item::create([
                'company_id'          => $selectedCompany->company_id,
                'item_code'           => Item::where('company_id', $selectedCompany->company_id)->max('item_code') + 1 ?? 1,
                'name'                => $itemData['name'],
                'quantity_count'      => $itemData['quantity'],
                'invoice_id'          => $invoice->id,
                'cost_price'          => $itemCostsWithTax[$index],
                'measurement'         => null,
                'purchase_date'       => now(),
                'date_of_manufacture' => now(),
                'brand_name'          => $data['vendor_name'],
                'replacement'         => null,
                'category'            => null,
                'vendor_name'         => $data['vendor_name'],
                'vendor_id'           => $vendor->id,
                'availability_stock'  => $itemData['quantity'],
                'images'              => $imagePath ? json_encode([$imagePath]) : null,
            ]);

            DB::table('category_item')->insert([
                'store_item_id' => $item->id,
                'category_id'   => $uncategorizedCategory->id,
            ]);

            if (!empty($data['tax_id'])) {
                DB::table('item_tax')->insert([
                    'store_item_id' => $item->id,
                    'tax_id'        => $data['tax_id'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }

        return response()->json([
            'message'    => 'Bulk items, invoice, and payment history stored successfully.',
            'invoice_id' => $invoice->id,
            'vendor'     => $vendor->vendor_name,
            'count'      => count($items),
        ]);
    }

    /**
     * Export items inline to an Excel file.
     */
    public function exportInline(): BinaryFileResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $items = Item::with('categories')
            ->where('company_id', $selectedCompany->company_id)
            ->select([
                'id',
                'item_code',
                'name',
                'quantity_count',
                'measurement',
                'purchase_date',
                'date_of_manufacture',
                'date_of_expiry',
                'brand_name',
                'replacement',
                'vendor_name',
                'vendor_id',
                'availability_stock',
                'cost_price',
                'selling_price'
            ])
            ->get();

        $data = [[
            'Item Code',
            'Name',
            'Quantity Count',
            'Measurement',
            'Purchase Date',
            'Date of Manufacture',
            'Date of Expiry',
            'Brand Name',
            'Replacement',
            'Vendor Name',
            'Vendor ID',
            'Available Stock',
            'Cost Price',
            'Selling Price',
            'Categories',
        ]];

        foreach ($items as $item) {
            $categoryNames = $item->categories->pluck('name')->implode(', ');
            $data[] = [
                $item->item_code,
                $item->name,
                $item->quantity_count,
                $item->measurement,
                optional($item->purchase_date)->format('Y-m-d'),
                optional($item->date_of_manufacture)->format('Y-m-d'),
                optional($item->date_of_expiry)->format('Y-m-d'),
                $item->brand_name,
                $item->replacement,
                $item->vendor_name,
                $item->vendor_id ?? null,
                $item->availability_stock,
                $item->cost_price,
                $item->selling_price,
                $categoryNames,
            ];
        }

        $export = new class($data) implements \Maatwebsite\Excel\Concerns\FromArray {
            protected $data;
            public function __construct(array $data)
            {
                $this->data = $data;
            }
            public function array(): array
            {
                return $this->data;
            }
        };

        return Excel::download($export, 'items_export_with_categories.xlsx');
    }

    /**
     * Import items inline from an Excel file.
     */
    public function importInline(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyId       = $selectedCompany->company_id;

        try {
            $rows = Excel::toArray([], $request->file('file'))[0];

            unset($rows[0]);

            foreach ($rows as $row) {
                if (!isset($row[0]) || empty($row[0])) continue;

                $item = Item::create([
                    'item_code'             => $row[0],
                    'name'                  => $row[1],
                    'quantity_count'        => $row[2],
                    'measurement'           => $row[3],
                    'purchase_date'         => $row[4] ?? null,
                    'date_of_manufacture'   => $row[5] ?? null,
                    'date_of_expiry'        => $row[6] ?? null,
                    'brand_name'            => $row[7],
                    'replacement'           => $row[8],
                    'vendor_name'           => $row[9] ?? "NA",
                    'vendor_id'             => $row[10] ?? null,
                    'availability_stock'    => $row[11] ?? 0,
                    'cost_price'            => $row[12],
                    'selling_price'         => $row[13],
                    'company_id'            => $companyId,
                ]);

                if (!empty($row[14])) {
                    $categoryNames = explode(',', $row[14]);
                    $categoryIds = [];

                    foreach ($categoryNames as $catName) {
                        $trimmed = trim($catName);
                        if ($trimmed === '') continue;

                        $category = \App\Models\Category::firstOrCreate(
                            ['name' => $trimmed, 'company_id' => $companyId]
                        );

                        $categoryIds[] = $category->id;
                    }

                    if (!empty($categoryIds)) {
                        $item->categories()->sync($categoryIds);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Items imported successfully with categories.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete items.
     */
    public function bulkDeleteItems(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_id'   => 'required|array',
            'item_id.*' => 'integer|exists:store_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $itemIds         = $request->input('item_id');

        Item::whereIn('id', $itemIds)->where('company_id', $selectedCompany->company_id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Items deleted successfully.',
        ]);
    }
}
