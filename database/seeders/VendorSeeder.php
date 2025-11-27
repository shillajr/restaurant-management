<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vendor;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            ['name' => 'Fresh Farm Suppliers', 'email' => 'orders@freshfarm.example', 'phone' => '+255710000001'],
            ['name' => 'Quality Meats Ltd', 'email' => 'sales@qualitymeats.example', 'phone' => '+255710000002'],
            ['name' => 'Premium Foods Co', 'email' => 'contact@premiumfoods.example', 'phone' => '+255710000003'],
            ['name' => 'Ocean Fresh Suppliers', 'email' => 'support@oceanfresh.example', 'phone' => '+255710000004'],
            ['name' => 'Dairy Delights Co', 'email' => 'info@dairydelights.example', 'phone' => '+255710000005'],
            ['name' => 'Spice Market Ltd', 'email' => 'orders@spicemarket.example', 'phone' => '+255710000006'],
            ['name' => 'Green Valley Suppliers', 'email' => 'hello@greenvalley.example', 'phone' => '+255710000007'],
        ];

        foreach ($vendors as $vendor) {
            Vendor::firstOrCreate(['name' => $vendor['name']], $vendor);
        }
    }
}
