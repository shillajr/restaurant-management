<?php

use App\Models\Vendor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('expenses', function (Blueprint $table) use ($isSqlite) {
            if (! Schema::hasColumn('expenses', 'item_name')) {
                $table->string('item_name')->nullable();
            }

            if (! Schema::hasColumn('expenses', 'quantity')) {
                $table->decimal('quantity', 12, 2)->default(0);
            }

            if (! Schema::hasColumn('expenses', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->default(0);
            }

            if (! Schema::hasColumn('expenses', 'vendor_id')) {
                if ($isSqlite) {
                    $table->unsignedBigInteger('vendor_id')->nullable();
                } else {
                    $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
                }
            }
        });

        DB::table('expenses')->orderBy('id')->chunk(200, function ($expenses): void {
            foreach ($expenses as $expense) {
                $items = $expense->items;

                if (is_string($items)) {
                    $decoded = json_decode($items, true);
                } else {
                    $decoded = $items;
                }

                $decoded = is_array($decoded) ? $decoded : [];
                $firstItem = $decoded[0] ?? null;

                $itemName = $firstItem['name']
                    ?? $firstItem['item']
                    ?? $expense->description
                    ?? 'Expense Item';

                $quantity = isset($firstItem['quantity']) ? (float) $firstItem['quantity'] : 1.0;
                $quantity = $quantity > 0 ? $quantity : 1.0;

                $unitPrice = isset($firstItem['unit_price']) ? (float) $firstItem['unit_price'] : null;

                if ($unitPrice === null) {
                    $unitPrice = $quantity > 0 ? ((float) $expense->amount) / $quantity : (float) $expense->amount;
                }

                $vendorName = $expense->vendor;

                if (! $vendorName && isset($firstItem['vendor'])) {
                    $vendorName = $firstItem['vendor'];
                }

                $vendorId = null;

                if ($vendorName) {
                    $vendorId = Vendor::query()
                        ->where('name', $vendorName)
                        ->value('id');
                }

                $amount = round($quantity * $unitPrice, 2);

                DB::table('expenses')
                    ->where('id', $expense->id)
                    ->update([
                        'item_name' => $itemName,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'amount' => $amount,
                        'vendor_id' => $vendorId,
                        'vendor' => $vendorName,
                    ]);
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'items')) {
                $table->dropColumn('items');
            }
        });
    }

    public function down(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('expenses', function (Blueprint $table) use ($isSqlite) {
            if (! Schema::hasColumn('expenses', 'items')) {
                $table->json('items')->nullable();
            }
        });

        DB::table('expenses')->orderBy('id')->chunk(200, function ($expenses): void {
            foreach ($expenses as $expense) {
                $itemPayload = [[
                    'item_id' => null,
                    'name' => $expense->item_name ?? 'Expense Item',
                    'quantity' => (float) ($expense->quantity ?? 0),
                    'unit_price' => (float) ($expense->unit_price ?? 0),
                    'line_total' => (float) ($expense->quantity ?? 0) * (float) ($expense->unit_price ?? 0),
                    'vendor' => $expense->vendor,
                ]];

                DB::table('expenses')
                    ->where('id', $expense->id)
                    ->update([
                        'items' => json_encode($itemPayload),
                    ]);
            }
        });

        Schema::table('expenses', function (Blueprint $table) use ($isSqlite) {
            if (Schema::hasColumn('expenses', 'item_name')) {
                $table->dropColumn('item_name');
            }

            if (Schema::hasColumn('expenses', 'quantity')) {
                $table->dropColumn('quantity');
            }

            if (Schema::hasColumn('expenses', 'unit_price')) {
                $table->dropColumn('unit_price');
            }

            if (Schema::hasColumn('expenses', 'vendor_id')) {
                if (! $isSqlite) {
                    $table->dropForeign(['vendor_id']);
                }
                $table->dropColumn('vendor_id');
            }
        });
    }
};
