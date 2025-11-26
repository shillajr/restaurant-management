<?php

namespace App\Services;

use App\Models\LoyverseSale;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoyverseService
{
    /**
     * Loyverse API base URL
     *
     * @var string
     */
    protected $baseUrl = 'https://api.loyverse.com/v1/';

    /**
     * API Key for authentication
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiKey = config('services.loyverse.api_key');
    }

    /**
     * Sync daily sales from Loyverse API
     *
     * @param string $date Date in Y-m-d format
     * @return bool
     */
    public function syncDailySales($date)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(30)->get($this->baseUrl . 'receipts', [
                'created_at_min' => $date . 'T00:00:00Z',
                'created_at_max' => $date . 'T23:59:59Z',
                'limit' => 100
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['receipts']) && is_array($data['receipts'])) {
                    $this->processSalesData($data['receipts'], $date);
                    
                    activity()
                        ->withProperties([
                            'date' => $date,
                            'receipts_count' => count($data['receipts'])
                        ])
                        ->log('Loyverse daily sales synced');
                    
                    return true;
                }
                
                Log::warning('Loyverse API returned no receipts for date: ' . $date);
                return true;
            }
            
            Log::error('Loyverse API Error: ' . $response->status() . ' - ' . $response->body());
            return false;
            
        } catch (\Exception $e) {
            Log::error('Loyverse Sync Error: ' . $e->getMessage(), [
                'date' => $date,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Process webhook data from Loyverse
     *
     * @param array $payload
     * @return bool
     */
    public function processWebhook(array $payload)
    {
        try {
            DB::beginTransaction();

            if (!isset($payload['event']) || !isset($payload['data'])) {
                Log::error('Invalid Loyverse webhook payload', ['payload' => $payload]);
                return false;
            }

            $event = $payload['event'];
            $data = $payload['data'];

            switch ($event) {
                case 'receipt.created':
                case 'receipt.updated':
                    $this->processReceiptWebhook($data);
                    break;
                
                case 'receipt.deleted':
                    $this->deleteReceipt($data['id']);
                    break;
                
                default:
                    Log::info('Unhandled Loyverse webhook event: ' . $event, ['data' => $data]);
            }

            DB::commit();

            activity()
                ->withProperties([
                    'event' => $event,
                    'receipt_id' => $data['id'] ?? null
                ])
                ->log('Loyverse webhook processed');

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Loyverse Webhook Error: ' . $e->getMessage(), [
                'payload' => $payload,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Import sales data from CSV file
     *
     * @param string $filePath
     * @return array
     */
    public function importFromCSV($filePath)
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception('CSV file not found: ' . $filePath);
            }

            $imported = 0;
            $errors = [];
            $handle = fopen($filePath, 'r');
            
            // Skip header row
            $header = fgetcsv($handle);
            
            DB::beginTransaction();

            while (($row = fgetcsv($handle)) !== false) {
                try {
                    // Assuming CSV format: receipt_id, date, total_sales, tax, items_json
                    if (count($row) < 5) {
                        continue;
                    }

                    $items = json_decode($row[4], true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $errors[] = "Invalid JSON in row for receipt: {$row[0]}";
                        continue;
                    }

                    LoyverseSale::updateOrCreate(
                        ['external_id' => $row[0]],
                        [
                            'date' => Carbon::parse($row[1])->format('Y-m-d'),
                            'total_sales' => (float) $row[2],
                            'tax' => (float) $row[3],
                            'items' => $items,
                            'synced_at' => now()
                        ]
                    );

                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Error processing row: " . $e->getMessage();
                    Log::error('CSV Import Row Error: ' . $e->getMessage(), ['row' => $row]);
                }
            }

            fclose($handle);
            DB::commit();

            activity()
                ->withProperties([
                    'file' => basename($filePath),
                    'imported' => $imported,
                    'errors' => count($errors)
                ])
                ->log('Loyverse CSV import completed');

            return [
                'success' => true,
                'imported' => $imported,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Loyverse CSV Import Error: ' . $e->getMessage(), [
                'file' => $filePath,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'imported' => 0,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Process sales data from API response
     *
     * @param array $receipts
     * @param string $date
     * @return void
     */
    protected function processSalesData($receipts, $date)
    {
        foreach ($receipts as $receipt) {
            try {
                LoyverseSale::updateOrCreate(
                    ['external_id' => $receipt['id']],
                    [
                        'date' => $date,
                        'total_sales' => $receipt['total_money'] ?? 0,
                        'tax' => $receipt['total_tax'] ?? 0,
                        'discount' => $receipt['total_discount'] ?? 0,
                        'items' => $receipt['line_items'] ?? [],
                        'payment_type' => $receipt['payments'][0]['payment_type_name'] ?? null,
                        'customer_name' => $receipt['customer_name'] ?? null,
                        'receipt_number' => $receipt['receipt_number'] ?? null,
                        'created_at_external' => isset($receipt['created_at']) ? Carbon::parse($receipt['created_at']) : null,
                        'synced_at' => now()
                    ]
                );
            } catch (\Exception $e) {
                Log::error('Error processing receipt: ' . $e->getMessage(), [
                    'receipt_id' => $receipt['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Process single receipt from webhook
     *
     * @param array $receipt
     * @return void
     */
    protected function processReceiptWebhook($receipt)
    {
        $date = isset($receipt['created_at']) 
            ? Carbon::parse($receipt['created_at'])->format('Y-m-d')
            : now()->format('Y-m-d');

        LoyverseSale::updateOrCreate(
            ['external_id' => $receipt['id']],
            [
                'date' => $date,
                'total_sales' => $receipt['total_money'] ?? 0,
                'tax' => $receipt['total_tax'] ?? 0,
                'discount' => $receipt['total_discount'] ?? 0,
                'items' => $receipt['line_items'] ?? [],
                'payment_type' => $receipt['payments'][0]['payment_type_name'] ?? null,
                'customer_name' => $receipt['customer_name'] ?? null,
                'receipt_number' => $receipt['receipt_number'] ?? null,
                'created_at_external' => isset($receipt['created_at']) ? Carbon::parse($receipt['created_at']) : null,
                'synced_at' => now()
            ]
        );
    }

    /**
     * Delete receipt from webhook
     *
     * @param string $externalId
     * @return void
     */
    protected function deleteReceipt($externalId)
    {
        LoyverseSale::where('external_id', $externalId)->delete();
        
        Log::info('Loyverse receipt deleted via webhook', ['external_id' => $externalId]);
    }

    /**
     * Verify API connection
     *
     * @return bool
     */
    public function verifyConnection()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(10)->get($this->baseUrl . 'stores');

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Loyverse Connection Error: ' . $e->getMessage());
            return false;
        }
    }
}
