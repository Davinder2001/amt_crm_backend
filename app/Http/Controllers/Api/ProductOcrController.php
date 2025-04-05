<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Storage;

class ProductOcrController extends Controller
{
    /**
     * Extracts text from uploaded invoice (image or PDF) and parses it.
     */
    public function extractTextFromImage(Request $request)
    {
        $request->validate([
            'invoice' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
        ]);

        $file = $request->file('invoice');
        $extension = strtolower($file->getClientOriginalExtension());

        $filename = 'invoice_' . time() . '.' . $extension;
        $path = $file->storeAs('invoices', $filename);

        $fullPath = storage_path('app/' . $path);
        $text = '';

        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            $text = (new TesseractOCR($fullPath))->run();
        } elseif ($extension === 'pdf') {
            try {
                $parser = new Parser();
                $pdf = $parser->parseFile($fullPath);
                $text = $pdf->getText();
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to parse PDF file.',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        $items = $this->parseInvoice($text);

        return response()->json([
            'status' => 'success',
            'items' => $items,
            'raw_text' => $text,
        ]);
    }

    /**
     * Extract item details from invoice text using regex.
     */
    private function parseInvoice(string $text): array
    {
        $items = [];
        $lines = preg_split('/\r\n|\r|\n/', $text);

        foreach ($lines as $line) {
            if (preg_match('/(.+?)\s{1,}(\d+)\s{1,}([\d\.]+)/', $line, $matches)) {
                $items[] = [
                    'name' => trim($matches[1]),
                    'quantity' => (int)$matches[2],
                    'price' => (float)$matches[3],
                ];
            }
        }

        return $items;
    }
}
