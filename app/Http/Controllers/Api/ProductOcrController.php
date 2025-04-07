<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Smalot\PdfParser\Parser;
use App\Models\Item;
use App\Services\SelectedCompanyService;


class ProductOcrController extends Controller
{
 
    public function scanAndSaveText(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:jpg,jpeg,png,bmp,pdf|max:5120',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        File::ensureDirectoryExists(public_path('ocr_uploads'));
    
        $file = $request->file('image');
        $fileName = uniqid('ocr_', true) . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('ocr_uploads'), $fileName);
        $filePath = public_path('ocr_uploads/' . $fileName);
    
        $rawText = '';
    
        if ($file->getClientOriginalExtension() === 'pdf') {
            // Convert PDF to text using PDF Parser (or you could use Imagick to convert it to image and OCR it)
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $rawText = $pdf->getText();
            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to parse PDF file.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        } else {
            // Extract text using Tesseract for images
            $rawText = (new TesseractOCR($filePath))->run();
        }
    
        $lines = explode("\n", trim($rawText));
        $extractedItems = [];
        $grandTotal = 0;
    
        foreach ($lines as $index => $line) {
            if ($index === 0 || trim($line) === '') continue;
    
            $parts = preg_split('/\s{2,}|\t+|\s+/', trim($line));
    
            if (count($parts) >= 3) {
                [$name, $quantity, $price] = $parts;
    
                if (!is_numeric($quantity) || !is_numeric($price)) {
                    continue;
                }
    
                $subTotal = (int) $quantity * (float) $price;
                $grandTotal += $subTotal;
    
                $extractedItems[] = [
                    'name'       => $name,
                    'quantity'   => (int) $quantity,
                    'price'      => (float) $price,
                    'sub_total'  => $subTotal,
                ];
            }
        }
    
        if (empty($extractedItems)) {
            return response()->json([
                'status' => false,
                'message' => 'No valid product data found in the file.',
                'raw_text' => $rawText,
            ], 422);
        }
    
        return response()->json([
            'status'       => true,
            'message'      => 'Products extracted successfully.',
            'ocr_text'     => $rawText,
            'products'     => $extractedItems,
            'grand_total'  => $grandTotal,
        ]);
    }
    

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products'   => 'required|array|min:1',
            'products.*.name' => 'required|string',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $products = $request->input('products');
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $lastItemCode = Item::where('company_id', $selectedCompany->id)->max('item_code') ?? 0;

        $savedItems = [];

        foreach ($products as $product) {
            $lastItemCode++;

            $item = Item::create([
                'company_id'     => $selectedCompany->id,
                'item_code'      => $lastItemCode,
                'name'           => $product['name'],
                'quantity_count' => $product['quantity'],
                'price'          => $product['price'],
            ]);

            $savedItems[] = $item;
        }

        return response()->json([
            'status' => true,
            'message' => 'Products saved successfully.',
            'items' => $savedItems,
        ], 201);
    }
}
