<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\Models\Item;
use App\Services\SelectedCompanyService;


class ProductOcrController extends Controller
{
    public function scanAndSaveText(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png,bmp|max:5120',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        File::ensureDirectoryExists(public_path('ocr_uploads'));
    
        $image = $request->file('image');
        $imageName = uniqid('ocr_', true) . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('ocr_uploads'), $imageName);
    
        $fullPath = public_path('ocr_uploads/' . $imageName);
        $rawText = (new TesseractOCR($fullPath))->run();
    
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
                'message' => 'No valid product data found in the image.',
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
    
    
}
