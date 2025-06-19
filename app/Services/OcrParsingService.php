<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrParsingService
{
    public function extractText(string $filePath, string $extension): string
    {
        if ($extension === 'pdf') {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        }

        return (new TesseractOCR($filePath))->run();
    }

    public function parseWithGpt(string $rawText): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer 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',
                'HTTP-Referer'  => 'https://yourdomain.com',
                'Content-Type'  => 'application/json',
            ])->timeout(20)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'mistralai/mistral-7b-instruct:free',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant. Ignore headers like "Name Qty Price". Convert the OCR text below into a JSON array with keys: name, quantity, price.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Convert this OCR output to JSON:\n\n{$rawText}",
                    ],
                ],
            ]);

            if (!$response->successful()) {
                return null;
            }

            $content = $response->json('choices.0.message.content');

            if (preg_match('/```json(.*?)```/s', $content, $matches)) {
                $json = json_decode(trim($matches[1]), true);
            } else {
                $json = json_decode($content, true);
            }

            return is_array($json) ? $json : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

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
                    'name'      => $name,
                    'quantity'  => (int)$quantity,
                    'price'     => (float)$price,
                ];
            }
        }

        return $items;
    }
}
