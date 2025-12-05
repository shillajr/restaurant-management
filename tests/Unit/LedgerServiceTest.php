<?php

namespace Tests\Unit;

use App\Models\FinancialLedger;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Finance\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledgerService;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(now()->startOfMinute());
        $this->ledgerService = app(LedgerService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function it_creates_customer_receivable_with_configured_defaults(): void
    {
        Config::set('finance.currency_code', 'EUR');
        Config::set('finance.reminder_cadence_days', 10);

        $user = User::factory()->create();

        $ledger = $this->ledgerService->createCustomerReceivable(
            $user,
            'Alice',
            'Customer',
            '+255700000000',
            125.75,
            'Team event catering'
        );

        $this->assertDatabaseHas('credit_sales', [
            'id' => $ledger->credit_sale_id,
            'currency' => 'EUR',
            'total_amount' => 125.75,
        ]);

        $this->assertSame(FinancialLedger::TYPE_RECEIVABLE, $ledger->ledger_type);
        $this->assertSame('EUR', $ledger->currency);
        $this->assertEquals(125.75, (float) $ledger->principal_amount);
        $this->assertEquals(125.75, (float) $ledger->outstanding_amount);
        $this->assertEquals('Alice Customer', $ledger->vendor_name);
        $this->assertTrue($ledger->next_reminder_due_at->equalTo($ledger->opened_at->copy()->addDays(10)));
    }

    #[Test]
    public function it_creates_vendor_debt_with_vendor_association(): void
    {
        Config::set('finance.reminder_cadence_days', 5);

        $user = User::factory()->create();
        $vendor = Vendor::factory()->create([
            'name' => 'Prime Suppliers',
            'email' => 'accounts@prime.test',
            'phone' => '+255711111111',
        ]);

        $purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $vendor->id,
            'items' => [[
                'item' => 'Cooking Oil',
                'quantity' => 3,
                'price' => 45000,
                'vendor' => $vendor->name,
            ]],
            'grand_total' => 135000,
        ]);

        $ledger = $this->ledgerService->createVendorDebt(
            $user,
            $purchaseOrder,
            $vendor,
            $vendor->name,
            135000,
            'Stock on credit',
            'Cooking Oil — Qty 3 (Total 135000)'
        );

        $this->assertSame(FinancialLedger::TYPE_LIABILITY, $ledger->ledger_type);
        $this->assertSame($vendor->id, $ledger->vendor_id);
        $this->assertSame('Prime Suppliers', $ledger->vendor_name);
        $this->assertEquals(135000.0, (float) $ledger->principal_amount);
        $this->assertTrue($ledger->next_reminder_due_at->equalTo($ledger->opened_at->copy()->addDays(5)));
    }
}
