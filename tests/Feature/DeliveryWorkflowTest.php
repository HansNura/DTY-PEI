<?php

namespace Tests\Feature;

use App\Enums\DeliveryOrderStatus;
use App\Enums\RollStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\DtyRoll;
use App\Models\Fleet;
use App\Models\ProofOfDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class DeliveryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_logistics_can_issue_a_delivery_order_and_download_its_pdf(): void
    {
        $logistics = User::factory()->role(UserRole::Logistik)->create();
        $driver = User::factory()->role(UserRole::Driver)->create();
        $customer = Customer::factory()->create();
        $fleet = Fleet::factory()->create(['driver_id' => $driver->id]);
        $roll = DtyRoll::factory()->create([
            'created_by' => $logistics->id,
            'nomor_lot' => 'LOT-ISSUE',
            'berat_gram' => '1250.00',
        ]);

        $this->actingAs($logistics)
            ->get(route('logistics.orders.create'))
            ->assertOk()
            ->assertSee('Terbitkan Surat Jalan');

        $this->actingAs($logistics)
            ->post(route('logistics.orders.store'), [
                'customer_id' => $customer->id,
                'fleet_id' => $fleet->id,
                'driver_id' => $driver->id,
                'tanggal' => '2026-11-01',
                'rolls' => [$roll->id],
            ])
            ->assertRedirect();

        $order = DeliveryOrder::with('items')->firstOrFail();
        $this->assertSame(DeliveryOrderStatus::Issued, $order->status);
        $this->assertSame($driver->id, $order->driver_id);
        $this->assertSame(RollStatus::Allocated, $roll->fresh()->status);
        $this->assertSame('1.25', $order->items->first()->berat_kg);

        $response = $this->get(route('logistics.orders.pdf', $order));
        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_driver_can_update_journey_scan_every_roll_and_save_customer_signature(): void
    {
        Storage::fake('local');
        [$driver, $order, $roll] = $this->makeOrder();

        $this->actingAs($driver);
        $this->get(route('driver.deliveries.show', $order))
            ->assertOk()
            ->assertSee($order->nomor_do);

        foreach ([
            DeliveryOrderStatus::Departed,
            DeliveryOrderStatus::InTransit,
            DeliveryOrderStatus::Arrived,
        ] as $status) {
            $this->patch(route('driver.deliveries.status', $order), ['status' => $status->value])
                ->assertRedirect();
        }

        $this->get(route('driver.deliveries.show', $order))
            ->assertOk()
            ->assertSee('data-receipt-form', false);

        $this->post(route('driver.deliveries.confirm', $order), [
            'received_by_name' => 'Sari Penerima',
            'scanned_tokens' => [$roll->qr_token],
            'signature' => $this->signatureData(),
        ])->assertRedirect(route('driver.deliveries.show', $order));

        $this->assertSame(DeliveryOrderStatus::Received, $order->fresh()->status);
        $this->assertSame(RollStatus::Delivered, $roll->fresh()->status);
        $this->assertDatabaseHas('delivery_order_receipt_scans', [
            'delivery_order_id' => $order->id,
            'dty_roll_id' => $roll->id,
            'result' => 'valid',
        ]);
        $proof = ProofOfDelivery::firstOrFail();
        $this->assertSame('Sari Penerima', $proof->received_by_name);
        Storage::disk('local')->assertExists($proof->signature_path);

        $this->get(route('deliveries.signature', $order))->assertOk();
        $logistics = User::factory()->role(UserRole::Logistik)->create();
        $signedPdf = $this->actingAs($logistics)->get(route('logistics.orders.pdf', $order));
        $signedPdf->assertOk();
        $this->assertStringStartsWith('%PDF', $signedPdf->getContent());
    }

    public function test_receipt_rejects_wrong_qr_and_keeps_scan_audit_without_completing_delivery(): void
    {
        [$driver, $order] = $this->makeOrder(DeliveryOrderStatus::Arrived);
        $otherRoll = DtyRoll::factory()->create();

        $this->actingAs($driver)
            ->post(route('driver.deliveries.confirm', $order), [
                'received_by_name' => 'Penerima',
                'scanned_tokens' => [$otherRoll->qr_token],
                'signature' => $this->signatureData(),
            ])
            ->assertSessionHasErrors('scanned_tokens');

        $this->assertSame(DeliveryOrderStatus::Arrived, $order->fresh()->status);
        $this->assertDatabaseHas('delivery_order_receipt_scans', [
            'delivery_order_id' => $order->id,
            'dty_roll_id' => $otherRoll->id,
            'result' => 'wrong_do',
        ]);
        $this->assertDatabaseCount('proof_of_deliveries', 0);
    }

    public function test_receipt_rejects_duplicate_and_unknown_qr_codes(): void
    {
        [$driver, $order, $roll] = $this->makeOrder(DeliveryOrderStatus::Arrived);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.confirm', $order), [
                'received_by_name' => 'Penerima',
                'scanned_tokens' => [$roll->qr_token, $roll->qr_token, 'unknown-token'],
                'signature' => $this->signatureData(),
            ])
            ->assertSessionHasErrors('scanned_tokens');

        $this->assertDatabaseHas('delivery_order_receipt_scans', [
            'delivery_order_id' => $order->id,
            'dty_roll_id' => $roll->id,
            'result' => 'duplicate',
        ]);
        $this->assertDatabaseHas('delivery_order_receipt_scans', [
            'delivery_order_id' => $order->id,
            'dty_roll_id' => null,
            'result' => 'unknown',
        ]);
        $this->assertSame(DeliveryOrderStatus::Arrived, $order->fresh()->status);
    }

    public function test_receipt_requires_every_roll_on_the_delivery_order(): void
    {
        [$driver, $order, $firstRoll] = $this->makeOrder(DeliveryOrderStatus::Arrived);
        $secondRoll = DtyRoll::factory()->create(['status' => RollStatus::Allocated]);
        DeliveryOrderItem::create([
            'delivery_order_id' => $order->id,
            'dty_roll_id' => $secondRoll->id,
            'berat_kg' => '1.50',
        ]);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.confirm', $order), [
                'received_by_name' => 'Penerima',
                'scanned_tokens' => [$firstRoll->qr_token],
                'signature' => $this->signatureData(),
            ])
            ->assertSessionHasErrors('scanned_tokens');

        $this->assertDatabaseCount('delivery_order_receipt_scans', 1);
        $this->assertDatabaseCount('proof_of_deliveries', 0);
    }

    public function test_delivery_order_cannot_exceed_fleet_capacity(): void
    {
        $logistics = User::factory()->role(UserRole::Logistik)->create();
        $driver = User::factory()->role(UserRole::Driver)->create();
        $customer = Customer::factory()->create();
        $fleet = Fleet::factory()->create([
            'driver_id' => $driver->id,
            'kapasitas_kg' => '1.00',
        ]);
        $roll = DtyRoll::factory()->create([
            'created_by' => $logistics->id,
            'berat_gram' => '1250.00',
        ]);

        $this->actingAs($logistics)
            ->post(route('logistics.orders.store'), [
                'customer_id' => $customer->id,
                'fleet_id' => $fleet->id,
                'driver_id' => $driver->id,
                'tanggal' => '2026-11-01',
                'rolls' => [$roll->id],
            ])
            ->assertSessionHasErrors('rolls');

        $this->assertDatabaseCount('delivery_orders', 0);
        $this->assertSame(RollStatus::InStock, $roll->fresh()->status);
    }

    public function test_logistics_can_filter_and_download_delivery_reports_as_pdf_and_excel(): void
    {
        $logistics = User::factory()->role(UserRole::Logistik)->create();
        $driver = User::factory()->role(UserRole::Driver)->create();
        $customer = Customer::factory()->create(['nama_pabrik' => 'Pabrik Tujuan']);
        $fleet = Fleet::factory()->create(['driver_id' => $driver->id]);
        [$order, $roll] = $this->makeOrderFor($driver, $customer, $fleet, 'LOT-REPORT');
        $this->makeOrderFor($driver, $customer, $fleet, 'LOT-OTHER');

        $query = [
            'from' => '2026-11-01',
            'to' => '2026-11-01',
            'lot' => 'LOT-REPORT',
            'fleet_id' => $fleet->id,
        ];

        $this->actingAs($logistics)
            ->get(route('logistics.reports.deliveries', $query))
            ->assertOk()
            ->assertSee($order->nomor_do)
            ->assertSee($roll->nomor_lot)
            ->assertDontSee('LOT-OTHER');

        $pdf = $this->get(route('logistics.reports.deliveries.pdf', $query));
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $excel = $this->get(route('logistics.reports.deliveries.excel', $query));
        $excel->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition');
        $content = $excel->streamedContent();
        $this->assertStringStartsWith('PK', $content);

        $temporaryFile = tempnam(sys_get_temp_dir(), 'pmt-report-');
        $this->assertNotFalse($temporaryFile);

        try {
            $this->assertNotFalse(file_put_contents($temporaryFile, $content));
            $sheet = IOFactory::createReader('Xlsx')->load($temporaryFile)->getActiveSheet();
            $values = $sheet->toArray();
            $this->assertSame($order->nomor_do, $values[5][0]);
            $this->assertSame('LOT-REPORT', $values[5][6]);
            $this->assertNotContains('LOT-OTHER', array_merge(...$values));
        } finally {
            unlink($temporaryFile);
        }
    }

    public function test_driver_cannot_view_another_drivers_delivery_order(): void
    {
        $driver = User::factory()->role(UserRole::Driver)->create();
        [, $order] = $this->makeOrder();

        $this->actingAs($driver)
            ->get(route('driver.deliveries.show', $order))
            ->assertForbidden();
    }

    /**
     * @return array{User, DeliveryOrder, DtyRoll}
     */
    private function makeOrder(DeliveryOrderStatus $status = DeliveryOrderStatus::Issued): array
    {
        $driver = User::factory()->role(UserRole::Driver)->create();
        $customer = Customer::factory()->create();
        $fleet = Fleet::factory()->create(['driver_id' => $driver->id]);
        [$order, $roll] = $this->makeOrderFor($driver, $customer, $fleet, 'LOT-TEST', $status);

        return [$driver, $order, $roll];
    }

    /**
     * @return array{DeliveryOrder, DtyRoll}
     */
    private function makeOrderFor(
        User $driver,
        Customer $customer,
        Fleet $fleet,
        string $lot,
        DeliveryOrderStatus $status = DeliveryOrderStatus::Issued
    ): array {
        $order = DeliveryOrder::factory()->create([
            'customer_id' => $customer->id,
            'fleet_id' => $fleet->id,
            'driver_id' => $driver->id,
            'tanggal' => '2026-11-01',
            'status' => $status,
        ]);
        $roll = DtyRoll::factory()->create([
            'nomor_lot' => $lot,
            'status' => $status === DeliveryOrderStatus::Received ? RollStatus::Delivered : RollStatus::Allocated,
        ]);
        DeliveryOrderItem::create([
            'delivery_order_id' => $order->id,
            'dty_roll_id' => $roll->id,
            'berat_kg' => '1.50',
        ]);

        return [$order, $roll];
    }

    private function signatureData(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC';
    }
}
