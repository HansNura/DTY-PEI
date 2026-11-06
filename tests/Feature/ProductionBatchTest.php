<?php

namespace Tests\Feature;

use App\Enums\RollStatus;
use App\Enums\UserRole;
use App\Models\DtyRoll;
use App\Models\ProductionBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionBatchTest extends TestCase
{
    use RefreshDatabase;

    private function warehouseUser(): User
    {
        return User::factory()->role(UserRole::AdminGudang)->create();
    }

    public function test_warehouse_user_can_create_a_production_batch(): void
    {
        $user = $this->warehouseUser();

        $this->actingAs($user)
            ->post(route('warehouse.batches.store'), [
                'nomor_merge' => '4d9164',
                'denier' => 150,
                'filamen' => 48,
                'intermingled' => '0',
                'twist_direction' => 'z',
                'production_date' => '2026-11-03',
            ])
            ->assertRedirect(route('warehouse.batches.index'));

        $batch = ProductionBatch::firstOrFail();
        $this->assertSame('4D9164', $batch->nomor_merge);
        $this->assertSame('150/48 NI Z', $batch->specification);
        $this->assertTrue($batch->is_active);
        $this->assertSame($user->id, $batch->created_by);
    }

    public function test_only_one_batch_can_be_active_at_a_time(): void
    {
        $user = $this->warehouseUser();
        $first = ProductionBatch::factory()->create(['is_active' => true]);
        $second = ProductionBatch::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->patch(route('warehouse.batches.activate', $second))
            ->assertRedirect();

        $this->assertFalse($first->fresh()->is_active);
        $this->assertTrue($second->fresh()->is_active);
        $this->assertSame(1, ProductionBatch::query()->active()->count());
    }

    public function test_merge_must_be_unique(): void
    {
        $this->warehouseUser();
        ProductionBatch::factory()->create(['nomor_merge' => '4D9164']);

        $this->actingAs($this->warehouseUser())
            ->post(route('warehouse.batches.store'), [
                'nomor_merge' => '4d9164',
                'denier' => 150,
                'filamen' => 48,
                'production_date' => '2026-11-03',
            ])
            ->assertSessionHasErrors('nomor_merge');
    }

    public function test_batch_with_bobbins_cannot_be_edited(): void
    {
        $user = $this->warehouseUser();
        $batch = ProductionBatch::factory()->create([
            'nomor_merge' => '4D9164',
            'created_by' => $user->id,
        ]);
        DtyRoll::factory()->create([
            'production_batch_id' => $batch->id,
            'status' => RollStatus::ReadyToPack,
        ]);

        $this->actingAs($user)
            ->put(route('warehouse.batches.update', $batch), [
                'nomor_merge' => '4D9999',
                'denier' => 150,
                'filamen' => 48,
                'production_date' => '2026-11-03',
            ])
            ->assertSessionHasErrors('nomor_merge');

        $this->assertSame('4D9164', $batch->fresh()->nomor_merge);
    }

    public function test_only_warehouse_users_can_manage_production_batches(): void
    {
        $this->actingAs(User::factory()->role(UserRole::Logistik)->create())
            ->get(route('warehouse.batches.index'))
            ->assertForbidden();
    }
}
