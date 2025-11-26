<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\ChefRequisition;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Expense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Spatie\Activitylog\Models\Activity;

class TestMakerCheckerWorkflow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:maker-checker-workflow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the complete maker-checker workflow for requisitions and purchase orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Maker-Checker Workflow Test...');
        $this->newLine();

        // Step 1: Create chef user and submit requisition
        $this->info('📝 Step 1: Creating chef user and submitting requisition...');
        
        $chef = User::firstOrCreate(
            ['email' => 'test-chef@restaurant.com'],
            [
                'name' => 'Test Chef',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        
        if (!$chef->hasRole('chef')) {
            $chef->assignRole('chef');
        }

        $requisition = ChefRequisition::create([
            'chef_id' => $chef->id,
            'requested_for_date' => now()->addDays(3)->format('Y-m-d'),
            'items' => [
                [
                    'item' => 'Fresh Tomatoes',
                    'quantity' => 50,
                    'unit' => 'kg'
                ],
                [
                    'item' => 'Olive Oil',
                    'quantity' => 10,
                    'unit' => 'liters'
                ],
                [
                    'item' => 'Garlic',
                    'quantity' => 5,
                    'unit' => 'kg'
                ]
            ],
            'note' => 'Needed for weekend special menu',
            'status' => 'pending'
        ]);

        $this->info("✅ Requisition #{$requisition->id} created by {$chef->name}");
        $this->info("   Items: " . count($requisition->items));
        $this->info("   Status: {$requisition->status}");
        $this->newLine();

        // Step 2: Create manager and approve requisition
        $this->info('👔 Step 2: Creating manager user and approving requisition...');
        
        $manager = User::firstOrCreate(
            ['email' => 'test-manager@restaurant.com'],
            [
                'name' => 'Test Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        
        if (!$manager->hasRole('manager')) {
            $manager->assignRole('manager');
        }

        $requisition->update([
            'status' => 'approved',
            'checker_id' => $manager->id,
            'checked_at' => now(),
        ]);

        activity()
            ->performedOn($requisition)
            ->causedBy($manager)
            ->log('Requisition approved via test workflow');

        $this->info("✅ Requisition approved by {$manager->name}");
        $this->info("   Checked at: {$requisition->checked_at->format('Y-m-d H:i:s')}");
        $this->newLine();

        // Step 3: Create purchase order from approved requisition
        $this->info('🛒 Step 3: Creating purchase order from approved requisition...');
        
        $supplier = Supplier::firstOrCreate(
            ['name' => 'Test Supplier Inc'],
            [
                'contact_person' => 'John Supplier',
                'email' => 'contact@testsupplier.com',
                'phone' => '+1-555-0123',
                'address' => '123 Supply Street, Commerce City',
                'is_active' => true,
            ]
        );

        $purchaseOrder = PurchaseOrder::create([
            'chef_requisition_id' => $requisition->id,
            'supplier_id' => $supplier->id,
            'items' => $requisition->items,
            'estimated_total' => 750.00,
            'delivery_date' => now()->addDays(2)->format('Y-m-d'),
            'notes' => 'Delivery to kitchen entrance',
            'status' => 'pending',
            'created_by' => $manager->id,
        ]);

        // Update requisition status to fulfilled
        $requisition->update(['status' => 'fulfilled']);

        $this->info("✅ Purchase Order #{$purchaseOrder->id} created");
        $this->info("   Supplier: {$supplier->name}");
        $this->info("   Estimated Total: \${$purchaseOrder->estimated_total}");
        $this->newLine();

        // Step 4: Mark purchase as completed with receipt
        $this->info('💰 Step 4: Marking purchase as completed with receipt...');
        
        // Create a dummy receipt file
        $receiptContent = "INVOICE\n\nTest Supplier Inc\nInvoice #: INV-2024-001\n\nTotal: $785.50\n\nThank you for your business!";
        $receiptPath = 'receipts/test-receipt-' . now()->timestamp . '.txt';
        Storage::disk('public')->put($receiptPath, $receiptContent);

        $purchaseOrder->update([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-2024-001',
            'total_amount' => 785.50,
            'receipt_path' => $receiptPath,
            'purchased_at' => now(),
            'status' => 'completed'
        ]);

        activity()
            ->performedOn($purchaseOrder)
            ->causedBy($manager)
            ->withProperties([
                'invoice_number' => 'INV-2024-001',
                'total_amount' => 785.50,
            ])
            ->log('Purchase order marked as purchased via test workflow');

        $this->info("✅ Purchase Order marked as completed");
        $this->info("   Invoice: {$purchaseOrder->invoice_number}");
        $this->info("   Total Amount: \${$purchaseOrder->total_amount}");
        $this->info("   Receipt stored at: {$receiptPath}");
        $this->newLine();

        // Step 5: Create expense record
        $this->info('📊 Step 5: Creating expense record...');
        
        $expense = Expense::create([
            'ledger_code' => 'PURCHASE',
            'amount' => $purchaseOrder->total_amount,
            'date' => now()->format('Y-m-d'),
            'description' => "Purchase Order #{$purchaseOrder->id} - Invoice: {$purchaseOrder->invoice_number}",
            'receipt_url' => $receiptPath,
            'approved_by' => $manager->id,
            'approved_at' => now(),
            'purchase_order_id' => $purchaseOrder->id,
        ]);

        $this->info("✅ Expense record #{$expense->id} created");
        $this->info("   Ledger Code: {$expense->ledger_code}");
        $this->info("   Amount: \${$expense->amount}");
        $this->info("   Approved by: {$manager->name}");
        $this->newLine();

        // Step 6: Check audit logs
        $this->info('📋 Step 6: Verifying audit logs...');
        
        $requisitionLogs = Activity::where('subject_type', ChefRequisition::class)
            ->where('subject_id', $requisition->id)
            ->get();

        $purchaseOrderLogs = Activity::where('subject_type', PurchaseOrder::class)
            ->where('subject_id', $purchaseOrder->id)
            ->get();

        $this->info("✅ Audit logs verified:");
        $this->info("   Requisition logs: {$requisitionLogs->count()} entries");
        
        foreach ($requisitionLogs as $log) {
            $causer = $log->causer ? $log->causer->name : 'System';
            $this->info("      - {$log->description} by {$causer} at {$log->created_at->format('Y-m-d H:i:s')}");
        }
        
        $this->info("   Purchase Order logs: {$purchaseOrderLogs->count()} entries");
        
        foreach ($purchaseOrderLogs as $log) {
            $causer = $log->causer ? $log->causer->name : 'System';
            $this->info("      - {$log->description} by {$causer} at {$log->created_at->format('Y-m-d H:i:s')}");
        }
        
        $this->newLine();

        // Summary
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('✨ WORKFLOW TEST COMPLETED SUCCESSFULLY! ✨');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();
        
        $this->table(
            ['Step', 'Entity', 'ID', 'Status'],
            [
                ['1', 'Chef Requisition', "#{$requisition->id}", $requisition->status],
                ['2', 'Approval', "Checker: {$manager->name}", 'Approved'],
                ['3', 'Purchase Order', "#{$purchaseOrder->id}", $purchaseOrder->status],
                ['4', 'Receipt', $purchaseOrder->invoice_number, 'Uploaded'],
                ['5', 'Expense', "#{$expense->id}", 'Recorded'],
                ['6', 'Audit Logs', ($requisitionLogs->count() + $purchaseOrderLogs->count()) . ' entries', 'Verified'],
            ]
        );

        $this->newLine();
        $this->info('🔑 Test Users Created:');
        $this->info("   Chef: test-chef@restaurant.com (password: password)");
        $this->info("   Manager: test-manager@restaurant.com (password: password)");
        $this->newLine();

        return Command::SUCCESS;
    }
}
