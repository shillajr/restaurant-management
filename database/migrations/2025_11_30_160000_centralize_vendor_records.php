<?php

use App\Models\Vendor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $backupPath = 'migrations/vendor_centralization.json';

        $vendorSnapshot = DB::table('vendors')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $purchaseOrderSnapshot = DB::table('purchase_orders')
            ->select('id', 'supplier_id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $expenseSnapshot = [];
        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'vendor_id')) {
            $expenseSnapshot = DB::table('expenses')
                ->select('id', 'vendor_id')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        Storage::disk('local')->makeDirectory('migrations');
        Storage::disk('local')->put($backupPath, json_encode([
            'vendors' => $vendorSnapshot,
            'purchase_orders' => $purchaseOrderSnapshot,
            'expenses' => $expenseSnapshot,
        ], JSON_PRETTY_PRINT));

        DB::transaction(function () use ($expenseSnapshot): void {
            /** @var \App\Models\Vendor|null $canonical */
            $canonical = Vendor::query()->firstWhere('name', 'Standard Vendor');

            if ($canonical) {
                $canonical->update([
                    'email' => null,
                    'phone' => '+255757022929',
                    'is_active' => true,
                ]);
            } else {
                $canonical = Vendor::create([
                    'name' => 'Standard Vendor',
                    'email' => null,
                    'phone' => '+255757022929',
                    'is_active' => true,
                ]);
            }

            DB::table('purchase_orders')
                ->whereNotNull('supplier_id')
                ->update(['supplier_id' => $canonical->id]);

            if (!empty($expenseSnapshot)) {
                DB::table('expenses')
                    ->whereNotNull('vendor_id')
                    ->update(['vendor_id' => $canonical->id]);
            }

            Vendor::query()
                ->whereKeyNot($canonical->id)
                ->delete();
        });
    }

    public function down(): void
    {
        $backupPath = 'migrations/vendor_centralization.json';

        if (!Storage::disk('local')->exists($backupPath)) {
            return;
        }

        $payload = json_decode(Storage::disk('local')->get($backupPath), true);

        if (!is_array($payload)) {
            return;
        }

        DB::transaction(function () use ($payload): void {
            $vendorRows = collect($payload['vendors'] ?? [])
                ->map(fn ($row) => array_change_key_case($row, CASE_LOWER));

            $snapshotIncludesStandardVendor = $vendorRows
                ->contains(fn (array $row) => ($row['name'] ?? null) === 'Standard Vendor');

                $normalizedVendorRows = $vendorRows
                    ->map(function (array $row) {
                        return [
                            'id' => $row['id'] ?? null,
                            'name' => $row['name'] ?? null,
                            'email' => $row['email'] ?? null,
                            'phone' => $row['phone'] ?? null,
                            'is_active' => $row['is_active'] ?? true,
                            'created_at' => $row['created_at'] ?? now(),
                            'updated_at' => $row['updated_at'] ?? now(),
                        ];
                    })
                    ->filter(fn (array $row) => ! is_null($row['id']))
                    ->values();

                if ($normalizedVendorRows->isNotEmpty()) {
                    DB::table('vendors')->upsert(
                        $normalizedVendorRows->all(),
                        ['id'],
                        ['name', 'email', 'phone', 'is_active', 'created_at', 'updated_at']
                    );
            }

            $purchaseOrderRows = collect($payload['purchase_orders'] ?? [])
                ->map(fn ($row) => array_change_key_case($row, CASE_LOWER));

            foreach ($purchaseOrderRows as $purchaseOrderRow) {
                DB::table('purchase_orders')
                    ->where('id', $purchaseOrderRow['id'])
                    ->update(['supplier_id' => $purchaseOrderRow['supplier_id']]);
            }

            $expenseRows = collect($payload['expenses'] ?? [])
                ->map(fn ($row) => array_change_key_case($row, CASE_LOWER));

            foreach ($expenseRows as $expenseRow) {
                DB::table('expenses')
                    ->where('id', $expenseRow['id'])
                    ->update(['vendor_id' => $expenseRow['vendor_id']]);
            }

            if ($vendorRows->isNotEmpty()) {
                $snapshotIds = $vendorRows->pluck('id')->filter()->all();
                DB::table('vendors')
                    ->whereNotIn('id', $snapshotIds)
                    ->delete();
            }

            if (! $snapshotIncludesStandardVendor) {
                DB::table('vendors')
                    ->where('name', 'Standard Vendor')
                    ->delete();
            }
        });

        Storage::disk('local')->delete($backupPath);
    }
};
