<?php

namespace App\Console\Commands;

use App\Models\FinancialLedger;
use App\Notifications\VendorPaymentReminder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendVendorPaymentReminders extends Command
{
    protected $signature = 'ledgers:send-vendor-reminders {--dry-run : Gather stats without sending notifications}';

    protected $description = 'Send payment reminders for vendor liabilities that are due.';

    public function handle(): int
    {
        $now = Carbon::now();
        $dryRun = (bool) $this->option('dry-run');

        $ledgers = FinancialLedger::query()
            ->dueForReminder()
            ->with(['vendor'])
            ->get();

        if ($ledgers->isEmpty()) {
            $this->info('No ledgers require reminders at this time.');
            return self::SUCCESS;
        }

        $recipientsByLedger = [];

        $managerEmails = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['manager', 'finance', 'admin']))
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();

        foreach ($ledgers as $ledger) {
            $emails = collect([
                $ledger->contact_email,
                $ledger->vendor?->email,
            ])->filter()->unique()->values();

            if ($emails->isEmpty()) {
                $emails = $managerEmails;
            }

            $recipientsByLedger[$ledger->id] = $emails;
        }

        if ($dryRun) {
            $this->line('Dry run complete. ' . count($recipientsByLedger) . ' ledgers would receive reminders.');
            return self::SUCCESS;
        }

        foreach ($ledgers as $ledger) {
            $emails = $recipientsByLedger[$ledger->id];

            if ($emails->isEmpty()) {
                Log::warning('Vendor reminder skipped due to missing recipients.', [
                    'ledger_id' => $ledger->id,
                ]);
                $ledger->markReminderSent($now);
                continue;
            }

            foreach ($emails as $email) {
                Notification::route('mail', $email)->notify(new VendorPaymentReminder($ledger));
            }

            $ledger->markReminderSent($now);

            activity()
                ->performedOn($ledger)
                ->event('vendor_payment_reminder')
                ->withProperties([
                    'reminder_sent_at' => $now->toDateTimeString(),
                    'recipients' => $emails,
                ])
                ->log('Automated vendor payment reminder sent.');

            $this->info('Reminder dispatched for ledger ' . $ledger->ledger_code . ' to ' . $emails->implode(', '));
        }

        return self::SUCCESS;
    }
}
