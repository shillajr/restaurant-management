<?php

namespace App\Notifications;

use App\Models\PayrollPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class PaymentMadeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $payment;

    /**
     * Create a new notification instance.
     */
    public function __construct(PayrollPayment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $payroll = $this->payment->payroll;
        $monthName = Carbon::parse($payroll->month)->format('F Y');
        $amountPaid = 'KES ' . number_format($this->payment->amount, 2);
        $paymentDate = Carbon::parse($this->payment->payment_date)->format('d M Y');
        $paymentMethod = $this->payment->payment_method ?? __('mail.payment_made.method_na');
        $paymentReference = $this->payment->payment_reference ?? __('mail.payment_made.reference_na');
        $totalDue = 'KES ' . number_format($payroll->total_due, 2);
        $totalPaid = 'KES ' . number_format($payroll->total_paid, 2);
        $outstandingBalance = 'KES ' . number_format($payroll->outstanding_balance, 2);

        return (new MailMessage)
            ->subject(__('mail.payment_made.subject', ['month' => $monthName]))
            ->greeting(__('mail.payment_made.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.payment_made.intro', ['month' => $monthName]))
            ->line(__('mail.payment_made.details_heading'))
            ->line(__('mail.payment_made.amount', ['amount' => $amountPaid]))
            ->line(__('mail.payment_made.date', ['date' => $paymentDate]))
            ->line(__('mail.payment_made.method', ['method' => $paymentMethod]))
            ->line(__('mail.payment_made.reference', ['reference' => $paymentReference]))
            ->line('')
            ->line(__('mail.payment_made.summary_heading'))
            ->line(__('mail.payment_made.total_due', ['amount' => $totalDue]))
            ->line(__('mail.payment_made.total_paid', ['amount' => $totalPaid]))
            ->line(__('mail.payment_made.outstanding_balance', ['amount' => $outstandingBalance]))
            ->line('')
            ->line(__('mail.payment_made.thanks'))
            ->salutation(__('mail.payment_made.salutation', ['app' => config('app.name')]));
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $payroll = $this->payment->payroll;
        
        return [
            'payment_id' => $this->payment->id,
            'payroll_id' => $payroll->id,
            'amount' => $this->payment->amount,
            'payment_date' => $this->payment->payment_date,
            'month' => Carbon::parse($payroll->month)->format('F Y'),
            'outstanding_balance' => $payroll->outstanding_balance,
        ];
    }
}