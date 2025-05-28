<?php

namespace App\Http\Controllers\Api;

use App\Models\{Item, StoreVendor, CategoryItem, Category};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Tax;
use App\Models\Package;
use App\Models\ItemTax;
use App\Models\VendorInvoice;
use App\Models\VendorPaymentHistory;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;
use App\Http\Resources\ItemResource;
use App\Http\Resources\CategoryTreeResource;
use App\Models\TableMeta;
use App\Models\TableManagement;
use Illuminate\Support\Facades\Auth;



class ItemsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index(): JsonResponse
    // {
    //     $items = Item::with(['variants.attributeValues.attribute', 'taxes', 'categories'])->get();
    //     return response()->json(ItemResource::collection($items));
    // }


    public function index(): JsonResponse
    {
        $activeCompanyId = SelectedCompanyService::getSelectedCompanyOrFail()->company->id;
        $userId          = Auth::id();
        $tableName       = 'items';
        $table           = TableManagement::where('company_id', $activeCompanyId)->where('user_id', $userId)->where('table_name', $tableName)->first();
        $defaultColumns  = ['id', 'name'];
        $columns         = $defaultColumns;
        $relations       = [];

        if ($table) {

            $metaColumns = TableMeta::where('table_id', $table->id)->where('value', true)->pluck('col_name')->toArray();

            if (!in_array('id', $metaColumns)) {
                $metaColumns[] = 'id';
            }

            if (!in_array('name', $metaColumns)) {
                $metaColumns[] = 'name';
            }

            $columns = $metaColumns;

            if (in_array('variants', $columns)) {
                $relations[] = 'variants.attributeValues.attribute';
                $columns     = array_filter($columns, fn($col) => $col !== 'variants');
            }

            if (in_array('taxes', $columns)) {
                $relations[] = 'taxes';
                $columns     = array_filter($columns, fn($col) => $col !== 'taxes');
            }

            if (in_array('categories', $columns)) {
                $relations[] = 'categories';
                $columns     = array_filter($columns, fn($col) => $col !== 'categories');
            }
        }

        $items = Item::select($columns)->with($relations)->get();

        return response()->json($items);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $selectedCompany    = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyId          = $selectedCompany->company_id;

        $validator = Validator::make($request->all(), [
            'name'                      => 'required|string|max:255',
            'quantity_count'            => 'required|integer',
            'measurement'               => 'nullable|string',
            'purchase_date'             => 'nullable|date',
            'date_of_manufacture'       => 'required|date',
            'date_of_expiry'            => 'nullable|date',
            'brand_name'                => 'required|string|max:255',
            'replacement'               => 'nullable|string|max:255',
            'categories'                => 'nullable|array',
            'categories.*'              => 'integer|exists:categories,id',
            'vendor_name'               => 'nullable|string|max:255',
            'cost_price'                => 'required|numeric|min:0',
            'selling_price'             => 'required|numeric|min:0',
            'availability_stock'        => 'required|integer',
            'images.*'                  => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'variants'                  => 'nullable|array',
            'variants.*.price'          => 'required_with:variants|numeric|min:0',
            'variants.*.ragular_price'  => 'nullable:variants|numeric|min:0',
            'variants.*.stock'          => 'nullable|integer|min:0',
            'variants.*.attributes'     => 'required_with:variants|array',
            'tax_id'                    => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data               = $validator->validated();
        $data['company_id'] = $companyId;
        $package            = Package::find($selectedCompany->company->package_id ?? 1);
        $packageType        = $package->package_type ?? 'monthly';
        $allowedItemCount   = $package->items_number ?? 0;
        $now                = now();

        $itemQuery = Item::where('company_id', $companyId);
        if ($packageType === 'monthly') {
            $itemQuery->whereYear('created_at', $now->year)->whereMonth('created_at', $now->month);
        } else {
            $itemQuery->whereYear('created_at', $now->year);
        }

        if ($itemQuery->count() >= $allowedItemCount) {
            return response()->json([
                'success' => false,
                'message' => "Item limit reached for your {$packageType} package. Allowed: {$allowedItemCount} items.",
            ], 403);
        }

        try {

            $lastItemCode = Item::where('company_id', $companyId)
                ->select(DB::raw('MAX(CAST(item_code AS UNSIGNED)) as max_code'))
                ->value('max_code');
            $data['item_code'] = $lastItemCode ? $lastItemCode + 1 : 1;

            if (!empty($data['vendor_name'])) {
                StoreVendor::firstOrCreate([
                    'vendor_name' => $data['vendor_name'],
                    'company_id'  => $companyId,
                ]);
            }

            $imageLinks = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $filename = uniqid('item_') . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('uploads/items'), $filename);
                    $imageLinks[] = asset('uploads/items/' . $filename);
                }
            }
            $data['images'] = $imageLinks;
            $item           = Item::create($data);

            if (!empty($data['variants'])) {
                foreach ($data['variants'] as $variantData) {
                    $variant = $item->variants()->create([
                        'regular_price' => $variantData['regular_price'] ?? 0,
                        'price'         => $variantData['price'],
                        'stock'         => $variantData['stock'] ?? 1,
                        'images'        => $imageLinks,
                    ]);

                    foreach ($variantData['attributes'] as $attribute) {
                        $variant->attributeValues()->attach($attribute['attribute_value_id'], [
                            'attribute_id' => $attribute['attribute_id'],
                        ]);
                    }
                }
            }

            if (!empty($data['categories'])) {
                foreach ($data['categories'] as $categoryId) {
                    CategoryItem::create([
                        'store_item_id' => $item->id,
                        'category_id'   => $categoryId,
                    ]);
                }
            } else {
                $uncategorized = Category::firstOrCreate([
                    'company_id' => $companyId,
                    'name'       => 'Uncategorized',
                ]);
                CategoryItem::create([
                    'store_item_id' => $item->id,
                    'category_id'   => $uncategorized->id,
                ]);
            }

            if (!empty($data['tax_id'])) {
                ItemTax::create([
                    'store_item_id' => $item->id,
                    'tax_id'        => $data['tax_id'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Item added successfully.',
                'item'    => new ItemResource($item->load('variants.attributeValues', 'categories')),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating the item.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        $selectedCompany    = SelectedCompanyService::getSelectedCompanyOrFail();
        $item               = Item::with(['variants.attributeValues.attribute', 'taxes', 'categories'])->find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found.'], 404);
        }

        if (!$selectedCompany->super_admin && $item->company_id !== $selectedCompany->company_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json(new ItemResource($item));
    }



    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'name'                      => 'nullable|string|max:255',
            'quantity_count'            => 'nullable|integer',
            'measurement'               => 'nullable|string',
            'purchase_date'             => 'nullable|date',
            'date_of_manufacture'       => 'nullable|date',
            'date_of_expiry'            => 'nullable|date',
            'brand_name'                => 'nullable|string|max:255',
            'replacement'               => 'nullable|string|max:255',
            'categories'                => 'nullable|array',
            'categories.*'              => 'integer|exists:categories,id',
            'vendor_name'               => 'nullable|string|max:255',
            'cost_price'                => 'nullable|numeric|min:0',
            'selling_price'             => 'nullable|numeric|min:0',
            'availability_stock'        => 'nullable|integer',
            'images.*'                  => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'variants'                  => 'nullable|array',
            'variants.*.price'          => 'required_with:variants|numeric|min:0',
            'variants.*.regular_price'  => 'nullable|numeric|min:0',
            'variants.*.stock'          => 'nullable|integer|min:0',
            'variants.*.attributes'     => 'required_with:variants|array',
            'tax_id'                    => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data               = $validator->validated();
        $selectedCompany    = SelectedCompanyService::getSelectedCompanyOrFail();
        $item               = Item::with('variants', 'categories')->find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found.'], 404);
        }

        if (!$selectedCompany->super_admin && $item->company_id !== $selectedCompany->company_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (!empty($data['vendor_name'])) {
            StoreVendor::firstOrCreate([
                'vendor_name' => $data['vendor_name'],
                'company_id'  => $selectedCompany->company_id,
            ]);
        }

        $imageLinks = $item->images ?? [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = uniqid('item_') . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/items'), $filename);
                $imageLinks[] = asset('uploads/items/' . $filename);
            }
        }

        $data['images'] = $imageLinks;

        if (empty($item->item_code)) {
            $lastItemCode = Item::where('company_id', $item->company_id)
                ->select(DB::raw('MAX(CAST(item_code AS UNSIGNED)) as max_code'))
                ->value('max_code');
            $data['item_code'] = $lastItemCode ? $lastItemCode + 1 : 1;
        }

        $item->update($data);
        $item->variants()->delete();

        if (!empty($data['variants'])) {
            foreach ($data['variants'] as $variantData) {
                $variant = $item->variants()->create([
                    'regular_price' => $variantData['regular_price'] ?? 0,
                    'price'         => $variantData['price'],
                    'stock'         => $variantData['stock'] ?? 1,
                    'images'        => $imageLinks,
                ]);

                foreach ($variantData['attributes'] as $attribute) {
                    $variant->attributeValues()->attach($attribute['attribute_value_id'], [
                        'attribute_id' => $attribute['attribute_id'],
                    ]);
                }
            }
        }

        CategoryItem::where('store_item_id', $item->id)->delete();

        if (!empty($data['categories']) && is_array($data['categories'])) {
            foreach ($data['categories'] as $categoryId) {
                CategoryItem::create([
                    'store_item_id' => $item->id,
                    'category_id'   => $categoryId,
                ]);
            }
        } else {
            $uncategorized = Category::firstOrCreate([
                'company_id' => $selectedCompany->company_id,
                'name'       => 'Uncategorized',
            ]);

            CategoryItem::create([
                'store_item_id' => $item->id,
                'category_id'   => $uncategorized->id,
            ]);
        }

        if (!empty($data['tax_id'])) {
            $taxExists = Tax::where('id', $data['tax_id'])->exists();

            if (!$taxExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid tax ID. No such tax exists.'
                ], 404);
            }

            ItemTax::updateOrCreate(
                ['store_item_id' => $item->id],
                ['tax_id' => $data['tax_id']]
            );
        }

        return response()->json([
            'message' => 'Item updated successfully.',
            'item'    => new ItemResource($item->load('variants.attributeValues', 'categories')),
        ]);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        $selectedCompany    = SelectedCompanyService::getSelectedCompanyOrFail();
        $item               = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found.'], 404);
        }

        if (!$selectedCompany->super_admin && $item->company_id !== $selectedCompany->company_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $item->delete();
        return response()->json(['message' => 'Item deleted successfully.']);
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
            'invoice_no'  => $data['invoice_no'],
            'invoice_date'=> now(),
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
     * Get the item category tree.
     */
    public function getItemCatTree(): JsonResponse
    {
        $categories = Category::with(['childrenRecursive', 'items.variants.attributeValues.attribute', 'items.taxes', 'items.categories',])->get();
        return response()->json(CategoryTreeResource::collection($categories), 200);
    }




    public function exportInline(): BinaryFileResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $items = Item::where('company_id', $selectedCompany->company_id)
            ->select([
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
            'Available Stock',
            'Cost Price',
            'Selling Price',
        ]];

        foreach ($items as $item) {
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
            ];
        }

        // Export
        $export = new class($data) implements FromArray {
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

        return Excel::download($export, 'items_export.xlsx');
    }


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
            $path = $request->file('file')->getRealPath();
            $rows = Excel::toArray([], $path)[0];

            unset($rows[0]);

            foreach ($rows as $row) {
                if (!isset($row[0]) || empty($row[0])) continue;

                Item::create([
                    'item_code'             => $row[0],
                    'name'                  => $row[1],
                    'quantity_count'        => $row[2],
                    'measurement'           => $row[3],
                    'purchase_date'         => $row[4] ?? null,
                    'date_of_manufacture'   => $row[5] ?? null,
                    'date_of_expiry'        => $row[6] ?? null,
                    'brand_name'            => $row[7],
                    'replacement'           => $row[8],
                    'vendor_name'           => $row[9],
                    'vendor_id'             => $row[10] ?? null,
                    'availability_stock'    => $row[11],
                    'cost_price'            => $row[12],
                    'selling_price'         => $row[13],
                    'company_id'            => $companyId,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Items imported successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    // public function importInline(Request $request): JsonResponse
    // {
    //     $validator = Validator::make($request->all(), [
    //         'file' => 'required|file|mimes:xlsx,xls',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Invalid file.',
    //             'errors' => $validator->errors(),
    //         ], 422);
    //     }

    //     $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
    //     $companyId       = $selectedCompany->company_id;

    //     try {
    //         $path = $request->file('file')->getRealPath();
    //         $rows = Excel::toArray([], $path)[0]; // First sheet
    //         unset($rows[0]); // Remove headers

    //         $inserted = 0;
    //         $updated  = 0;
    //         $skipped  = 0;

    //         foreach ($rows as $row) {
    //             if (!isset($row[0]) || empty($row[0])) continue;

    //             $existing = Item::where('company_id', $companyId)
    //                 ->where('item_code', $row[0])
    //                 ->first();

    //             $fields = [
    //                 'item_code'          => $row[0],
    //                 'name'               => $row[1],
    //                 'quantity_count'     => $row[2],
    //                 'measurement'        => $row[3],
    //                 'purchase_date'      => $row[4] ?? null,
    //                 'date_of_manufacture' => $row[5] ?? null,
    //                 'date_of_expiry'     => $row[6] ?? null,
    //                 'brand_name'         => $row[7],
    //                 'replacement'        => $row[8],
    //                 'vendor_name'        => $row[9],
    //                 'availability_stock' => $row[10],
    //                 'cost_price'         => $row[11],
    //                 'selling_price'      => $row[12],
    //                 'company_id'         => $companyId,
    //             ];

    //             if ($existing) {
    //                 $diff = [];
    //                 foreach ($fields as $key => $value) {
    //                     if ($existing->{$key} != $value) {
    //                         $diff[$key] = $value;
    //                     }
    //                 }

    //                 if (!empty($diff)) {
    //                     $existing->update($diff);
    //                     $updated++;
    //                 } else {
    //                     $skipped++;
    //                 }
    //             } else {
    //                 Item::create($fields);
    //                 $inserted++;
    //             }
    //         }

    //         if ($inserted === 0 && $updated === 0) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'All data already exists. No new or updated rows.',
    //             ], 200);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Import completed.',
    //             'inserted' => $inserted,
    //             'updated'  => $updated,
    //             'skipped'  => $skipped,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Import failed.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
}
