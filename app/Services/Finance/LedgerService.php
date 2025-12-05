<?php

namespace App\Services\Finance;

use App\Models\CreditSale;
use App\Models\FinancialLedger;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LedgerService
{
    public function createCustomerReceivable(
        User $user,
        string $firstName,
        string $lastName,
        string $phoneNumber,
        float $amount,
        ?string $notes = null
    ): FinancialLedger {
        return DB::transaction(function () use ($user, $firstName, $lastName, $phoneNumber, $amount, $notes) {
            $openedAt = Carbon::now();
            $currency = config('finance.currency_code');
            $reminderOffset = $this->reminderCadenceDays();

            $sale = CreditSale::create([
                'sale_date' => $openedAt->toDateString(),
                'currency' => $currency,
                'total_amount' => $amount,
                'customer_first_name' => $firstName,
                'customer_last_name' => $lastName,
                'customer_phone' => $phoneNumber,
                'customer_email' => null,
                'notes' => $notes,
                'recorded_by' => $user->getAuthIdentifier(),
            ]);

            $ledger = new FinancialLedger([
                'ledger_type' => FinancialLedger::TYPE_RECEIVABLE,
                'status' => FinancialLedger::STATUS_OPEN,
                'credit_sale_id' => $sale->id,
                'vendor_name' => trim($firstName . ' ' . $lastName),
                'contact_first_name' => $firstName,
                'contact_last_name' => $lastName,
                'contact_phone' => $phoneNumber,
                'principal_amount' => $amount,
                'outstanding_amount' => $amount,
                'paid_amount' => 0,
                'opened_at' => $openedAt,
                'next_reminder_due_at' => $openedAt->copy()->addDays($reminderOffset),
                'notes' => $notes,
            ]);

            $ledger->source()->associate($sale);
            $ledger->creditSale()->associate($sale);
            $ledger->save();

            Log::info('finance.ledger.customer_receivable_created', [
                'ledger_id' => $ledger->id,
                'credit_sale_id' => $sale->id,
                'principal_amount' => $amount,
                'recorded_by' => $user->getAuthIdentifier(),
            ]);

            return $ledger;
        });
    }

    public function createVendorDebt(
        User $user,
        PurchaseOrder $purchaseOrder,
        ?Vendor $vendor,
        string $displayVendorName,
        float $principalAmount,
        ?string $notes = null,
        string $itemSummary = ''
    ): FinancialLedger {
        return DB::transaction(function () use ($user, $purchaseOrder, $vendor, $displayVendorName, $principalAmount, $notes, $itemSummary) {
            $openedAt = Carbon::now();
            $reminderOffset = $this->reminderCadenceDays();

            $ledger = new FinancialLedger([
                'ledger_type' => FinancialLedger::TYPE_LIABILITY,
                'status' => FinancialLedger::STATUS_OPEN,
                'purchase_order_id' => $purchaseOrder->id,
                'vendor_id' => $vendor?->id,
                'vendor_name' => $displayVendorName,
                'vendor_phone' => $vendor?->phone,
                'contact_phone' => $vendor?->phone,
                'contact_email' => $vendor?->email,
                'principal_amount' => $principalAmount,
                'outstanding_amount' => $principalAmount,
                'paid_amount' => 0,
                'opened_at' => $openedAt,
                'next_reminder_due_at' => $openedAt->copy()->addDays($reminderOffset),
                'notes' => $notes,
            ]);

            if ($vendor) {
                $ledger->vendor()->associate($vendor);
            }

            $ledger->source()->associate($purchaseOrder);
            $ledger->save();

            Log::info('finance.ledger.vendor_debt_created', [
                'ledger_id' => $ledger->id,
                'purchase_order_id' => $purchaseOrder->id,
                'vendor_id' => $vendor?->id,
                'principal_amount' => $principalAmount,
                'item_summary' => $itemSummary,
                'recorded_by' => $user->getAuthIdentifier(),
            ]);

            return $ledger;
        });
    }

    private function reminderCadenceDays(): int
    {
        $days = (int) config('finance.reminder_cadence_days', 7);

        return $days > 0 ? $days : 7;
    }
}
