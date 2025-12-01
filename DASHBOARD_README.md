# Restaurant Management Dashboard

A comprehensive restaurant management system with role-based access control, KPI tracking, and maker-checker workflow for requisitions.

## Dashboard Features

### 🎯 KPI Cards (Admin, Manager, Finance roles)
- **Today's Sales**: Total revenue and transaction count from POS
- **Today's Expenses**: Total expenses with item count
- **Today's Profit**: Net profit with margin percentage
- **Pending Approvals**: Count of requisitions awaiting approval

### 🚀 Quick Actions (Role-based)
- **New Requisition** (Chef, Manager, Purchaser, Admin): Submit ingredient requests
- **Purchase Order** (Purchaser, Finance, Manager, Admin): Create purchase orders
- **Approve & Send POs** (Manager, Purchaser, Finance, Admin): Approve purchase orders and dispatch vendor WhatsApp alerts
- **Add Expense** (Finance, Manager, Admin): Record expenses
- **View Reports** (Admin, Manager, Finance, Purchaser): Access financial and operational reports
- **Manage Payroll** (Admin, Finance): Process payroll
- **Settings** (Admin): System configuration

### 📊 Recent Requisitions Table
- Real-time list of requisitions
- Status indicators (Pending, Approved, Rejected)
- Quick view action
- Role-based filtering (Chefs see only their own)

## Getting Started

### Access the Dashboard

1. **Start the server** (if not already running):
   ```bash
   php artisan serve
   ```

2. **Open your browser** and navigate to:
   ```
   http://127.0.0.1:8000
   ```

3. **Login** with one of the test accounts:

### Test Accounts

All test accounts use the password: `password`

| Role | Email | Permissions |
|------|-------|-------------|
| **Admin** | admin@restaurant.com | Full system access |
| **Manager** | manager@restaurant.com | All features except payroll and settings; can approve POs with WhatsApp vendor sends |
| **Chef** | chef@restaurant.com | Requisition management only |
| **Purchaser** | purchaser@restaurant.com | All features except payroll, expenses, and settings |
| **Finance** | finance@restaurant.com | All features except requisitions and settings |
| **Auditor** | auditor@restaurant.com | Reports access only |

## Dashboard Views by Role

### Admin/Manager Dashboard
- All KPI cards visible
- All quick action buttons
- Complete requisitions table
- Pending approvals counter

### Chef Dashboard
- Limited KPI visibility
- New Requisition button
- Personal requisitions only
- Status tracking

### Finance Dashboard
- Full financial KPIs
- Expense management
- Report access
- Payroll management (Admin & Finance)

### Auditor Dashboard
- Read-only view of all data
- Activity logs access
- Report generation

## Current Sample Data

The system is pre-loaded with:

### Today's Sales (5 transactions)
- Total: **$497.65**
- Transactions: 5
- Payment methods: Cash, Credit Card

### Today's Expenses (4 items)
- Total: **$308.50**
- Categories: Food & Beverage, Utilities, Supplies

### Today's Profit
- **$189.15** (38.0% margin)

## Next Steps

To fully utilize the dashboard:

1. **Create requisitions** as a chef
2. **Approve/reject** as manager
3. **Create purchase orders** as purchaser
4. **Record expenses** as finance
5. **View reports** to analyze performance

## Technical Details

### Technologies Used
- **Backend**: Laravel 12, PHP 8.5
- **Frontend**: Tailwind CSS, Alpine.js
- **Database**: SQLite
- **Authentication**: Laravel Sanctum + Session Auth
- **Permissions**: Spatie Laravel Permission

### Key Features
- Role-based access control (RBAC)
- Real-time KPI calculations
- Responsive design (mobile-friendly)
- Activity logging
- Maker-checker workflow
- CSV/PDF export capabilities

### Database Schema
- Users with roles and permissions
- Chef Requisitions with approval workflow
- Loyverse Sales integration
- Expenses tracking
- Activity logs for audit trail

## Troubleshooting

### Can't login?
- Ensure database is seeded: `php artisan db:seed`
- Check credentials match test accounts above

### No data showing?
- Run: `php artisan db:seed --class=DashboardDataSeeder`

### Server not running?
- Run: `php artisan serve`
- Check terminal for error messages

## Development

### Reset Database
```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=DashboardDataSeeder
```

### Test Workflow
```bash
php artisan test:maker-checker-workflow
```

### View Routes
```bash
php artisan route:list
```

## API Endpoints

Dashboard data is also available via API:

- `GET /dashboard/stats` - KPI statistics
- `GET /dashboard/activity` - Recent activity feed

All API routes require authentication with Sanctum tokens.

---

**Built with Laravel 12** | **Last Updated: November 25, 2025**
