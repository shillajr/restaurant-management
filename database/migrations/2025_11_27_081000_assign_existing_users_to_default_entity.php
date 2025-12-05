<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $entity = DB::table('entities')->where('slug', 'default')->first();

        if (! $entity) {
            $id = DB::table('entities')->insertGetId([
                'name' => config('app.name', 'RMS Default'),
                'slug' => 'default',
                'timezone' => config('app.timezone'),
                'currency' => 'USD',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $id = $entity->id;
        }

        DB::table('users')
            ->whereNull('entity_id')
            ->update(['entity_id' => $id]);
    }

    public function down(): void
    {
        // Intentionally left blank; we do not want to orphan users by removing entity assignments.
    }
};
