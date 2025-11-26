# Items Management System - Implementation Guide

## Overview
The Items Management System provides a comprehensive solution for managing all purchasable items in the restaurant. This implementation includes all required features as specified in the requirements.

## Features Implemented

### 1. Item Master Creation ✅

All items are stored in the `items` table with the following required fields:

#### Required Fields:
- **Item Name**: Unique identifier for the item
- **Category**: Predefined categories for organization
  - Vegetables, Fruits, Meat, Seafood, Dairy, Grains, Cooking Oils, Spices, Beverages, Cleaning Supplies, Office Supplies
- **Unit of Measure (UoM)**: Supported units include:
  - Kilograms (kg), Grams (g), Pounds (lbs), Ounces (oz)
  - Liters (L), Milliliters (ml)
  - Pieces (pc), Dozen, Boxes, Packs
- **Vendor/Supplier**: Primary vendor for the item
- **Price**: Current purchasing price in TZS
- **Status**: Active/Inactive (controls requisition availability)

#### Optional Fields:
- **Stock**: Current stock level
- **Reorder Level**: Low stock alert threshold
- **Description**: Additional item details

### 2. Item Availability for Requisition ✅

**Selection in Requisitions:**
- Only **active** items appear in requisition dropdowns
- Items are searchable by name
- Items can be filtered by category
- Dropdown is user-friendly with search functionality

**API Endpoints Available:**
```
GET /api/items/active - Get all active items
GET /api/items/active?category=Vegetables - Filter by category
GET /api/items/active?search=tomato - Search by name
```

### 3. Additional Improvements Implemented ✅

#### a. Status Control
- Items have `active` or `inactive` status
- **Active** items: Available for requisition
- **Inactive** items: Not available for requisition (prevents deletion of items with historical records)

#### b. Price History Tracking
- Separate `item_price_history` table tracks all price changes
- Records: old price, new price, changed by, changed at
- Maintains accurate purchase records over time
- Automatic logging when item price is updated

#### c. Stock Management
- Track current stock levels
- Set reorder levels for low stock alerts
- Visual indicators for low stock items in the UI
- API endpoint for low stock alerts: `GET /api/items/low-stock`

## Database Schema

### Items Table
```sql
CREATE TABLE items (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(255) NOT NULL,
    uom VARCHAR(50) NOT NULL,
    vendor VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    stock DECIMAL(10, 2) NULL,
    reorder_level DECIMAL(10, 2) NULL,
    description TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX(name),
    INDEX(category),
    INDEX(status)
);
```

### Item Price History Table
```sql
CREATE TABLE item_price_history (
    id BIGINT PRIMARY KEY,
    item_id BIGINT FOREIGN KEY REFERENCES items(id) ON DELETE CASCADE,
    old_price DECIMAL(10, 2) NOT NULL,
    new_price DECIMAL(10, 2) NOT NULL,
    changed_by VARCHAR(255) NULL,
    changed_at TIMESTAMP NOT NULL
);
```

## File Structure

```
app/
├── Models/
│   ├── Item.php                    # Item model with scopes and helpers
│   └── ItemPriceHistory.php        # Price history tracking model
├── Http/Controllers/
│   └── ItemController.php          # CRUD operations for items
database/
├── migrations/
│   └── 2025_11_25_212311_create_items_table.php
└── seeders/
    └── ItemSeeder.php              # Sample data (30 items)
resources/views/
└── settings/
    └── index.blade.php             # Settings page with Items tab
routes/
└── web.php                         # Routes configuration
```

## Usage Guide

### Accessing Items Management

1. Navigate to **Settings** from the dashboard
2. Click on the **Items Management** tab
3. You will see a table of all items with search and filter options

### Adding a New Item

1. Click the **"Add New Item"** button
2. Fill in the required fields:
   - Item Name
   - Category (dropdown)
   - Unit of Measure (dropdown)
   - Vendor/Supplier name
   - Price (in TZS)
   - Status (Active/Inactive)
3. Optionally add:
   - Current Stock
   - Reorder Level
   - Description
4. Click **"Add Item"** to save

### Editing an Item

1. Click **"Edit"** next to any item in the table
2. Modify the fields as needed
3. Click **"Update Item"** to save
4. **Note**: Price changes are automatically logged to price history

