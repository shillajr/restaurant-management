<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\User;
use App\Models\EntityGeneralSetting;
use App\Models\EntityProfileSetting;
use App\Models\EntityNotificationSetting;
use App\Models\EntityIntegrationSetting;
use App\Models\EntitySecuritySetting;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            // Requisition permissions
            'view requisitions',
            'create requisitions',
            'edit requisitions',
            'delete requisitions',
            'approve requisitions',
            
            // Purchase order permissions
            'view purchase orders',
            'create purchase orders',
            'edit purchase orders',
            'delete purchase orders',
            'mark purchased',
            
            // Expense permissions
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',
            'approve expenses',
            
            // Payroll permissions
            'view payroll',
            'create payroll',
            'edit payroll',
            'delete payroll',
            'mark paid',
            
            // Report permissions
            'view reports',
            'export reports',
            
            // User management
            'manage users',
            'manage roles',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create Roles and Assign Permissions
        
        // Admin - Full Access
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Manager - Most Access
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view requisitions',
            'approve requisitions',
            'view purchase orders',
            'create purchase orders',
            'edit purchase orders',
            'mark purchased',
            'view expenses',
            'create expenses',
            'approve expenses',
            'view payroll',
            'create payroll',
            'edit payroll',
            'mark paid',
            'view reports',
            'export reports',
        ]);

        // Chef - Requisition Creator
        $chef = Role::create(['name' => 'chef']);
        $chef->givePermissionTo([
            'view requisitions',
            'create requisitions',
            'edit requisitions',
            'delete requisitions',
        ]);

        // Purchaser - Purchase Orders
        $purchaser = Role::create(['name' => 'purchaser']);
        $purchaser->givePermissionTo([
            'view requisitions',
            'view purchase orders',
            'create purchase orders',
            'edit purchase orders',
            'mark purchased',
            'view expenses',
            'create expenses',
        ]);

        // Finance - Expenses and Payroll
        $finance = Role::create(['name' => 'finance']);
        $finance->givePermissionTo([
            'view expenses',
            'create expenses',
            'edit expenses',
            'approve expenses',
            'view payroll',
            'create payroll',
            'edit payroll',
            'mark paid',
            'view reports',
            'export reports',
        ]);

        // Auditor - View Only
        $auditor = Role::create(['name' => 'auditor']);
        $auditor->givePermissionTo([
            'view requisitions',
            'view purchase orders',
            'view expenses',
            'view payroll',
            'view reports',
            'export reports',
        ]);

        // Ensure default entity exists
        $entity = Entity::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => config('app.name', 'Restaurant Management System'),
                'timezone' => config('app.timezone', 'America/Los_Angeles'),
                'currency' => 'USD',
                'is_active' => true,
            ]
        );

        EntityGeneralSetting::firstOrCreate(
            ['entity_id' => $entity->id],
            [
                'timezone' => $entity->timezone,
                'currency' => $entity->currency,
                'date_format' => 'm/d/Y',
                'language' => config('app.locale', 'en'),
            ]
        );

        EntityProfileSetting::firstOrCreate(
            ['entity_id' => $entity->id],
            [
                'restaurant_name' => $entity->name,
                'email' => 'info@restaurant.com',
                'phone' => '+1-555-0100',
            ]
        );

        EntityNotificationSetting::firstOrCreate(
            ['entity_id' => $entity->id],
            [
                'notify_requisitions' => true,
                'notify_expenses' => true,
                'notify_purchase_orders' => true,
                'notify_payroll' => false,
                'notify_email_daily' => false,
                'sms_enabled' => false,
                'whatsapp_enabled' => false,
                'sms_provider' => 'twilio',
            ]
        );

        EntityIntegrationSetting::firstOrCreate(['entity_id' => $entity->id]);

        EntitySecuritySetting::firstOrCreate(
            ['entity_id' => $entity->id],
            [
                'two_factor_enabled' => false,
                'session_timeout_enabled' => false,
                'session_timeout_minutes' => 30,
                'password_expiry_enabled' => false,
                'password_expiry_days' => 90,
            ]
        );

        // Create Test Users
        
        // Admin User
        $adminUser = User::create([
            'entity_id' => $entity->id,
            'name' => 'System Admin',
            'email' => 'admin@restaurant.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('admin');

        // Manager User
        $managerUser = User::create([
            'entity_id' => $entity->id,
            'name' => 'Restaurant Manager',
            'email' => 'manager@restaurant.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $managerUser->assignRole('manager');

        // Chef User
        $chefUser = User::create([
            'entity_id' => $entity->id,
            'name' => 'Head Chef',
            'email' => 'chef@restaurant.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $chefUser->assignRole('chef');

        // Purchaser User
        $purchaserUser = User::create([
            'entity_id' => $entity->id,
            'name' => 'Purchase Officer',
            'email' => 'purchaser@restaurant.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $purchaserUser->assignRole('purchaser');

        // Finance User
        $financeUser = User::create([
            'entity_id' => $entity->id,
            'name' => 'Finance Manager',
            'email' => 'finance@restaurant.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $financeUser->assignRole('finance');

        // Auditor User
        $auditorUser = User::create([
            'entity_id' => $entity->id,
            'name' => 'Internal Auditor',
            'email' => 'auditor@restaurant.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $auditorUser->assignRole('auditor');

        $this->command->info('Roles, permissions, and test users created successfully!');
        $this->command->info('Login credentials (all users): password');

        // Seed core master data
        $this->call([
            ItemSeeder::class,
            VendorSeeder::class,
        ]);
        $this->command->info('Item and Vendor master data seeded.');
    }
}
