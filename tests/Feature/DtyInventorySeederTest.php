<?php

namespace Tests\Feature;

use App\Enums\RollStatus;
use App\Models\DtyCarton;
use App\Models\DtyRoll;
use App\Models\ProductionBatch;
use Database\Seeders\DtyInventorySeeder;
use Database\Seeders\ProductionBatchSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DtyInventorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_seeder_creates_varied_demo_data_and_is_idempotent(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(ProductionBatchSeeder::class);
        $this->seed(DtyInventorySeeder::class);
        $this->seed(DtyInventorySeeder::class);

        $this->assertSame(5, ProductionBatch::count());
        $this->assertSame(9, DtyCarton::count());
        $this->assertSame(70, DtyRoll::count());
        $this->assertSame(12, DtyRoll::where('status', RollStatus::ReadyToPack)->count());
        $this->assertSame(2, DtyRoll::where('status', RollStatus::Overweight)->count());
        $this->assertSame(2, DtyRoll::where('status', RollStatus::Waste)->count());
        $this->assertSame(40, DtyRoll::where('status', RollStatus::InStock)->count());
        $this->assertSame(1, DtyRoll::where('status', RollStatus::Allocated)->count());
        $this->assertSame(1, DtyRoll::where('status', RollStatus::Loaded)->count());
        $this->assertSame(6, DtyRoll::where('status', RollStatus::Delivered)->count());
        $this->assertSame(6, DtyRoll::where('status', RollStatus::Cancelled)->count());
    }
}
