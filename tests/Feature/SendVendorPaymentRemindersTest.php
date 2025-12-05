<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\FinancialLedger;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\VendorPaymentReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendVendorPaymentRemindersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function sends_reminder_for_due_ledger_and_updates_schedule(): void
    {
        Notification::fake();

        $vendor = Vendor::create([
            'name' => 'Fresh Farm',
            'email' => 'ap@freshfarm.test',
        ]);

        $ledger = new FinancialLedger([
            'ledger_type' => FinancialLedger::TYPE_LIABILITY,
            'status' => FinancialLedger::STATUS_OPEN,
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->name,
            'contact_email' => $vendor->email,
            'currency' => 'USD',
            'principal_amount' => 200,
            'outstanding_amount' => 200,
            'paid_amount' => 0,
            'opened_at' => Carbon::now()->subWeek(),
            'next_reminder_due_at' => Carbon::now()->subDay(),
        ]);

        $ledger->source_type = User::class;
        $ledger->source_id = User::factory()->create()->id;
        $ledger->save();

        $this->artisan('ledgers:send-vendor-reminders')->assertExitCode(0);

        Notification::assertSentOnDemand(VendorPaymentReminder::class, function ($notification, $channels, $notifiable) use ($vendor) {
            return in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === $vendor->email;
        });

        $ledger->refresh();

        $this->assertNotNull($ledger->last_reminder_sent_at);
        $this->assertTrue($ledger->next_reminder_due_at->greaterThan(Carbon::now()));

        $activity = Activity::where('event', 'vendor_payment_reminder')->first();
        $this->assertNotNull($activity);
        $this->assertSame($ledger->id, $activity->subject_id);
        $this->assertContains($vendor->email, $activity->properties['recipients']);
    }

    #[Test]
    public function dry_run_reports_without_sending_notifications(): void
    {
        Notification::fake();

        $ledger = new FinancialLedger([
            'ledger_type' => FinancialLedger::TYPE_LIABILITY,
            'status' => FinancialLedger::STATUS_OPEN,
            'vendor_name' => 'No Email Vendor',
            'outstanding_amount' => 100,
            'principal_amount' => 100,
            'opened_at' => Carbon::now()->subDays(2),
            'next_reminder_due_at' => Carbon::now()->subHour(),
        ]);
        $ledger->source_type = User::class;
        $ledger->source_id = User::factory()->create()->id;
        $ledger->save();

        $this->artisan('ledgers:send-vendor-reminders', ['--dry-run' => true])->assertExitCode(0);

        Notification::assertNothingSent();
        $ledger->refresh();
        $this->assertNull($ledger->last_reminder_sent_at);
    }
}
