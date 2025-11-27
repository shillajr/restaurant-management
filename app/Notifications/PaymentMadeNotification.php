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

        return (new MailMessage)
                    ->subject('Salary Payment Received - ' . $monthName)
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('A payment has been made to your salary for ' . $monthName . '.')
                    ->line('**Payment Details:**')
                    ->line('Amount Paid: KES ' . number_format($this->payment->amount, 2))
                    ->line('Payment Date: ' . Carbon::parse($this->payment->payment_date)->format('d M Y'))
                    ->line('Payment Method: ' . ($this->payment->payment_method ?? 'N/A'))
                    ->line('Payment Reference: ' . ($this->payment->payment_reference ?? 'N/A'))
                    ->line('')
                    ->line('**Payroll Summary:**')
                    ->line('Total Due: KES ' . number_format($payroll->total_due, 2))
                    ->line('Total Paid: KES ' . number_format($payroll->total_paid, 2))
                    ->line('Outstanding Balance: KES ' . number_format($payroll->outstanding_balance, 2))
                    ->line('')
                    ->line('Thank you for your service!')
                    ->salutation('Regards, ' . config('app.name'));
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