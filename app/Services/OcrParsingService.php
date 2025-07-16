<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrParsingService
{
    /**
     * Extract text from a file using either PDF parser or Tesseract OCR
     */
    public function extractText(string $filePath, string $extension): string
    {
        if ($extension === 'pdf') {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        }

        return (new TesseractOCR($filePath))->run();
    }

    /**
     * Use GPT via OpenRouter to parse structured product data
     */
    public function parseWithGpt(string $rawText): ?array
    {
        try {
            $response = Http::withHeaders([
                // 'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
                'Authorization' => 'Bearer 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',
                'Referer' => env('OPENROUTER_REFERER', 'http://localhost'),
            ])->timeout(20)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'mistralai/mistral-7b-instruct:free',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant. Ignore headers like "Name Qty Price". Convert the OCR text below into a JSON array with keys: name, quantity, price. Only return a valid JSON array.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Convert this OCR output to JSON:\n\n{$rawText}",
                    ],
                ],
            ]);

            if (!$response->successful()) {
                logger()->error('OpenRouter GPT error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $content = $response->json('choices.0.message.content');

            // Attempt to extract valid JSON
            if (preg_match('/```json(.*?)```/s', $content, $matches)) {
                $json = json_decode(trim($matches[1]), true);
            } elseif (preg_match('/\[(.*?)\]/s', $content, $matches)) {
                $json = json_decode('[' . trim($matches[1]) . ']', true);
            } else {
                $json = json_decode($content, true);
            }

            // Validate format
            if (is_array($json) && isset($json[0]['name']) && isset($json[0]['quantity']) && isset($json[0]['price'])) {
                return $json;
            }

            logger()->warning('GPT returned invalid JSON', ['content' => $content]);
            return null;
        } catch (\Throwable $e) {
            logger()->error('GPT exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fallback: Parse manually by splitting lines
     */
    public function parseManually(string $rawText): array
    {
        $lines = explode("\n", trim($rawText));
        $items = [];

        foreach ($lines as $index => $line) {
            if ($index === 0 || trim($line) === '') continue;

            $parts = preg_split('/\s{2,}|\t+|\s+/', trim($line));

            if (count($parts) >= 3) {
                [$name, $quantity, $price] = $parts;

                if (!is_numeric($quantity) || !is_numeric($price)) continue;

                $items[] = [
                    'name' => $name,
                    'quantity' => (int)$quantity,
                    'price' => (float)$price,
                ];
            }
        }

        return $items;
    }
}