### Managing Item Status

- Click on the status badge (Active/Inactive) to toggle
- **Active** items appear in requisition forms
- **Inactive** items are hidden from requisition but retain historical data

### Searching and Filtering

**Search Box**: Type item name or vendor to search
**Category Filter**: Select a category to filter items
**Status Filter**: Show only active or inactive items

### Low Stock Alerts

Items with stock levels at or below the reorder level are:
- Highlighted in **red** in the stock column
- Marked with a "Low stock!" indicator
- Available via API endpoint for alerts

## API Reference

### Get Active Items
```
GET /api/items/active
Optional params: ?category=Vegetables&search=tomato
Response: JSON array of active items
```

### Get Low Stock Items
```
GET /api/items/low-stock
Response: JSON array of items with stock <= reorder_level
```

## Model Methods

### Item Model

**Scopes:**
- `Item::active()` - Get only active items
- `Item::byCategory($category)` - Filter by category
- `Item::lowStock()` - Get items with low stock

**Helper Methods:**
- `isAvailable()` - Check if item is active
- `isLowStock()` - Check if stock is below reorder level
- `logPriceChange($old, $new, $user)` - Log price change to history

## Integration with Requisitions

To use items in requisition forms:

1. Fetch active items via API:
```javascript
fetch('/api/items/active?category=Vegetables')
    .then(response => response.json())
    .then(items => {
        // Populate dropdown
    });
```

2. The item object includes:
```json
{
    "id": 1,
    "name": "Tomatoes",
    "category": "Vegetables",
    "uom": "kg",
    "vendor": "Fresh Farm Suppliers",
    "price": 3500,
    "status": "active",
    "stock": 45,
    "reorder_level": 20
}
```

## Sample Data

The system comes with 30 pre-populated items via `ItemSeeder`:
- 10 food items (vegetables, meat, seafood, dairy)
- 5 grain items
- 2 cooking oils
- 3 spices
- 2 beverages
- 5 cleaning supplies
- 2 office supplies
- 3 fruits

To seed the database:
```bash
php artisan db:seed --class=ItemSeeder
```

## Validation Rules

**Item Creation/Update:**
- `name`: Required, string, max 255 characters
- `category`: Required, string, max 255 characters
- `uom`: Required, string, max 50 characters
- `vendor`: Required, string, max 255 characters
- `price`: Required, numeric, minimum 0
- `status`: Required, must be 'active' or 'inactive'
- `stock`: Optional, numeric, minimum 0
- `reorder_level`: Optional, numeric, minimum 0
- `description`: Optional, string

## Best Practices

1. **Always set reorder levels** for inventory tracking
2. **Use consistent vendor names** to avoid duplicates
3. **Keep prices updated** - price history maintains accuracy
4. **Use inactive status** instead of deleting items with historical data
5. **Review low stock alerts** regularly via the API endpoint
6. **Categorize items properly** for easier filtering in requisitions

## Future Enhancements (Optional)

1. **Multiple Vendor Support**
   - Allow items to have multiple vendors with different prices
   - Set a preferred vendor per item

2. **Vendor Master**
   - Create separate vendor management
   - Link items to vendor IDs instead of text

3. **Automated Stock Updates**
   - Integrate with purchase orders
   - Auto-deduct stock on requisition fulfillment

4. **Barcode Support**
   - Add barcode field for scanning
   - Generate barcodes for items

5. **Price Alerts**
   - Notify when vendor changes prices
   - Track price trends over time

## Troubleshooting

**Items not appearing in requisition:**
- Check item status is "active"
- Verify item exists in database
- Check API endpoint `/api/items/active`

**Price history not logging:**
- Ensure user is authenticated (for changed_by field)
- Check that price actually changed
- Verify foreign key relationship

**Search not working:**
- Clear browser cache
- Check Alpine.js is loaded
- Verify search query in browser console

## Support

For issues or questions, refer to:
- Database migration: `database/migrations/2025_11_25_212311_create_items_table.php`
- Item model: `app/Models/Item.php`
- Controller: `app/Http/Controllers/ItemController.php`
- View: `resources/views/settings/index.blade.php` (Items Management tab)
