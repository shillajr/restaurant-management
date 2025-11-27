<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support dropping foreign keys, so we need to recreate the table
        // First, get all existing data
        $purchaseOrders = DB::table('purchase_orders')->get();
        
        // Drop the table
        Schema::dropIfExists('purchase_orders');
        
        // Recreate with correct foreign key
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number');
            $table->foreignId('requisition_id')->constrained('chef_requisitions')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('approved_at')->nullable();
            $table->date('requested_delivery_date')->nullable();
            $table->json('items');
            $table->decimal('total_quantity', 10, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('other_charges', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Workflow status
            $table->string('workflow_status')->default('pending');
            
            // Legacy fields
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained('vendors')->onDelete('set null');
            $table->string('invoice_number')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->string('receipt_path')->nullable();
            
            // Status enum
            $table->enum('status', ['open', 'assigned', 'ordered', 'partially_received', 'received', 'closed', 'cancelled'])->default('open');
        });
        
        // Restore data if any existed
        foreach ($purchaseOrders as $po) {
            DB::table('purchase_orders')->insert((array) $po);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - this fixes a broken migration
    }
};
