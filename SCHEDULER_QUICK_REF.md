# Scheduled Tasks Quick Reference

## ✅ Configured Tasks

### 📊 Daily Sales Sync
- **When:** 2:00 AM daily
- **What:** Syncs Loyverse POS sales data
- **Command:** Automatic (closure)
- **Next Due:** Check with `php artisan schedule:list`

### 📧 Approval Reminders  
- **When:** 9:00 AM daily
- **What:** Notifies managers of pending requisitions
- **Command:** `php artisan approvals:remind`
- **Next Due:** Check with `php artisan schedule:list`

## 🚀 Quick Commands

```bash
# View all scheduled tasks
php artisan schedule:list

# Run scheduler (for testing)
php artisan schedule:work

# Test approval reminders manually
php artisan approvals:remind

# Run all due tasks immediately
php artisan schedule:run
```

## 📋 Setup Checklist

- [x] Tasks defined in `routes/console.php`
- [x] `RemindPendingApprovals` command created
- [x] Both tasks tested and working
- [ ] Set `LOYVERSE_API_KEY` in `.env` (for production)
- [ ] Configure cron job (for production)

## 🔧 Production Setup

Add to crontab:
```bash
* * * * * cd /path/to/restaurant-management && php artisan schedule:run >> /dev/null 2>&1
```

## 📊 Current Status

Run `php artisan schedule:list` to see:
```
0 2 * * *  sync-loyverse-sales ........... Next Due: 18 hours from now
0 9 * * *  php artisan approvals:remind .. Next Due: 1 hour from now
```

---
See [SCHEDULED_TASKS.md](./SCHEDULED_TASKS.md) for full documentation.
