<?php

namespace App\Notifications;

use App\Models\FinancialLedger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class VendorPaymentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly FinancialLedger $ledger)
    {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ledger = $this->ledger->fresh();
        $dueDate = $ledger?->next_reminder_due_at instanceof Carbon
            ? $ledger->next_reminder_due_at->format('M d, Y')
            : 'soon';

        return (new MailMessage)
            ->subject('Payment Reminder: ' . ($ledger->ledger_code ?? 'Vendor Balance'))
            ->greeting('Hello,')
            ->line('This is a friendly reminder that there is an outstanding balance recorded for ' . ($ledger->vendor_name ?? 'one of your vendors') . '.')
            ->line('Ledger reference: ' . ($ledger->ledger_code ?? 'N/A'))
            ->line('Outstanding amount: ' . currency_format($ledger->outstanding_amount ?? 0))
            ->line('Next check-in date: ' . $dueDate)
            ->line('Notes: ' . ($ledger->notes ?? 'No additional notes provided.'))
            ->action('View Ledger', route('financial-ledgers.index'))
            ->line('If this balance has already been settled, please ignore this reminder.');
    }
}
