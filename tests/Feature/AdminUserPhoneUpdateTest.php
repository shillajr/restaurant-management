<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserPhoneUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chef', 'guard_name' => 'web']);
    }

    #[Test]
    public function admin_can_update_a_users_phone_number(): void
    {
        $entity = $this->createEntity();

        $admin = User::factory()->create([
            'entity_id' => $entity->id,
            'phone' => '255700000001',
        ]);
        $admin->assignRole('admin');

        $staff = User::factory()->create([
            'entity_id' => $entity->id,
            'phone' => '255700000002',
            'email' => '255700000002@users.local',
        ]);
        $staff->assignRole('chef');

        $response = $this->actingAs($admin)->put(route('admin.users.contact.update', $staff), [
            '_contact_user_id' => $staff->id,
            'phone' => '(255) 700-111-222',
        ]);

        $response->assertRedirect(route('settings', ['tab' => 'users']));

        $staff->refresh();

        $this->assertSame('255700111222', $staff->phone);
        $this->assertSame('255700111222@users.local', $staff->email);
    }

    #[Test]
    public function phone_number_must_be_unique(): void
    {
        $entity = $this->createEntity();

        $admin = User::factory()->create([
            'entity_id' => $entity->id,
            'phone' => '255700000010',
        ]);
        $admin->assignRole('admin');

        $staff = User::factory()->create([
            'entity_id' => $entity->id,
            'phone' => '255700000011',
        ]);
        $staff->assignRole('chef');

        $other = User::factory()->create([
            'entity_id' => $entity->id,
            'phone' => '255700000022',
        ]);
        $other->assignRole('chef');

        $response = $this->actingAs($admin)->from(route('settings', ['tab' => 'users']))->put(route('admin.users.contact.update', $staff), [
            '_contact_user_id' => $staff->id,
            'phone' => $other->phone,
        ]);

        $response->assertSessionHasErrors(['phone'], null, 'contact');

        $staff->refresh();

        $this->assertSame('255700000011', $staff->phone);
    }

    private function createEntity(): Entity
    {
        return Entity::create([
            'name' => 'Test Restaurant',
            'slug' => Str::slug('test-' . Str::uuid()),
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
        ]);
    }
}
