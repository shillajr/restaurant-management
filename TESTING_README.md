# Chef Requisition Workflow - PHPUnit Tests

## ✅ Test Suite Created

Comprehensive test suite for the restaurant management system's chef requisition workflow has been created.

## 📁 Files Created

### Test Files
- **tests/Feature/ChefRequisitionWorkflowTest.php** - 27 comprehensive test cases

### Factory Files
- **database/factories/ChefRequisitionFactory.php** - Factory for creating test requisitions
- **database/factories/PurchaseOrderFactory.php** - Factory for creating test purchase orders
- **database/factories/ExpenseFactory.php** - Factory for creating test expenses

### Model & Migration
- **app/Models/PurchaseOrder.php** - Purchase order model with relationships
- **database/migrations/2025_11_25_075757_create_purchase_orders_table.php** - Database schema

## 🧪 Test Coverage

### 1. Authentication & Authorization (4 tests)
- ✅ test_chef_can_create_requisition
- ✅ test_chef_can_view_own_requisitions
- ✅ test_manager_can_view_all_requisitions
- ✅ test_unauthenticated_user_cannot_access_api

### 2. Maker-Checker Approval Process (7 tests)
- ✅ test_manager_can_approve_pending_requisition
- ✅ test_manager_can_reject_pending_requisition
- ✅ test_cannot_approve_already_approved_requisition
- ✅ test_cannot_approve_rejected_requisition
- ✅ test_chef_cannot_approve_own_requisition
- ✅ test_unauthorized_user_cannot_approve_requisition
- ✅ test_audit_log_created_on_approval

### 3. Purchase Order Management (5 tests)
- ✅ test_purchaser_can_create_purchase_order_from_approved_requisition
- ✅ test_cannot_create_purchase_order_from_pending_requisition
- ✅ test_cannot_create_purchase_order_from_rejected_requisition
- ✅ test_purchaser_can_mark_purchase_order_as_purchased
- ✅ test_purchase_order_validation_requires_valid_amounts

### 4. Expense Tracking (1 test)
- ✅ test_expense_created_when_purchase_marked_as_purchased

### 5. Audit Trail & Activity Logging (3 tests)
- ✅ test_audit_log_created_on_requisition_creation
- ✅ test_audit_log_created_on_approval
- ✅ test_audit_log_created_on_rejection

### 6. Data Validation (2 tests)
- ✅ test_requisition_validation_requires_future_date
- ✅ test_requisition_validation_requires_items

### 7. Role-Based Access Control (3 tests)
- ✅ test_user_can_only_delete_own_pending_requisitions
- ✅ test_cannot_delete_approved_requisition
- ✅ Includes role restrictions throughout all tests

### 8. Complete Workflow Integration (1 test)
- ✅ test_complete_workflow_from_requisition_to_expense

**Total: 27 test cases**

## 🔧 Test Setup

Each test includes:
- **Role creation**: chef, manager, purchaser, finance
- **Permission setup**: 8 granular permissions
- **Test users**: Pre-configured with appropriate roles
- **Fresh database**: Using RefreshDatabase trait
- **Sanctum authentication**: API token-based auth

## 📊 Test Data Factories

### ChefRequisitionFactory
```php
// States available:
ChefRequisition::factory()->pending()->create();
ChefRequisition::factory()->approved()->create();
ChefRequisition::factory()->rejected()->create();
```

### PurchaseOrderFactory
```php
// States available:
PurchaseOrder::factory()->assigned()->create();
PurchaseOrder::factory()->purchased()->create();
```

### ExpenseFactory
```php
// Simple factory:
Expense::factory()->create();
```

## 🚀 Running the Tests

### Run All Tests
```bash
php artisan test
```

### Run Only Workflow Tests
```bash
php artisan test --filter=ChefRequisitionWorkflowTest
```

### Run Specific Test
```bash
php artisan test --filter=test_chef_can_create_requisition
```

### Run with Coverage
```bash
php artisan test --coverage
```

### Verbose Output
```bash
php artisan test --filter=ChefRequisitionWorkflowTest --verbose
```

## ⚠️ Current Status

### ✅ Completed
- All 27 test cases written
- Factory classes created
- PurchaseOrder model and migration created
- Database schema updated
- Test structure follows Laravel best practices

### ⚙️ Pending Implementation

The tests are **currently failing** because the API controllers need to be fully implemented. The tests serve as a specification for required functionality:

1. **ChefRequisitionController** needs:
   - `store()` method with validation
   - `index()` method with role-based filtering
   - `approve()` method
   - `reject()` method
   - `destroy()` method
   - Authorization checks

2. **PurchaseOrderController** needs:
   - `store()` method
   - `markPurchased()` method with expense creation
   - Validation rules
   - Authorization checks

3. **Authorization Policies** need to be created:
   - ChefRequisitionPolicy
   - PurchaseOrderPolicy

## 📝 Test Examples

### Example 1: Maker-Checker Workflow Test
```php
public function test_manager_can_approve_pending_requisition()
{
    $requisition = ChefRequisition::factory()->pending()->create([
        'chef_id' => $this->chef->id
    ]);

    $this->actingAs($this->manager, 'sanctum');

    $response = $this->postJson("/api/requisitions/{$requisition->id}/approve", [
        'approval_notes' => 'Approved for purchase'
    ]);

    $response->assertStatus(200)
             ->assertJsonFragment([
                 'status' => 'approved',
                 'checker_id' => $this->manager->id
             ]);

    $this->assertDatabaseHas('chef_requisitions', [
        'id' => $requisition->id,
        'status' => 'approved',
        'checker_id' => $this->manager->id
    ]);
}
```

### Example 2: Complete Workflow Test
The `test_complete_workflow_from_requisition_to_expense()` test demonstrates the entire process:
1. Chef creates requisition
2. Manager approves it
3. Purchaser creates purchase order
4. Purchaser marks as purchased
5. Expense is automatically created
6. Audit trail is verified

## 🎯 Next Steps

To make all tests pass, you need to:

1. **Implement Controller Methods:**
   ```bash
   # Update ChefRequisitionController
   # Update PurchaseOrderController
   ```

2. **Create Policies:**
   ```bash
   php artisan make:policy ChefRequisitionPolicy --model=ChefRequisition
   php artisan make:policy PurchaseOrderPolicy --model=PurchaseOrder
   ```

3. **Add Validation Rules:**
   - Create Form Request classes
   - Or add validation directly in controllers

4. **Register Policies:**
   - Update `AuthServiceProvider`

5. **Run Tests:**
   ```bash
   php artisan test --filter=ChefRequisitionWorkflowTest
   ```

## 💡 Benefits

These tests provide:
- **Documentation**: Tests show exactly how the system should work
- **Regression Prevention**: Catch bugs before deployment
- **Confidence**: Refactor code knowing tests will catch errors
- **Specification**: Tests define the expected API behavior
- **Quality Assurance**: Ensure workflow integrity

## 📖 Test-Driven Development (TDD)

The tests are ready to guide implementation:
1. Pick a failing test
2. Implement the minimum code to make it pass
3. Refactor if needed
4. Move to next test
5. Repeat until all pass

This ensures you build exactly what's needed, no more, no less.

---

**All test infrastructure is in place and ready for controller implementation!** 🎉
