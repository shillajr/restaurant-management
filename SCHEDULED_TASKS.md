# Scheduled Tasks Configuration

This document describes the automated scheduled tasks for the Restaurant Management System.

## Laravel 11+ Task Scheduler

In Laravel 11+, scheduled tasks are defined in `routes/console.php` instead of `app/Console/Kernel.php`.

## Configured Tasks

### 1. Daily Loyverse Sales Sync
**Schedule:** Daily at 2:00 AM  
**Purpose:** Automatically sync yesterday's sales data from Loyverse POS  
**Implementation:**
```php
Schedule::call(function () {
    app(LoyverseService::class)->syncDailySales(now()->format('Y-m-d'));
})->dailyAt('02:00')->name('sync-loyverse-sales')->withoutOverlapping();
```

**Features:**
- Runs at 2:00 AM to avoid peak hours
- Uses `withoutOverlapping()` to prevent concurrent executions
- Named task for better monitoring: `sync-loyverse-sales`
- Syncs sales data for accurate P&L reporting

### 2. Pending Approvals Reminder
**Schedule:** Daily at 9:00 AM  
**Purpose:** Remind managers about pending requisition approvals  
**Implementation:**
```php
Schedule::command('approvals:remind')->dailyAt('09:00')->name('remind-pending-approvals');
```

**Features:**
- Runs at 9:00 AM (start of business day)
- Sends notifications to all admin/manager users
- Logs reminder activity for audit trail
- Displays pending requisition details

## Manual Testing

### Test Loyverse Sales Sync
```bash
# Run manually via Tinker
php artisan tinker
>>> app(\App\Services\LoyverseService::class)->syncDailySales(now()->format('Y-m-d'));
```

### Test Approvals Reminder
```bash
# Run the command directly
php artisan approvals:remind
```

**Expected Output:**
```
Found 3 pending requisition(s).
Reminders sent to 2 manager(s).

+----+------------+----------------+-------+--------------+
| ID | Chef       | Requested For  | Items | Created      |
+----+------------+----------------+-------+--------------+
| 1  | John Chef  | 2025-11-26     | 5     | 2 hours ago  |
| 2  | Jane Cook  | 2025-11-27     | 3     | 1 day ago    |
| 3  | Bob Smith  | 2025-11-28     | 7     | 3 days ago   |
+----+------------+----------------+-------+--------------+
```

## Running the Scheduler

### Development Environment

To run the scheduler locally for testing:

```bash
# Run the scheduler every minute (simulates cron)
php artisan schedule:work
```

This command will check for scheduled tasks every minute and run them if they're due.

### Production Environment

Add this single cron entry to your server's crontab:

```bash
# Edit crontab
crontab -e

# Add this line (adjust path to your project)
* * * * * cd /path/to/restaurant-management && php artisan schedule:run >> /dev/null 2>&1
```

**Important:** This cron entry runs every minute and Laravel's scheduler determines which tasks should actually execute.

## Monitoring Scheduled Tasks

### List All Scheduled Tasks
```bash
php artisan schedule:list
```

**Expected Output:**
```
┌─────────────────────────────────────┬──────────────────┬─────────────┬────────────────────┐
│ Description                         │ Expression       │ Next Due    │ Command            │
├─────────────────────────────────────┼──────────────────┼─────────────┼────────────────────┤
│ sync-loyverse-sales                 │ 0 2 * * *        │ 5 hours     │ Closure            │
│ remind-pending-approvals            │ 0 9 * * *        │ 12 hours    │ approvals:remind   │
└─────────────────────────────────────┴──────────────────┴─────────────┴────────────────────┘
```

### View Schedule Execution
```bash
# Run scheduler in foreground (useful for debugging)
php artisan schedule:work --verbose
```

## Task Details

### Loyverse Sales Sync Task

**File:** `routes/console.php`  
**Service:** `app/Services/LoyverseService.php`  
**Method:** `syncDailySales()`

**What it does:**
1. Connects to Loyverse API
2. Fetches all sales for the specified date
3. Imports sales data into `loyverse_sales` table
4. Updates revenue metrics for dashboard

**Configuration Required:**
- Set `LOYVERSE_API_KEY` in `.env` file
- Configure Loyverse API access in `config/services.php`

**Error Handling:**
- Logs all API errors
- Sends notification on failure (if configured)
- Continues operation even if some records fail

### Approvals Reminder Command

**File:** `app/Console/Commands/RemindPendingApprovals.php`  
**Signature:** `approvals:remind`

**What it does:**
1. Queries all pending chef requisitions
2. Finds all admin/manager users
3. Sends reminder notifications
4. Logs activity for audit trail
5. Displays summary in console

**Notification Methods:**
- Console output (always)
- Log file entries (always)
- Email notifications (optional, commented out)

**To enable email notifications:**
1. Configure mail settings in `.env`
2. Uncomment the Mail::to() line in the command
3. Create a `PendingApprovalsReminder` mailable

## Advanced Scheduling Options

### Additional Features Available

```php
// Run on weekdays only
->weekdays()

// Run on weekends only
->weekends()

// Run on specific days
->days([1, 3, 5]) // Monday, Wednesday, Friday

// Run hourly
->hourly()

// Run every 30 minutes
->everyThirtyMinutes()

// Run with timezone
->timezone('America/New_York')

// Send output to log
->sendOutputTo(storage_path('logs/scheduler.log'))

// Email output on success
->emailOutputOnSuccess('admin@restaurant.com')

// Email output on failure
->emailOutputOnFailure('admin@restaurant.com')

// Prevent overlapping executions
->withoutOverlapping()

// Run in background
->runInBackground()

// Run only in specific environments
->environments(['production'])
```

## Adding New Scheduled Tasks

To add a new scheduled task:

1. **Edit routes/console.php:**
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('your:command')
    ->daily()
    ->name('your-task-name');
```

2. **Or use a closure:**
```php
Schedule::call(function () {
    // Your task logic here
})->daily()->name('your-task-name');
```

3. **Create a command (if needed):**
```bash
php artisan make:command YourCommandName
```

## Troubleshooting

### Task Not Running

1. **Check if scheduler is running:**
   ```bash
   php artisan schedule:work
   ```

2. **Verify cron is configured (production):**
   ```bash
   crontab -l
   ```

3. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Debugging Tasks

Run tasks manually to test:
```bash
# Run specific command
php artisan approvals:remind

# Run all scheduled tasks
php artisan schedule:run

# Test schedule without running
php artisan schedule:test
```

## Performance Considerations

- **Loyverse Sync:** May take 1-5 minutes depending on sales volume
- **Approvals Reminder:** Typically completes in < 1 second
- Both tasks use database transactions for data integrity
- `withoutOverlapping()` prevents multiple simultaneous executions

## Security Notes

- All scheduled tasks run with application privileges
- Ensure `.env` file is not accessible via web
- API keys are stored securely in environment variables
- Log files may contain sensitive data - restrict access

## Related Documentation

- [Laravel Task Scheduling](https://laravel.com/docs/11.x/scheduling)
- [Loyverse API Documentation](https://developer.loyverse.com/)
- [Dashboard README](./DASHBOARD_README.md)

---

**Last Updated:** November 25, 2025  
**Laravel Version:** 11+
